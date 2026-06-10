<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollPeriod extends Model
{
    protected $fillable = [
        'business_id', 'employee_id', 'period_month',
        'gross_earning', 'total_pieces', 'total_paid', 'net_due',
        'is_finalized', 'finalized_by', 'finalized_at', 'notes',
    ];

    protected $casts = [
        'is_finalized' => 'boolean',
        'finalized_at' => 'datetime',
        'gross_earning'=> 'decimal:2',
        'total_paid'   => 'decimal:2',
        'net_due'      => 'decimal:2',
    ];

    public function business()    { return $this->belongsTo(Business::class); }
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function finalizedBy() { return $this->belongsTo(User::class, 'finalized_by'); }

    public function finalize(int $userId, string $notes = ''): void
    {
        $this->update([
            'is_finalized' => true,
            'finalized_by' => $userId,
            'finalized_at' => now(),
            'notes'        => $notes,
        ]);
    }
}
