<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductVariant extends Model {
    protected $fillable = ['product_id','name','sku','barcode','price','cost_price','stock','attributes','woo_variant_id'];
    protected $casts = ['attributes'=>'array','price'=>'decimal:2','cost_price'=>'decimal:2'];
    public function product() { return $this->belongsTo(Product::class); }
}
