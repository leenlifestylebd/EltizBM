<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ActivityLog extends Model {
    protected $fillable = ['business_id','user_id','action','model_type','model_id','old_values','new_values','ip_address'];
    protected $casts = ['old_values'=>'array','new_values'=>'array'];
    public function business() { return $this->belongsTo(Business::class); }
    public function user() { return $this->belongsTo(User::class); }
}
