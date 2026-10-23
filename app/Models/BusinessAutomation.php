<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessAutomation extends Model
{
    public const ABILITIES = [
        'catalog:read' => 'Read products and stock',
        'orders:read' => 'Read order status',
        'orders:write' => 'Create orders',
        'reports:read' => 'Read business reports',
        'transactions:write' => 'Record income and expenses',
        'automation:read' => 'Let trusted automation read saved service credentials',
    ];

    public const SECRET_FIELDS = [
        'openrouter_api_key', 'telegram_bot_token', 'facebook_page_token',
        'facebook_app_secret', 'facebook_verify_token', 'n8n_webhook_secret',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['token_hash', 'secrets'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'abilities' => 'array', 'settings' => 'array', 'secrets' => 'encrypted:array'];
    }
}
