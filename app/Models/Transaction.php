<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Transaction extends Model {
    use SoftDeletes;
    protected $fillable = ['business_id','category_id','created_by','order_id','type','title','amount','payment_method','reference','notes','transaction_date'];
    protected $casts = ['amount'=>'decimal:2','transaction_date'=>'date'];
    public function business() { return $this->belongsTo(Business::class); }
    public function category() { return $this->belongsTo(AccountCategory::class, 'category_id'); }
}
