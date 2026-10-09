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

    // =============================================
    // SHARED REPORT FILTERS
    // =============================================
    // Every report (P&L, Expense by Category, Income Source, Business Overview,
    // dashboard summaries, CSV/PDF exports) must classify rows the same way, so the
    // rules live here once. The "not" scopes are deliberately NULL-safe: a plain
    // `whereNot(reference like ...)` compiles to `NOT (reference LIKE ...)`, which is
    // NULL — and therefore drops the row — for genuine rows that have no reference.

    /** Internal account-transfer legs; both legs share a TRANSFER-<timestamp> reference. */
    public function scopeInternalTransfer($q)
    {
        return $q->where('reference', 'like', 'TRANSFER-%');
    }

    /** Everything that is NOT an internal transfer (rows with a NULL reference included). */
    public function scopeNotInternalTransfer($q)
    {
        return $q->where(fn ($r) => $r->whereNull('reference')->orWhere('reference', 'not like', 'TRANSFER-%'));
    }

    /** Cash paid to workers (advance/salary/bonus) — reference EMPPAY-<employee_id>. */
    public function scopeWorkerPayment($q)
    {
        return $q->where('reference', 'like', 'EMPPAY-%');
    }

    /** Everything that is NOT a worker payment (rows with a NULL reference included). */
    public function scopeNotWorkerPayment($q)
    {
        return $q->where(fn ($r) => $r->whereNull('reference')->orWhere('reference', 'not like', 'EMPPAY-%'));
    }

    /**
     * Operating expense: a real business expense for the period, excluding internal
     * transfers and worker wage payments (the latter are reported separately as
     * "Production Wages" so labour is never counted twice).
     */
    public function scopeOperatingExpense($q)
    {
        return $q->where('type', 'expense')->notInternalTransfer()->notWorkerPayment();
    }
}
