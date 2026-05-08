<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseOrder extends Model {
    protected $fillable = ['business_id','vendor_id','created_by','po_number','status','payment_status','total','paid_amount','expected_date','notes'];
    protected $casts = ['total'=>'decimal:2','paid_amount'=>'decimal:2','expected_date'=>'date'];
    public function business() { return $this->belongsTo(Business::class); }
    public function vendor() { return $this->belongsTo(Vendor::class); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
}
