<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookInboxEvent extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['lease_hash'];

    protected function casts(): array
    {
        return ['payload' => 'encrypted:array', 'draft' => 'encrypted', 'lease_until' => 'datetime'];
    }

    public function reply()
    {
        return $this->hasOne(FacebookReply::class, 'event_id');
    }
}
