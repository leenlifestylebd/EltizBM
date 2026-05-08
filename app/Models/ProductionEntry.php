<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionEntry extends Model
{
    protected $fillable = [
        'business_id', 'employee_id', 'production_rate_id',
        'entry_date', 'quantity', 'amount', 'notes',
    ];

    protected $casts = ['entry_date' => 'date'];

    public function business()       { return $this->belongsTo(Business::class); }
    public function employee()       { return $this->belongsTo(Employee::class); }
    public function productionRate() { return $this->belongsTo(ProductionRate::class); }
}