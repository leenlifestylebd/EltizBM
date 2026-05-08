<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendance';

    protected $fillable = [
        'business_id','employee_id','attendance_date',
        'status','check_in','check_out','notes',
    ];

    protected $casts = ['attendance_date' => 'date'];

    public function business() { return $this->belongsTo(Business::class); }
    public function employee() { return $this->belongsTo(Employee::class); }
}