<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Warehouse extends Model {
    protected $fillable = ['business_id','name','address','is_default'];
    protected $casts = ['is_default'=>'boolean'];
    public function business() { return $this->belongsTo(Business::class); }
}
