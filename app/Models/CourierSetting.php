<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CourierSetting extends Model {
    protected $fillable = ['business_id','provider','api_key','api_secret','config','is_active'];
    protected $casts = ['config'=>'array','is_active'=>'boolean','api_key'=>'encrypted','api_secret'=>'encrypted'];
    public function business() { return $this->belongsTo(Business::class); }
}
