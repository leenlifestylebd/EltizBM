<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Vendor extends Model {
    use SoftDeletes;
    protected $fillable = ['business_id','name','phone','email','address','due_amount'];
    protected $casts = ['due_amount'=>'decimal:2'];
    public function business() { return $this->belongsTo(Business::class); }
    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
}
