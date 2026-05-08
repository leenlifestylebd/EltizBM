<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionRate extends Model
{
    protected $fillable = [
        'business_id', 'product_name', 'variant_name',
        'color', 'size', 'rate', 'unit', 'is_active',
    ];

    public function business() { return $this->belongsTo(Business::class); }
    public function entries()  { return $this->hasMany(ProductionEntry::class); }

    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->product_name, $this->variant_name, $this->color, $this->size]);
        return implode(' - ', $parts);
    }
}