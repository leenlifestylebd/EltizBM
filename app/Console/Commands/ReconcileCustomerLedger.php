<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile the denormalized customer counters (due_amount, total_purchase) against
 * the orders that are the real source of truth.
 *
 * Safety:
 *   - Dry-run by default; nothing is written unless --apply is passed.
 *   - Never deletes or rewrites history — it only sets the two derived counters.
 *   - Before applying, the previous values are written to a CSV backup AND an
 *     activity_logs row records old → new for every changed customer (auditable,
 *     reversible from the CSV).
 */
class ReconcileCustomerLedger extends Command
{
    protected $signature = 'erp:reconcile-customer-ledger
                            {--apply : write the corrections (default is a read-only dry run)}
                            {--business= : limit to a single business id}';

    protected $description = 'Audit (and optionally correct) customer due/total_purchase counters against orders. No data is deleted.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $business = $this->option('business');

        $customers = Customer::query()
            ->when($business, fn ($q) => $q->where('business_id', $business))
            ->orderBy('business_id')->orderBy('id')
            ->get();

        $changes = [];
        foreach ($customers as $customer) {
            $orders = Order::where('business_id', $customer->business_id)
                ->where('customer_id', $customer->id);

            $derivedDue = (float) (clone $orders)
                ->whereNotIn('status', ['cancelled', 'returned', 'refunded'])
                ->sum('due_amount');
            $derivedTotal = (float) (clone $orders)->sum('total');

            $dueOld   = (float) $customer->due_amount;
            $totalOld = (float) $customer->total_purchase;

            if (abs($dueOld - $derivedDue) > 0.01 || abs($totalOld - $derivedTotal) > 0.01) {
                $changes[] = [
                    'customer'     => $customer,
                    'due_old'      => $dueOld,
                    'due_new'      => max(0, $derivedDue),
                    'total_old'    => $totalOld,
                    'total_new'    => max(0, $derivedTotal),
                ];
            }
        }

        if (empty($changes)) {
            $this->info('No customer counter discrepancies found. Nothing to do.');
            return self::SUCCESS;
        }

        $this->table(
            ['business', 'customer', 'due old', 'due new', 'purch old', 'purch new'],
            array_map(fn ($c) => [
                $c['customer']->business_id,
                $c['customer']->id . ' ' . $c['customer']->name,
                number_format($c['due_old'], 2),
                number_format($c['due_new'], 2),
                number_format($c['total_old'], 2),
                number_format($c['total_new'], 2),
            ], $changes)
        );

        $dueDelta   = array_sum(array_map(fn ($c) => $c['due_new'] - $c['due_old'], $changes));
        $totalDelta = array_sum(array_map(fn ($c) => $c['total_new'] - $c['total_old'], $changes));
        $this->line(sprintf(
            'Customers to change: %d   Due delta: %s   Total_purchase delta: %s',
            count($changes), number_format($dueDelta, 2), number_format($totalDelta, 2)
        ));

        if (!$apply) {
            $this->warn('DRY RUN — nothing written. Re-run with --apply to correct (a CSV backup + audit log are written first).');
            return self::SUCCESS;
        }

        // Backup the previous values before changing anything.
        $backupDir = storage_path('app/reconciliation');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $backupPath = $backupDir . '/customer-ledger-before-' . now()->format('Ymd-His') . '.csv';
        $fh = fopen($backupPath, 'w');
        fputcsv($fh, ['customer_id', 'business_id', 'due_before', 'due_after', 'total_purchase_before', 'total_purchase_after']);
        foreach ($changes as $c) {
            fputcsv($fh, [
                $c['customer']->id, $c['customer']->business_id,
                $c['due_old'], $c['due_new'], $c['total_old'], $c['total_new'],
            ]);
        }
        fclose($fh);

        DB::transaction(function () use ($changes) {
            foreach ($changes as $c) {
                /** @var Customer $customer */
                $customer = $c['customer'];

                ActivityLog::create([
                    'business_id' => $customer->business_id,
                    'user_id'     => auth()->id(),
                    'action'      => 'reconciled',
                    'model_type'  => Customer::class,
                    'model_id'    => $customer->id,
                    'old_values'  => ['due_amount' => $c['due_old'], 'total_purchase' => $c['total_old']],
                    'new_values'  => ['due_amount' => $c['due_new'], 'total_purchase' => $c['total_new']],
                ]);

                $customer->update([
                    'due_amount'     => $c['due_new'],
                    'total_purchase' => $c['total_new'],
                ]);
            }
        });

        $this->info("Applied corrections to " . count($changes) . " customers.");
        $this->line("Backup of previous values: {$backupPath}");
        return self::SUCCESS;
    }
}
