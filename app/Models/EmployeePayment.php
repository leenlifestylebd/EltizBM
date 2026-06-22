<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeePayment extends Model
{
    protected $fillable = [
        'business_id', 'employee_id', 'created_by', 'transaction_id',
        'payment_date', 'amount', 'type', 'payment_method', 'notes',
    ];

    protected $casts = ['payment_date' => 'date'];

    public function business()    { return $this->belongsTo(Business::class); }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by'); }
    public function transaction() { return $this->belongsTo(Transaction::class, 'transaction_id'); }
}