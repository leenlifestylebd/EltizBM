<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Business extends Model {
    use HasFactory;
    protected $fillable = ['name','slug','logo','phone','email','address','domain','currency','currency_symbol','vat_enabled','vat_rate','vat_label','vat_mode','tax_enabled','tax_rate','tax_label','timezone','plan','trial_ends_at','tags','print_settings','status_config','theme_settings'];
    protected $casts = [
        'vat_enabled'   => 'boolean',
        'tax_enabled'   => 'boolean',
        'trial_ends_at' => 'datetime',
        'tags'          => 'array',
        'print_settings'=> 'array',
        'status_config' => 'array',
        'theme_settings'=> 'array',
    ];

    /**
     * Generate a full Tailwind-compatible color palette from a single hex color.
     * Mixes with white for light shades, darkens for dark shades.
     */
    public static function generatePalette(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));

        $mix = function (float $ratio) use ($r, $g, $b): string {
            return sprintf('#%02x%02x%02x',
                (int) round($r + (255 - $r) * $ratio),
                (int) round($g + (255 - $g) * $ratio),
                (int) round($b + (255 - $b) * $ratio),
            );
        };

        $darken = function (float $ratio) use ($r, $g, $b): string {
            return sprintf('#%02x%02x%02x',
                (int) round($r * (1 - $ratio)),
                (int) round($g * (1 - $ratio)),
                (int) round($b * (1 - $ratio)),
            );
        };

        return [
            '50'  => $mix(0.95),
            '100' => $mix(0.88),
            '200' => $mix(0.72),
            '300' => $mix(0.52),
            '400' => $mix(0.26),
            '500' => '#' . $hex,
            '600' => $darken(0.10),
            '700' => $darken(0.22),
            '800' => $darken(0.38),
            '900' => $darken(0.52),
        ];
    }

    /** Get the active theme palette (returns default blue if no theme set). */
    public function themePalette(): array
    {
        $color = $this->theme_settings['primary_color'] ?? '#2563eb';
        return static::generatePalette($color);
    }

    public function themeColor(string $shade = '600'): string
    {
        return $this->themePalette()[$shade] ?? '#2563eb';
    }
    public function users() { return $this->hasMany(User::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function customers() { return $this->hasMany(Customer::class); }
    public function employees() { return $this->hasMany(Employee::class); }
    public function wooCommerce() { return $this->hasOne(WoocommerceSetting::class); }
    public function isOnTrial(): bool { return $this->trial_ends_at && $this->trial_ends_at->isFuture(); }
    public function trialDaysLeft(): int { if (!$this->trial_ends_at) return 0; return max(0, (int) now()->diffInDays($this->trial_ends_at, false)); }
}
