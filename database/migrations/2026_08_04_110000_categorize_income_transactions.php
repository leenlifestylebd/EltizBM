<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AccountCategory;
use App\Models\Transaction;

return new class extends Migration
{
    /**
     * Back-fill: move existing un-categorised income into per-business categories —
     * order payments → "Sales", tailoring receipts → "Tailoring" — so the Income
     * Sources report stops showing everything as "Uncategorized".
     */
    public function up(): void
    {
        Transaction::query()
            ->where('type', 'income')
            ->whereNull('category_id')
            ->where(fn ($q) => $q->whereNotNull('order_id')->orWhere('reference', 'like', 'TAILORING-%'))
            ->select('business_id')
            ->distinct()
            ->pluck('business_id')
            ->each(function ($bizId) {
                $sales = AccountCategory::firstOrCreate(
                    ['business_id' => $bizId, 'name' => 'Sales', 'type' => 'income'],
                    ['business_id' => $bizId, 'name' => 'Sales', 'type' => 'income']
                );
                $tailoring = AccountCategory::firstOrCreate(
                    ['business_id' => $bizId, 'name' => 'Tailoring', 'type' => 'income'],
                    ['business_id' => $bizId, 'name' => 'Tailoring', 'type' => 'income']
                );

                Transaction::where('business_id', $bizId)->where('type', 'income')
                    ->whereNull('category_id')->whereNotNull('order_id')
                    ->update(['category_id' => $sales->id]);

                Transaction::where('business_id', $bizId)->where('type', 'income')
                    ->whereNull('category_id')->where('reference', 'like', 'TAILORING-%')
                    ->update(['category_id' => $tailoring->id]);
            });
    }

    public function down(): void
    {
        // No-op.
    }
};
