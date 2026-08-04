<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class TailoringOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'business_id','customer_id','order_number','customer_name','customer_phone',
        'height','body_fit','body_loose','shoulder','sleeve','sleeve_open','neck','round_gher','measurement_note',
        'fabric_type','color','design_note',
        'price','advance_paid',
        'stage','delivery_date','created_by',
    ];

    protected $casts = [
        'height'      => 'decimal:2',
        'body_fit'    => 'decimal:2',
        'body_loose'  => 'decimal:2',
        'shoulder'    => 'decimal:2',
        'sleeve'      => 'decimal:2',
        'sleeve_open' => 'decimal:2',
        'neck'        => 'decimal:2',
        'round_gher'  => 'decimal:2',
        'price'       => 'decimal:2',
        'advance_paid'=> 'decimal:2',
        'delivery_date'=> 'date',
    ];

    const STAGES = [
        'pending'   => ['label' => 'Pending',   'color' => 'gray',   'icon' => '📋'],
        'cutting'   => ['label' => 'Cutting',   'color' => 'blue',   'icon' => '✂️'],
        'stitching' => ['label' => 'Stitching', 'color' => 'yellow', 'icon' => '🪡'],
        'finishing' => ['label' => 'Finishing', 'color' => 'orange', 'icon' => '🔧'],
        'ready'     => ['label' => 'Ready',     'color' => 'green',  'icon' => '✅'],
        'delivered' => ['label' => 'Delivered', 'color' => 'teal',   'icon' => '📦'],
        'cancelled' => ['label' => 'Cancelled', 'color' => 'red',    'icon' => '❌'],
    ];

    const STAGE_FLOW = ['pending','cutting','stitching','finishing','ready','delivered'];

    protected static function booted(): void
    {
        static::creating(function (TailoringOrder $o) {
            if (empty($o->order_number)) {
                $base = 'TLR-' . now()->format('ym') . '-';   // e.g. TLR-2608-
                $last = self::withTrashed()
                    ->where('business_id', $o->business_id)
                    ->where('order_number', 'like', $base . '%')
                    ->orderByRaw('LENGTH(order_number) DESC, order_number DESC')
                    ->value('order_number');
                $seq = $last ? ((int) substr($last, strlen($base))) + 1 : 1;
                do {
                    $number = $base . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                    $seq++;
                } while (self::withTrashed()->where('business_id', $o->business_id)->where('order_number', $number)->exists());
                $o->order_number = $number;
            }
        });
    }

    public function business()   { return $this->belongsTo(Business::class); }
    public function customer()   { return $this->belongsTo(Customer::class); }
    public function createdBy()  { return $this->belongsTo(User::class, 'created_by'); }
    public function tasks()      { return $this->hasMany(ProductionTask::class); }

    public function getDueAmountAttribute(): float
    {
        return max(0, (float)$this->price - (float)$this->advance_paid);
    }

    public function getStageInfoAttribute(): array
    {
        return self::STAGES[$this->stage] ?? self::STAGES['pending'];
    }

    public function getNextStageAttribute(): ?string
    {
        $idx = array_search($this->stage, self::STAGE_FLOW);
        return ($idx !== false && $idx < count(self::STAGE_FLOW) - 1)
            ? self::STAGE_FLOW[$idx + 1]
            : null;
    }

    public function getStageProgressAttribute(): int
    {
        $idx = array_search($this->stage, self::STAGE_FLOW);
        return $idx !== false ? (int) round(($idx / (count(self::STAGE_FLOW) - 1)) * 100) : 0;
    }

    public function scopeForBusiness($q, int $bizId) { return $q->where('business_id', $bizId); }
}
