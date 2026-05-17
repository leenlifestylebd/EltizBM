<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialPurchase extends Model
{
    protected $fillable = [
        'business_id', 'created_by', 'purchase_date',
        'item_name', 'category', 'shop_name', 'color',
        'quantity', 'rate', 'amount', 'payment_method', 'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'quantity'      => 'decimal:2',
        'rate'          => 'decimal:2',
        'amount'        => 'decimal:2',
    ];

    public static $categories = [
        'fabric'     => 'Fabric / Kapor',
        'accessory'  => 'Accessory (Zipper, Button...)',
        'packaging'  => 'Packaging (Board, Poly...)',
        'embroidery' => 'Embroidery / Design',
        'marketing'  => 'Marketing (Meta Ads...)',
        'logistics'  => 'Logistics / Delivery',
        'other'      => 'Other',
    ];

    public function business()  { return $this->belongsTo(Business::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function getCategoryLabelAttribute(): string
    {
        return self::$categories[$this->category] ?? ucfirst($this->category);
    }
}
