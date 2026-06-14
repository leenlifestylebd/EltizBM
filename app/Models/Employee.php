<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    public function salaries()        { return $this->hasMany(EmployeeSalary::class)->orderByDesc('effective_from'); }

    /**
     * The monthly salary in effect for a given month — the latest revision whose
     * effective_from is on or before that month. Falls back to the `salary`
     * column when there is no history (so a raise never rewrites past months).
     */
    public function salaryForMonth($month): float
    {
        $m = ($month instanceof \Carbon\Carbon ? $month->copy() : \Carbon\Carbon::parse($month))
                ->startOfMonth()->toDateString();

        // Use the eager-loaded collection when present (relation is ordered DESC,
        // so the first match on-or-before $m is the effective one).
        if ($this->relationLoaded('salaries')) {
            $rec = $this->salaries->first(fn($s) => $s->effective_from->toDateString() <= $m);
            return $rec ? (float) $rec->amount : (float) $this->salary;
        }

        $rec = $this->salaries()->where('effective_from', '<=', $m)->first();
        return $rec ? (float) $rec->amount : (float) $this->salary;
    }

    /** Sum of the effective monthly salary across every month in [$from, $to]. */
    public function salaryOwedForRange($from, $to): float
    {
        $start = \Carbon\Carbon::parse($from)->startOfMonth();
        $end   = \Carbon\Carbon::parse($to)->startOfMonth();
        if ($end->lt($start)) return 0.0;

        $total = 0.0;
        for ($m = $start->copy(); $m->lte($end); $m->addMonth()) {
            $total += $this->salaryForMonth($m);
        }
        return $total;
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
     * Running payroll ledger for production-based employees.
     * Opening = all earnings before $from minus all payments before $from.
     * Closing = opening + period earned - period paid - period deductions.
     * Positive closing = still owed to employee. Negative = overpaid.
     */
    public function payrollLedger($from, $to): array
    {
        $from = is_string($from) ? $from : $from->toDateString();
        $to   = is_string($to)   ? $to   : $to->toDateString();

        // Base salary owed before the period (each month at its effective rate).
        $priorBase = 0.0;
        if ((float)$this->salary > 0 || $this->salaries()->exists()) {
            $start = $this->joining_date
                ? \Carbon\Carbon::parse($this->joining_date)->startOfMonth()
                : \Carbon\Carbon::parse($from)->startOfMonth();
            $beforePeriod = \Carbon\Carbon::parse($from)->startOfMonth()->subMonth();
            if ($start->lte($beforePeriod)) $priorBase = $this->salaryOwedForRange($start, $beforePeriod);
        }

        $priorProduction = (float)$this->productionEntries()->where('entry_date', '<', $from)->sum('amount');
        $priorPaid       = $this->payments()->where('payment_date', '<', $from)->whereIn('type', ['salary', 'advance', 'bonus'])->sum('amount');
        $priorDeducted   = $this->payments()->where('payment_date', '<', $from)->where('type', 'deduction')->sum('amount');
        $opening         = $priorBase + (float)$priorProduction - (float)$priorPaid - (float)$priorDeducted;

        // This period: base salary (effective per month) + production piece-rate.
        $base       = $this->salaryOwedForRange($from, $to);
        $production = (float)$this->productionEntries()->whereBetween('entry_date', [$from, $to])->sum('amount');
        $earned     = $base + $production;
        $advance    = (float)$this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'advance')->sum('amount');
        $bonus      = (float)$this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'bonus')->sum('amount');
        $deduction  = (float)$this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'deduction')->sum('amount');
        $paid       = (float)$this->payments()->whereBetween('payment_date', [$from, $to])->whereIn('type', ['salary', 'advance', 'bonus'])->sum('amount');
        $closing    = $opening + $earned - $deduction - $paid;

        return compact('opening', 'earned', 'base', 'production', 'advance', 'bonus', 'deduction', 'paid', 'closing');
    }

    /**
     * Unified compensation for a period — works for every worker type at once:
     *   - Fixed base salary  (monthly salary × months in range; 0 if none)
     *   - Variable earnings  (production piece-rate; 0 if none)
     * A worker can have one, the other, or both. Net keeps the same convention
     * used across the app: net = base + production − paid − deduction, where
     * `paid` = salary/advance/bonus payments. This reduces exactly to the old
     * salary-only and production-only formulas.
     */
    public function compensation($from, $to): array
    {
        $from = is_string($from) ? $from : $from->toDateString();
        $to   = is_string($to)   ? $to   : $to->toDateString();

        $production = (float) $this->productionEntries()->whereBetween('entry_date', [$from, $to])->sum('amount');

        // Base = sum of the effective salary for each month in the range, so a
        // later raise never changes past months.
        $base = $this->salaryOwedForRange($from, $to);

        $advance   = (float) $this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'advance')->sum('amount');
        $bonus     = (float) $this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'bonus')->sum('amount');
        $deduction = (float) $this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'deduction')->sum('amount');
        $paid      = (float) $this->payments()->whereBetween('payment_date', [$from, $to])->whereIn('type', ['salary', 'advance', 'bonus'])->sum('amount');

        $gross = $base + $production;
        $net   = $gross - $paid - $deduction;

        return [
            'base'           => $base,
            'production'     => $production,
            'gross'          => $gross,
            'advance'        => $advance,
            'bonus'          => $bonus,
            'deduction'      => $deduction,
            'paid'           => $paid,
            'net'            => $net,
            'has_base'       => $base > 0,
            'has_production' => $production > 0,
        ];
    }
}