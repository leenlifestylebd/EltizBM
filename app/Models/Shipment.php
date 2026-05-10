<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Shipment extends Model {
    protected $fillable = ['business_id','order_id','courier_name','tracking_id','consignment_id','status','charge','cod_amount','notes','shipped_at','delivered_at'];
    protected $casts = ['charge'=>'decimal:2','cod_amount'=>'decimal:2','shipped_at'=>'datetime','delivered_at'=>'datetime'];
    public function business() { return $this->belongsTo(Business::class); }
    public function order() { return $this->belongsTo(Order::class); }
}
