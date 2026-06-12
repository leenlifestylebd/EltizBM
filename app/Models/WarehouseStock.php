<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    protected $fillable = ['business_id', 'warehouse_id', 'product_id', 'variant_id', 'quantity'];

    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function product()   { return $this->belongsTo(Product::class); }
}
