<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Business;
use Illuminate\Console\Command;

/**
 * Ensure every business has an accounts row per known payment method so opening
 * balances can be recorded. Additive and idempotent: existing rows are never changed,
 * and no other table is touched.
 */
class SeedAccounts extends Command
{
    protected $signature = 'erp:accounts:seed {--business= : limit to a single business id}';

    protected $description = 'Create the default accounts rows (opening balance 0) for each business. Additive, never overwrites.';

    protected array $methods = [
        'cash' => ['Cash', 'cash'],
        'bkash' => ['bKash', 'mfs'],
        'nagad' => ['Nagad', 'mfs'],
        'rocket' => ['Rocket', 'mfs'],
        'bank' => ['Bank', 'bank'],
        'card' => ['Card', 'bank'],
        'cod' => ['Cash on Delivery', 'cash'],
        'other' => ['Other', 'other'],
    ];

    public function handle(): int
    {
        $business = $this->option('business');
        $created = 0;

        foreach (Business::when($business, fn ($q) => $q->where('id', $business))->get() as $biz) {
            foreach ($this->methods as $method => [$name, $type]) {
                $account = Account::firstOrCreate(
                    ['business_id' => $biz->id, 'method' => $method],
                    ['name' => $name, 'type' => $type, 'opening_balance' => 0, 'is_active' => true]
                );
                if ($account->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        $this->info("Accounts ready. Created {$created} new row(s).");
        return self::SUCCESS;
    }
}
