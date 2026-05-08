<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WoocommerceSetting extends Model {
    protected $fillable = ['business_id','store_url','consumer_key','consumer_secret','is_active','sync_products','sync_orders','sync_stock','last_synced_at','webhook_secret'];
    protected $hidden = ['consumer_key','consumer_secret'];
    protected $casts = ['is_active'=>'boolean','sync_products'=>'boolean','sync_orders'=>'boolean','sync_stock'=>'boolean','last_synced_at'=>'datetime'];
    public function business() { return $this->belongsTo(Business::class); }
    public function getApiUrl(string $endpoint): string { return rtrim($this->store_url,'/').'/wp-json/wc/v3/'.ltrim($endpoint,'/'); }
}
