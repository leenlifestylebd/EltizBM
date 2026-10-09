<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Read-only stock reconciliation report.
 *
 * Compares each product's stored `stock` against the net of its stock_adjustments
 * rows. It only SELECTs — nothing is written or changed — so it is safe to run on any
 * environment. A mismatch is NOT proof of a bug: opening stock, WooCommerce syncs and
 * older movements were not always logged to the ledger. Treat the output as a review
 * list, not something to auto-correct.
 */
class StockLedgerAudit extends Command
{
    protected $signature = 'erp:stock-ledger-audit {--business= : limit to a single business id} {--top=20 : how many mismatched products to list} {--csv= : also write the mismatches to a CSV file (path optional)}';

    protected $description = 'Read-only report comparing products.stock with the stock movement ledger. Writes nothing.';

    public function handle(): int
    {
        $business = $this->option('business');
        $top = max(0, (int) $this->option('top'));

        $rows = DB::query()->fromSub(
            DB::table('products as p')
                ->leftJoin('stock_adjustments as s', 's.product_id', '=', 'p.id')
                ->whereNull('p.deleted_at')
                ->when($business, fn ($q) => $q->where('p.business_id', $business))
                ->selectRaw("p.id, p.business_id, p.name, p.stock,
                    COALESCE(SUM(CASE WHEN s.type IN ('increase','return') THEN s.quantity ELSE -s.quantity END),0) AS derived,
                    COUNT(s.id) AS movements")
                ->groupBy('p.id', 'p.business_id', 'p.name', 'p.stock'),
            'x'
        )->selectRaw('id, business_id, name, stock, derived, movements, ABS(stock - derived) AS diff')
         ->get();

        $total = $rows->count();
        $mismatched = $rows->filter(fn ($r) => abs((float) $r->stock - (float) $r->derived) > 0.01);
        $sumAbs = $mismatched->sum(fn ($r) => abs((float) $r->stock - (float) $r->derived));

        $this->table(['metric', 'value'], [
            ['products checked', number_format($total)],
            ['products with a stock/ledger mismatch', number_format($mismatched->count())],
            ['sum of absolute differences', number_format($sumAbs, 2)],
        ]);

        if ($top > 0 && $mismatched->isNotEmpty()) {
            $this->line('');
            $this->table(
                ['product', 'business', 'stock', 'ledger net', 'diff', 'movements'],
                $mismatched->sortByDesc(fn ($r) => abs((float) $r->stock - (float) $r->derived))->take($top)
                    ->map(fn ($r) => [
                        $r->id . ' ' . \Illuminate\Support\Str::limit((string) $r->name, 30),
                        $r->business_id,
                        number_format((float) $r->stock),
                        number_format((float) $r->derived),
                        number_format(abs((float) $r->stock - (float) $r->derived)),
                        $r->movements,
                    ])->values()->all()
            );
        }

        if ($this->option('csv') !== null) {
            $path = $this->option('csv') ?: storage_path('app/reconciliation/stock-ledger-audit-' . now()->format('Ymd-His') . '.csv');
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $fh = fopen($path, 'w');
            fputcsv($fh, ['product_id', 'business_id', 'name', 'stock', 'ledger_net', 'difference', 'movements']);
            foreach ($mismatched as $r) {
                fputcsv($fh, [
                    $r->id, $r->business_id, $r->name, $r->stock, $r->derived,
                    abs((float) $r->stock - (float) $r->derived), $r->movements,
                ]);
            }
            fclose($fh);
            $this->line("Mismatches written to: {$path}");
        }

        $this->line('Read-only report — nothing was modified. Mismatches may be explained by opening stock / external syncs.');
        return self::SUCCESS;
    }
}
