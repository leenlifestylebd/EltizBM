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
}