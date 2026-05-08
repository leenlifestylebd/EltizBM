<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Attribute extends Model {
    protected $fillable = ['business_id','name','type'];
    public function business() { return $this->belongsTo(Business::class); }
    public function values() { return $this->hasMany(AttributeValue::class)->orderBy('sort_order'); }
}
