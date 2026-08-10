<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'business_id','name','email','phone','password',
        'role','permissions','avatar','is_active','last_login_at',
        'is_approved','approved_by','approved_at','google_id',
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'permissions'       => 'array',
        'is_active'         => 'boolean',
        'is_approved'       => 'boolean',
        'last_login_at'     => 'datetime',
        'email_verified_at' => 'datetime',
        'approved_at'       => 'datetime',
    ];

    public function business()  { return $this->belongsTo(Business::class); }
    public function employee()  { return $this->hasOne(Employee::class); }
    public function isOwner():   bool { return $this->role === 'owner'; }
    public function isAdmin():   bool { return in_array($this->role, ['owner','admin']); }
    public function isManager(): bool { return in_array($this->role, ['owner','admin','manager']); }
    /**
     * Module-level access check — deny by default.
     * - owner/admin always have full access
     * - everyone else must have the module explicitly granted
     *
     * The old "no permissions set = full access" fallback meant a manager/staff
     * account could reach every module, so permissions could never actually
     * restrict anyone. Existing accounts were backfilled with their modules by
     * migration 2026_08_04_120000 so nobody lost access when this tightened.
     */
    public function hasPermission(string $p): bool
    {
        if ($this->isAdmin()) return true;
        return in_array($p, $this->permissions ?? [], true);
    }

    /** Module keys that can be granted to a manager/staff user. */
    public const MODULES = [
        'orders'       => 'Orders',
        'pos'          => 'POS',
        'products'     => 'Products & Catalog',
        'inventory'    => 'Inventory & Purchases',
        'accounts'     => 'Accounts & Money',
        'reports'      => 'Reports',
        'people'       => 'Customers & Vendors',
        'employees'    => 'Employees & Production',
        'courier'      => 'Courier & Shipping',
        'settings'     => 'Settings',
        'integrations' => 'Integrations',
    ];
}