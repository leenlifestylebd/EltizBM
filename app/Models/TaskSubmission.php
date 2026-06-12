<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskSubmission extends Model
{
    protected $fillable = [
        'business_id', 'production_task_id', 'employee_id',
        'submitted_quantity', 'rate_used', 'amount', 'quality_note', 'submitted_at', 'notes',
    ];

    protected $casts = [
        'submitted_at' => 'date',
        'rate_used'    => 'decimal:2',
        'amount'       => 'decimal:2',
    ];

    public function task()     { return $this->belongsTo(ProductionTask::class, 'production_task_id'); }
    public function employee() { return $this->belongsTo(Employee::class); }
    public function business() { return $this->belongsTo(Business::class); }
}
