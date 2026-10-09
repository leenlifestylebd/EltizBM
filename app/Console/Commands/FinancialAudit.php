<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only financial reconciliation report.
 *
 * Runs only SELECT queries — it never writes, updates or deletes anything, so it is
 * safe to run against any environment. It reports where the denormalized counters and
 * the underlying transactions disagree so the discrepancies can be reviewed.
 */
class FinancialAudit extends Command
{
    protected $signature = 'erp:financial-audit {--business= : limit to a single business id} {--csv= : also write the findings to a CSV file (path optional)}';

    protected $description = 'Read-only report of order/customer/stock reconciliation discrepancies. Writes nothing.';

    public function handle(): int
    {
        $business = $this->option('business');

        $scoped = fn ($q) => $business ? $q->where('business_id', $business) : $q;

        $findings = [];

        // 1. Orders with line items whose total does not equal items − discount + shipping.
        $lineMismatch = $scoped(DB::table('orders as o'))
            ->join('order_items as oi', 'oi.order_id', '=', 'o.id')
            ->whereNull('o.deleted_at')
            ->select('o.id')
            ->groupBy('o.id', 'o.total', 'o.discount', 'o.shipping_charge')
            ->havingRaw('ABS(o.total - (COALESCE(SUM(oi.total),0) - o.discount + o.shipping_charge)) > 0.05')
            ->get()->count();
        $findings[] = ['order total vs line items', $lineMismatch];

        // 2. Orders where due ≠ total − paid.
        $dueBad = $scoped(DB::table('orders'))->whereNull('deleted_at')
            ->whereRaw('ABS(due_amount - (total - paid_amount)) > 0.01')->count();
        $findings[] = ['order due ≠ total − paid', $dueBad];

        // 3. Orders where paid_amount ≠ Σ income transactions.
        $paidVsIncome = DB::query()->fromSub(
            $scoped(DB::table('orders as o'))
                ->selectRaw('o.paid_amount - COALESCE((SELECT SUM(t.amount) FROM transactions t
                    WHERE t.order_id = o.id AND t.type = ? AND t.deleted_at IS NULL),0) AS d', ['income'])
                ->whereNull('o.deleted_at'),
            'x'
        )->whereRaw('ABS(d) > 0.01')->count();
        $findings[] = ['order paid ≠ Σ income txns', $paidVsIncome];

        // 4. Customers whose due counter ≠ the open orders they hold.
        $custDue = DB::query()->fromSub(
            $scoped(DB::table('customers as c'))
                ->leftJoin('orders as o', function ($j) {
                    $j->on('o.customer_id', '=', 'c.id')
                      ->on('o.business_id', '=', 'c.business_id')
                      ->whereNotIn('o.status', ['cancelled', 'returned', 'refunded'])
                      ->whereNull('o.deleted_at');
                })
                ->selectRaw('c.id, c.due_amount, COALESCE(SUM(o.due_amount),0) open_due')
                ->groupBy('c.id', 'c.due_amount'),
            'y'
        )->whereRaw('ABS(due_amount - open_due) > 0.01')->count();
        $findings[] = ['customer due ≠ open orders', $custDue];

        // 5. Customers whose total_purchase ≠ Σ order totals.
        $custTotal = DB::query()->fromSub(
            $scoped(DB::table('customers as c'))
                ->leftJoin('orders as o', function ($j) {
                    $j->on('o.customer_id', '=', 'c.id')
                      ->on('o.business_id', '=', 'c.business_id')
                      ->whereNull('o.deleted_at');
                })
                ->selectRaw('c.id, c.total_purchase, COALESCE(SUM(o.total),0) orders_total')
                ->groupBy('c.id', 'c.total_purchase'),
            'z'
        )->whereRaw('ABS(total_purchase - orders_total) > 0.01')->count();
        $findings[] = ['customer total_purchase ≠ orders', $custTotal];

