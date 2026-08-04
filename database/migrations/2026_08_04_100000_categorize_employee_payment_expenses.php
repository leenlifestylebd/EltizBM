<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AccountCategory;
use App\Models\Transaction;

return new class extends Migration
{
    /**
     * Back-fill: move existing employee-payment expenses (EMPPAY-*) that have no
     * category into a per-business "Salary & Wages" category, so they stop showing
     * under "Uncategorized" in the expense reports.
     */
    public function up(): void
    {
        Transaction::query()
            ->where('type', 'expense')
            ->where('reference', 'like', 'EMPPAY-%')
            ->whereNull('category_id')
            ->select('business_id')
            ->distinct()
            ->pluck('business_id')
            ->each(function ($bizId) {
                $cat = AccountCategory::firstOrCreate(
                    ['business_id' => $bizId, 'name' => 'Salary & Wages', 'type' => 'expense'],
                    ['business_id' => $bizId, 'name' => 'Salary & Wages', 'type' => 'expense']
                );

                Transaction::where('business_id', $bizId)
                    ->where('type', 'expense')
                    ->where('reference', 'like', 'EMPPAY-%')
                    ->whereNull('category_id')
                    ->update(['category_id' => $cat->id]);
            });
    }

    public function down(): void
    {
        // No-op: we don't un-categorise on rollback.
    }
};
