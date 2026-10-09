<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id','user_id','name','phone','email',
        'designation','department','salary','joining_date',
        'status','type','working_days','per_day_salary',
    ];

    protected $casts = [
        'salary'       => 'decimal:2',
        'joining_date' => 'date',
    ];

    public function business()        { return $this->belongsTo(Business::class); }
    public function user()            { return $this->belongsTo(User::class); }
    public function productionLogs()  { return $this->hasMany(ProductionLog::class); }
    public function productionEntries() { return $this->hasMany(ProductionEntry::class); }
    public function payments()        { return $this->hasMany(EmployeePayment::class); }
    public function attendance()      { return $this->hasMany(Attendance::class); }
    public function attendances()     { return $this->hasMany(Attendance::class); }
    public function productionTasks() { return $this->hasMany(ProductionTask::class); }
    public function salaries()        { return $this->hasMany(EmployeeSalary::class)->orderByDesc('effective_from')->orderByDesc('id'); }

    /** Salary history defines payroll eligibility, independently of HR joining date. */
    public function salaryStartMonth(): ?Carbon
    {
        $this->loadMissing('salaries');
        if ($this->salaries->isNotEmpty()) {
            return Carbon::parse($this->salaries->min('effective_from'))->startOfMonth();
        }

        // Legacy employees without revisions have a stable start, never the viewed month.
        $start = $this->joining_date ?? $this->created_at;
        return $start ? Carbon::parse($start)->startOfMonth() : null;
    }

    public function salaryForMonth($month): float
    {
        $month = Carbon::parse($month)->startOfMonth();
        $this->loadMissing('salaries');
        if ($this->salaries->isNotEmpty()) {
            $revision = $this->salaries->first(fn ($salary) => $salary->effective_from->lte($month));
            // A gap before recorded history is not permission to use today's salary.
            return $revision ? (float) $revision->amount : 0.0;
        }

        $start = $this->salaryStartMonth();
        return $start && $month->gte($start) ? (float) $this->salary : 0.0;
    }

    /** Sum the effective monthly rates, including both endpoint months. */
    public function salaryOwedForRange($from, $to): float
    {
        $start = Carbon::parse($from)->startOfMonth();
        $end = Carbon::parse($to)->startOfMonth();
        $eligible = $this->salaryStartMonth();
        if (!$eligible || $end->lt($start) || $end->lt($eligible)) return 0.0;
        if ($start->lt($eligible)) $start = $eligible;

        $total = 0.0;
        for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
            $total += $this->salaryForMonth($month);
        }
        return round($total, 2);
    }

    public function todayProduction(): int
    {
        return $this->productionLogs()->whereDate('log_date', today())->sum('quantity');
    }

    public function todayAttendance(): ?Attendance
    {
        return $this->attendance()->whereDate('attendance_date', today())->first();
    }

    // Dynamic properties for payroll
    public int $today_production  = 0;
    public int $month_production  = 0;

    /**
     * Salary is credited on the first day of its effective month. Salary/advance
     * payments settle it; paid bonuses are separate and never settle base salary.
     * Positive opening/closing balances are owed to the employee.
     */
    public function payrollLedger($from, $to): array
    {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->startOfDay();
        if ($to->lt($from)) throw new \InvalidArgumentException('Payroll end must not precede its start.');

        $firstSalaryMonth = $from->copy()->startOfMonth();
        if ($from->day !== 1) $firstSalaryMonth->addMonth();
        $start = $this->salaryStartMonth();
        $priorBase = $start
            ? $this->salaryOwedForRange($start, $firstSalaryMonth->copy()->subMonth()) : 0.0;
        $base = $this->salaryOwedForRange($firstSalaryMonth, $to);
        $from = $from->toDateString();
        $to = $to->endOfDay()->toDateTimeString();

        $priorProduction = (float) $this->productionEntries()->where('entry_date', '<', $from)->sum('amount');
        $production = (float) $this->productionEntries()->whereBetween('entry_date', [$from, $to])->sum('amount');
        $prior = $this->payments()->where('payment_date', '<', $from)
            ->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');
        $period = $this->payments()->whereBetween('payment_date', [$from, $to])
            ->selectRaw('type, SUM(amount) as total')->groupBy('type')->pluck('total', 'type');

        $priorPaid = (float) ($prior['salary'] ?? 0) + (float) ($prior['advance'] ?? 0);
        $priorDeducted = (float) ($prior['deduction'] ?? 0);
        $advance = (float) ($period['advance'] ?? 0);
        $bonus = (float) ($period['bonus'] ?? 0);
        $deduction = (float) ($period['deduction'] ?? 0);
        $paid = round((float) ($period['salary'] ?? 0) + $advance, 2);
        $earned = round($base + $production, 2);
        $opening = round($priorBase + $priorProduction - $priorPaid - $priorDeducted, 2);
        $closing = round($opening + $earned - $paid - $deduction, 2);
        $cumulativeEarned = round($priorBase + $priorProduction + $earned, 2);
        $cumulativePaid = round($priorPaid + $paid, 2);

        return compact('opening', 'earned', 'base', 'production', 'advance', 'bonus',
            'deduction', 'paid', 'closing', 'cumulativeEarned', 'cumulativePaid');
    }

    /** Period-only compensation; use payrollLedger for a balance including arrears. */
    public function compensation($from, $to): array
    {
        $ledger = $this->payrollLedger($from, $to);
        return [
            'base' => $ledger['base'],
            'production' => $ledger['production'],
            'gross' => $ledger['earned'],
            'advance' => $ledger['advance'],
            'bonus' => $ledger['bonus'],
            'deduction' => $ledger['deduction'],
            'paid' => $ledger['paid'],
            'net' => round($ledger['earned'] - $ledger['paid'] - $ledger['deduction'], 2),
            'has_base' => $ledger['base'] > 0,
            'has_production' => $ledger['production'] > 0,
        ];
    }
}
