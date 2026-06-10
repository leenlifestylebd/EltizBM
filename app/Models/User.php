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
     * Module-level access check.
     * - owner/admin always have full access
     * - a user with no explicit permissions set keeps full access (backward compatible
     *   with every account that existed before per-user permissions were introduced)
     * - once permissions ARE set, only the listed modules are allowed
     */
    public function hasPermission(string $p): bool
    {
        if ($this->isAdmin()) return true;
        $perms = $this->permissions ?? [];
        if (empty($perms)) return true;
        return in_array($p, $perms);
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