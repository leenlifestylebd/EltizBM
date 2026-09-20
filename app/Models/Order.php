<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
class Order extends Model {
    use SoftDeletes;
    protected $fillable = ['business_id','warehouse_id','customer_id','created_by','order_number','source','status','payment_status','payment_method','payment_gateway','gateway_payment_id','gateway_trx_id','customer_name','customer_phone','shipping_address','shipping_area','shipping_district','subtotal','discount','shipping_charge','vat','tax','total','paid_amount','due_amount','notes','woo_order_id','woo_synced_at'];
    protected $casts = ['subtotal'=>'decimal:2','discount'=>'decimal:2','shipping_charge'=>'decimal:2','vat'=>'decimal:2','tax'=>'decimal:2','total'=>'decimal:2','paid_amount'=>'decimal:2','due_amount'=>'decimal:2','woo_synced_at'=>'datetime'];
    protected static function booted(): void {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $prefix = match ($order->source) {
                    'pos'         => 'POS',
                    'woocommerce' => 'WOO',
                    default       => 'ORD',
                };
                $order->order_number = self::generateNumber((int) $order->business_id, $prefix);
            }
        });
    }

    /**
     * Sequential, human-friendly order number: PREFIX-YYMM-NNN
     * (e.g. ORD-2608-001) — a monthly running serial per business + prefix.
     * Old random numbers stay as-is; the YYMM filter keeps them out of the count.
     */
    public static function generateNumber(int $businessId, string $prefix = 'ORD'): string
    {
        $base = $prefix . '-' . now()->format('ym') . '-';   // e.g. ORD-2608-

        $last = self::withTrashed()
            ->where('business_id', $businessId)
            ->where('order_number', 'like', $base . '%')
            ->orderByRaw('LENGTH(order_number) DESC, order_number DESC')
            ->value('order_number');

        $seq = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        // Defensive against a rare concurrent collision (low-concurrency app).
        do {
            $number = $base . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            $seq++;
        } while (self::withTrashed()->where('business_id', $businessId)->where('order_number', $number)->exists());

        return $number;
    }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function holdsStock(): bool
    {
        return !in_array($this->status, ['cancelled', 'returned', 'refunded'], true);
    }
    public function business() { return $this->belongsTo(Business::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function shipments() { return $this->hasMany(Shipment::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function isPaid(): bool { return $this->payment_status === 'paid'; }
    public function hasDue(): bool { return $this->due_amount > 0; }
    public function scopeToday($q) { return $q->whereDate('created_at', today()); }
}
