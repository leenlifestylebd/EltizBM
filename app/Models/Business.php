<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Business extends Model {
    use HasFactory;
    protected $fillable = ['name','slug','logo','phone','email','address','domain','currency','currency_symbol','vat_enabled','vat_rate','vat_label','vat_mode','tax_enabled','tax_rate','tax_label','timezone','plan','trial_ends_at','tags','print_settings','status_config'];
    protected $casts = [
        'vat_enabled'   => 'boolean',
        'tax_enabled'   => 'boolean',
        'trial_ends_at' => 'datetime',
        'tags'          => 'array',
        'print_settings'=> 'array',
        'status_config' => 'array',
    ];
    public function users() { return $this->hasMany(User::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function wooCommerce() { return $this->hasOne(WoocommerceSetting::class); }
    public function isOnTrial(): bool { return $this->trial_ends_at && $this->trial_ends_at->isFuture(); }
    public function trialDaysLeft(): int { if (!$this->trial_ends_at) return 0; return max(0, (int) now()->diffInDays($this->trial_ends_at, false)); }
}
