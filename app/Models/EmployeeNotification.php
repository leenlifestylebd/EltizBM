<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeNotification extends Model
{
    protected $fillable = [
        'business_id', 'employee_id', 'type', 'title', 'message', 'link', 'read_at',
    ];

    protected $casts = ['read_at' => 'datetime'];

    public function employee() { return $this->belongsTo(Employee::class); }

    public function scopeUnread($query) { return $query->whereNull('read_at'); }

    public static function notify(int $businessId, int $employeeId, string $type, string $title, ?string $message = null, ?string $link = null): self
    {
        return static::create([
            'business_id' => $businessId,
            'employee_id' => $employeeId,
            'type'        => $type,
            'title'       => $title,
            'message'     => $message,
            'link'        => $link,
        ]);
    }
}
