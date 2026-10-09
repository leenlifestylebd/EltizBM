<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = ['business_id', 'name', 'method', 'type', 'opening_balance', 'is_active'];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    public function business() { return $this->belongsTo(Business::class); }
}
