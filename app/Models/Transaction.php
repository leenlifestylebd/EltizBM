<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Transaction extends Model {
    use SoftDeletes;
    protected $fillable = ['business_id','category_id','created_by','order_id','type','title','amount','payment_method','reference','notes','transaction_date'];
    protected $casts = ['amount'=>'decimal:2','transaction_date'=>'date'];

    protected static function booted(): void
    {
        // Auto-categorise income so it doesn't pile up under "Uncategorized":
        //   order payments → "Sales", tailoring receipts → "Tailoring".
        // Manual income keeps whatever category the user picked (or none).
        static::creating(function (Transaction $t) {
            if ($t->category_id || $t->type !== 'income' || !$t->business_id) return;

            $name = $t->order_id
                ? 'Sales'
                : (($t->reference && str_starts_with($t->reference, 'TAILORING-')) ? 'Tailoring' : null);

            if ($name) {
                $t->category_id = AccountCategory::firstOrCreate(
                    ['business_id' => $t->business_id, 'name' => $name, 'type' => 'income'],
                    ['business_id' => $t->business_id, 'name' => $name, 'type' => 'income']
                )->id;
            }
        });
    }

    public function business() { return $this->belongsTo(Business::class); }
    public function category() { return $this->belongsTo(AccountCategory::class, 'category_id'); }
}
