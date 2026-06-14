<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id', 'employee_id', 'assigned_by', 'production_rate_id',
        'tailoring_order_id', 'stage', 'source_task_id',
        'product_name', 'color', 'size',
        'assigned_quantity', 'completed_quantity',
        'status', 'deadline', 'notes', 'assigned_at',
    ];

    protected $casts = [
        'deadline'    => 'date',
        'assigned_at' => 'datetime',
    ];

    public function employee()        { return $this->belongsTo(Employee::class); }
    public function assignedBy()      { return $this->belongsTo(User::class, 'assigned_by'); }
    public function productionRate()  { return $this->belongsTo(ProductionRate::class); }
    public function submissions()     { return $this->hasMany(TaskSubmission::class); }
    public function business()        { return $this->belongsTo(Business::class); }
    public function tailoringOrder()  { return $this->belongsTo(TailoringOrder::class); }
    public function sourceTask()      { return $this->belongsTo(ProductionTask::class, 'source_task_id'); }
    public function forwardedTasks()  { return $this->hasMany(ProductionTask::class, 'source_task_id'); }

    public function forwardedQuantity(): int
    {
        return (int) $this->forwardedTasks()->whereNotIn('status', ['cancelled'])->sum('assigned_quantity');
    }

    public function getForwardableQuantityAttribute(): int
    {
        return max(0, $this->completed_quantity - $this->forwardedQuantity());
    }

    /** The next stage in the pipeline after this task's stage, or null if last. */
    public function nextStage(): ?string
    {
        $order = ProductionRate::PIPELINE;
        $i = array_search($this->stage ?? 'sewing', $order, true);
        if ($i === false || $i === count($order) - 1) return null;
        return $order[$i + 1];
    }

    public function nextStageLabel(): ?string
    {
        $next = $this->nextStage();
        return $next ? (ProductionRate::STAGES[$next] ?? ucfirst($next)) : null;
    }

    /** Can completed pieces still be forwarded to a following stage? */
    public function canForward(): bool
    {
        return $this->nextStage() !== null && $this->forwardable_quantity > 0;
    }

    public function getPendingQuantityAttribute(): int
    {
        return max(0, $this->assigned_quantity - $this->completed_quantity);
    }

    public function getCompletionPercentAttribute(): int
    {
        if ($this->assigned_quantity === 0) return 0;
        return (int) min(100, round(($this->completed_quantity / $this->assigned_quantity) * 100));
    }

    public function isOverdue(): bool
    {
        return $this->deadline
            && $this->deadline->isPast()
            && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function recalculateStatus(): void
    {
        if ($this->status === 'cancelled') return;

        if ($this->completed_quantity >= $this->assigned_quantity) {
            $this->status = 'completed';
        } elseif ($this->completed_quantity > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'assigned';
        }

        $this->save();
    }
}
