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

    public function todayProduction(): int
    {
        return $this->productionLogs()->whereDate('log_date', today())->sum('quantity');
    }

    public function todayAttendance(): ?Attendance
    {
        return $this->attendance()->whereDate('attendance_date', today())->first();
    }

    /**
     * Professional payroll ledger for a period.
     *
     * Returns the opening balance carried in from before the period, this period's
     * earnings and payments, and the closing balance carried out.
     *
     *   closing = opening + (earned + bonus) − (deduction + advance + salaryPaid)
     *
     * A POSITIVE closing balance = the business still owes the worker (due).
     * A NEGATIVE closing balance = the worker was paid/advanced more than earned and
     * that amount carries forward as their advance (overpay), instead of being hidden.
     */
    public function payrollLedger($start, $end): array
    {
        $start = \Carbon\Carbon::parse($start)->startOfDay();
        $end   = \Carbon\Carbon::parse($end)->endOfDay();
        $beforeStart = $start->copy()->subDay()->endOfDay();

        $earnedUpTo = function ($cutoff) {
            if ($this->type === 'production') {
                return (float) $this->productionEntries()->where('entry_date', '<=', $cutoff)->sum('amount');
            }
            $perDay = $this->working_days ? ((float) $this->salary / $this->working_days) : 0;
            $days   = $this->attendances()->where('attendance_date', '<=', $cutoff)
                ->whereIn('status', ['present', 'half_day'])->count();
            return round($perDay * $days, 2);
        };

        $payAgg = function ($from, $to) {
            $q = $this->payments();
            if ($from) $q->where('payment_date', '>=', $from);
            if ($to)   $q->where('payment_date', '<=', $to);
            $rows = $q->get();
            return [
                'advance'   => (float) $rows->where('type', 'advance')->sum('amount'),
                'bonus'     => (float) $rows->where('type', 'bonus')->sum('amount'),
                'deduction' => (float) $rows->where('type', 'deduction')->sum('amount'),
                'paid'      => (float) $rows->where('type', 'salary')->sum('amount'),
            ];
        };

        // Opening balance: everything strictly before the period.
        $earnedBefore = $earnedUpTo($beforeStart);
        $pBefore      = $payAgg(null, $beforeStart);
        $opening      = ($earnedBefore + $pBefore['bonus']) - ($pBefore['deduction'] + $pBefore['advance'] + $pBefore['paid']);

        // This period.
        if ($this->type === 'production') {
            $earned = (float) $this->productionEntries()->whereBetween('entry_date', [$start, $end])->sum('amount');
        } else {
            $perDay = $this->working_days ? ((float) $this->salary / $this->working_days) : 0;
            $days   = $this->attendances()->whereBetween('attendance_date', [$start, $end])
                ->whereIn('status', ['present', 'half_day'])->count();
            $earned = round($perDay * $days, 2);
        }
        $p = $payAgg($start, $end);

        $closing = $opening + ($earned + $p['bonus']) - ($p['deduction'] + $p['advance'] + $p['paid']);

        return [
            'opening'   => round($opening, 2),
            'earned'    => round($earned, 2),
            'bonus'     => round($p['bonus'], 2),
            'deduction' => round($p['deduction'], 2),
            'advance'   => round($p['advance'], 2),
            'paid'      => round($p['paid'], 2),
            'closing'   => round($closing, 2),
        ];
    }

    // Dynamic properties for payroll
    public int $today_production  = 0;
    public int $month_production  = 0;
}