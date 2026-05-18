<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PaymentGatewaySetting extends Model
{
    protected $fillable = ['business_id', 'gateway', 'is_active', 'sandbox', 'credentials'];

    protected $casts = [
        'is_active'   => 'boolean',
        'sandbox'     => 'boolean',
    ];

    // Store credentials encrypted
    public function setCredentialsAttribute(array $value): void
    {
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    public function getCredentialsAttribute($value): array
    {
        try {
            return json_decode(Crypt::decryptString($value), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public static function forBusiness(int $bizId, string $gateway): ?self
    {
        return static::where('business_id', $bizId)
            ->where('gateway', $gateway)
            ->where('is_active', true)
            ->first();
    }
}
