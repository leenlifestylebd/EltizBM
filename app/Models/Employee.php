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

    // Dynamic properties for payroll
    public int $today_production  = 0;
    public int $month_production  = 0;

    /**
     * Running payroll ledger for production-based employees.
     * Opening = all earnings before $from minus all payments before $from.
     * Closing = opening + period earned - period paid + period deductions.
     * Positive closing = still owed to employee. Negative = overpaid.
     */
    public function payrollLedger($from, $to): array
    {
        $from = is_string($from) ? $from : $from->toDateString();
        $to   = is_string($to)   ? $to   : $to->toDateString();

        $priorEarned    = $this->productionEntries()->where('entry_date', '<', $from)->sum('amount');
        $priorPaid      = $this->payments()->where('payment_date', '<', $from)->whereIn('type', ['salary', 'advance', 'bonus'])->sum('amount');
        $priorDeducted  = $this->payments()->where('payment_date', '<', $from)->where('type', 'deduction')->sum('amount');
        $opening        = (float)$priorEarned - (float)$priorPaid + (float)$priorDeducted;

        $earned    = (float)$this->productionEntries()->whereBetween('entry_date', [$from, $to])->sum('amount');
        $advance   = (float)$this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'advance')->sum('amount');
        $bonus     = (float)$this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'bonus')->sum('amount');
        $deduction = (float)$this->payments()->whereBetween('payment_date', [$from, $to])->where('type', 'deduction')->sum('amount');
        $paid      = (float)$this->payments()->whereBetween('payment_date', [$from, $to])->whereIn('type', ['salary', 'advance', 'bonus'])->sum('amount');
        $closing   = $opening + $earned + $deduction - $paid;

        return compact('opening', 'earned', 'advance', 'bonus', 'deduction', 'paid', 'closing');
    }
}