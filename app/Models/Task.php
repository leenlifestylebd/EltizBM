<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id','created_by','assigned_to',
        'title','description','priority','status','due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function business()   { return $this->belongsTo(Business::class); }
    public function createdBy()  { return $this->belongsTo(User::class, 'created_by'); }
    public function assignedTo() { return $this->belongsTo(User::class, 'assigned_to'); }
}