        // 6. Orphan order-linked transactions.
        $orphans = $scoped(DB::table('transactions as t'))
            ->whereNotNull('t.order_id')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('orders as o')->whereColumn('o.id', 't.order_id'))
            ->count();
        $findings[] = ['orphan order-linked txns', $orphans];

        // 7. Cross-tenant order/transaction mismatch.
        $crossTenant = $scoped(DB::table('transactions as t'))
            ->join('orders as o', 'o.id', '=', 't.order_id')
            ->whereColumn('t.business_id', '<>', 'o.business_id')
            ->count();
        $findings[] = ['txn/order business mismatch', $crossTenant];

        // 8. Unbalanced transfer pairs.
        $badTransfers = DB::query()->fromSub(
            DB::table('transactions')
                ->where('reference', 'like', 'TRANSFER-%')->whereNull('deleted_at')
                ->selectRaw("reference, SUM(CASE WHEN type='income' THEN amount ELSE 0 END) i, SUM(CASE WHEN type='expense' THEN amount ELSE 0 END) e")
                ->groupBy('reference'),
            'tr'
        )->whereRaw('ABS(i - e) > 0.01')->count();
        $findings[] = ['unbalanced transfer refs', $badTransfers];

        // 9. Products whose stock ≠ the stock-adjustment ledger (informational —
        //    opening stock and external syncs are not always logged).
        $stockMismatch = DB::query()->fromSub(
            $scoped(DB::table('products as p'))
                ->leftJoin('stock_adjustments as s', 's.product_id', '=', 'p.id')
                ->whereNull('p.deleted_at')
                ->selectRaw("p.id, p.stock, COALESCE(SUM(CASE WHEN s.type IN ('increase','return') THEN s.quantity ELSE -s.quantity END),0) derived")
                ->groupBy('p.id', 'p.stock'),
            'st'
        )->whereRaw('ABS(stock - derived) > 0.01')->count();
        $findings[] = ['products stock ≠ adjustment ledger (info)', $stockMismatch];

        // 10. Purchase orders whose paid_amount ≠ the supplier-payment transactions.
        $poBad = 0;
        $poQuery = DB::table('purchase_orders')->select('id', 'paid_amount');
        if ($business) {
            $poQuery->where('business_id', $business);
        }
        foreach ($poQuery->get() as $po) {
            $paid = (float) DB::table('transactions')
                ->where('reference', 'PO-PAY-' . $po->id)->where('type', 'expense')->whereNull('deleted_at')
                ->sum('amount');
            if (abs($paid - (float) $po->paid_amount) > 0.01) {
                $poBad++;
            }
        }
        $findings[] = ['purchase order paid ≠ PO-PAY txns (info)', $poBad];

        // 11. Employee payments with no live linked cash-book transaction.
        $empBad = DB::table('employee_payments as ep')
            ->leftJoin('transactions as t', 't.id', '=', 'ep.transaction_id')
            ->when($business, fn ($q) => $q->where('ep.business_id', $business))
            ->where(function ($q) {
                $q->whereNull('ep.transaction_id')
                  ->orWhereNull('t.id')
                  ->orWhereNotNull('t.deleted_at');
            })
            ->count();
        $findings[] = ['employee payments missing live txn (info)', $empBad];

        // 12. Material purchases whose MAT- expense transaction is missing.
        $matBad = 0;
        $matQuery = DB::table('material_purchases')->select('id');
        if ($business) {
            $matQuery->where('business_id', $business);
        }
        foreach ($matQuery->get() as $m) {
            $exists = DB::table('transactions')
                ->where('reference', 'MAT-' . $m->id)->where('type', 'expense')->whereNull('deleted_at')
                ->exists();
            if (!$exists) {
                $matBad++;
            }
        }
        $findings[] = ['material purchases missing MAT- txn (info)', $matBad];

        // 13. Soft-deleted orders that still carry live transactions.
        $softdel = $scoped(DB::table('orders as o'))
            ->whereNotNull('o.deleted_at')
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('transactions as t')
                ->whereColumn('t.order_id', 'o.id')->whereNull('t.deleted_at'))
            ->count();
        $findings[] = ['soft-deleted orders with live txns', $softdel];

        // 14. Finalized payroll snapshots that no longer match the live ledger (i.e.
        //     payments were added/changed after the period was signed off).
        $snapshotBad = 0;
        $periodQuery = DB::table('payroll_periods')->where('is_finalized', true);
        if ($business) {
            $periodQuery->where('business_id', $business);
        }
        foreach ($periodQuery->get(['employee_id', 'period_month', 'net_due']) as $period) {
            $employee = \App\Models\Employee::find($period->employee_id);
            if (!$employee) {
                continue;
            }
            $from = \Carbon\Carbon::parse($period->period_month . '-01')->startOfMonth();
            $ledger = $employee->payrollLedger($from, $from->copy()->endOfMonth());
            if (abs((float) $ledger['closing'] - (float) $period->net_due) > 0.01) {
                $snapshotBad++;
            }
        }
        $findings[] = ['finalized payroll ≠ live ledger (info)', $snapshotBad];

        $this->table(
            ['check', 'affected rows'],
            array_map(fn ($f) => [$f[0], number_format($f[1])], $findings)
        );

        if ($this->option('csv') !== null) {
            $path = $this->option('csv') ?: storage_path('app/reconciliation/financial-audit-' . now()->format('Ymd-His') . '.csv');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $fh = fopen($path, 'w');
            fputcsv($fh, ['check', 'affected_rows']);
            foreach ($findings as $f) {
                fputcsv($fh, $f);
            }
            fclose($fh);
            $this->line("Findings written to: {$path}");
        }

        $this->line('Read-only report — nothing was modified.');
        return self::SUCCESS;
    }
}
