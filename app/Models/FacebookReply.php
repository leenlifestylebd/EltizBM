<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookReply extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['text' => 'encrypted', 'sent_at' => 'datetime'];
    }
}
