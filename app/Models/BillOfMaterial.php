<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillOfMaterial extends Model
{
    protected $table = 'bill_of_materials';

    protected $fillable = [
        'business_id', 'product_id', 'material_name', 'category',
        'quantity_per_piece', 'unit', 'wastage_percent', 'color',
        'notes', 'is_active',
    ];

    protected $casts = [
        'quantity_per_piece' => 'decimal:4',
        'wastage_percent'    => 'decimal:2',
        'is_active'          => 'boolean',
    ];

    public function business() { return $this->belongsTo(Business::class); }
    public function product()  { return $this->belongsTo(Product::class); }

    /**
     * Total material required per piece including wastage.
     */
    public function getEffectiveQtyAttribute(): float
    {
        return (float)$this->quantity_per_piece * (1 + ($this->wastage_percent / 100));
    }

    /**
     * Total material needed for a given production quantity.
     */
    public function totalRequired(int $pieces): float
    {
        return $this->effective_qty * $pieces;
    }
}
