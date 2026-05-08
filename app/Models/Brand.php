<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Brand extends Model {
    protected $fillable = ['business_id','name','slug','logo','is_active'];
    protected $casts = ['is_active'=>'boolean'];
    public function business() { return $this->belongsTo(Business::class); }
    public function products() { return $this->hasMany(Product::class); }
}
