<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model {
    protected $fillable = ['order_id','product_id','variant_id','product_name','sku','price','cost_price','quantity','discount','total'];
    protected $casts = ['price'=>'decimal:2','cost_price'=>'decimal:2','discount'=>'decimal:2','total'=>'decimal:2'];
    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
