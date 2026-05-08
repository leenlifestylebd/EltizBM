<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Product extends Model {
    use HasFactory, SoftDeletes;
    protected $fillable = ['business_id','category_id','brand_id','name','slug','sku','barcode','description','price','cost_price','sale_price','stock','low_stock_alert','type','unit','weight','images','is_active','track_stock','woo_product_id','woo_synced_at'];
    protected $casts = ['images'=>'array','is_active'=>'boolean','track_stock'=>'boolean','price'=>'decimal:2','cost_price'=>'decimal:2','sale_price'=>'decimal:2','woo_synced_at'=>'datetime'];
    public function business() { return $this->belongsTo(Business::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function brand() { return $this->belongsTo(Brand::class); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
    public function getCurrentPriceAttribute(): float { return $this->sale_price ?? $this->price; }
    public function isLowStock(): bool { return $this->stock <= $this->low_stock_alert; }
    public function isOutOfStock(): bool { return $this->stock <= 0; }
    public function scopeActive($q) { return $q->where('is_active', true); }
}
