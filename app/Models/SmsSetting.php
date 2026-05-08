<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SmsSetting extends Model {
    protected $fillable = ['business_id','provider','api_key','sender_id','order_confirm','shipment_update','payment_received'];
    protected $casts = ['order_confirm'=>'boolean','shipment_update'=>'boolean','payment_received'=>'boolean'];
    public function business() { return $this->belongsTo(Business::class); }
}
