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
        'is_approved','approved_by','approved_at',
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
    public function hasPermission(string $p): bool { if ($this->isOwner()) return true; return in_array($p, $this->permissions ?? []); }
}