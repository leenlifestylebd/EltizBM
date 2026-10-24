<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookProductMapping extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['auto_reply' => 'boolean'];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
