<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Customer extends Model {
    use SoftDeletes;
    protected $fillable = ['business_id','name','phone','email','address','area','district','due_amount','total_purchase','woo_customer_id'];
    protected $casts = ['due_amount'=>'decimal:2','total_purchase'=>'decimal:2'];
    public function business() { return $this->belongsTo(Business::class); }
    public function orders() { return $this->hasMany(Order::class); }
}
