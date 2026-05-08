<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AccountCategory extends Model {
    protected $fillable = ['business_id','name','type'];
    public function business() { return $this->belongsTo(Business::class); }
    public function transactions() { return $this->hasMany(Transaction::class, 'category_id'); }
}
