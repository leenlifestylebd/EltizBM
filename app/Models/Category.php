<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Category extends Model {
    protected $fillable = ['business_id','parent_id','name','slug','image','description','sort_order','is_active'];
    protected $casts = ['is_active'=>'boolean'];
    public function business() { return $this->belongsTo(Business::class); }
    public function parent() { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id'); }
    public function products() { return $this->hasMany(Product::class); }
}
