<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockAdjustment extends Model {
    protected $fillable = ['business_id','product_id','variant_id','warehouse_id','created_by','type','quantity','quantity_before','quantity_after','reason'];
    public function business() { return $this->belongsTo(Business::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
