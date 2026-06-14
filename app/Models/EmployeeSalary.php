<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalary extends Model
{
    protected $fillable = [
        'business_id', 'employee_id', 'amount', 'effective_from', 'created_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'effective_from' => 'date',
    ];

    public function employee() { return $this->belongsTo(Employee::class); }
}
