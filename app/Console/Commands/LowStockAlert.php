<?php

namespace App\Console\Commands;

use App\Mail\LowStockAlert as LowStockAlertMail;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class LowStockAlert extends Command
{
    protected $signature   = 'erp:low-stock-alert';
    protected $description = 'Send email alerts for products that are low or out of stock';

    public function handle(): void
    {
        $businesses = Business::all();

        foreach ($businesses as $biz) {
            $lowStock = Product::where('business_id', $biz->id)
                ->where('track_stock', true)
                ->where('is_active', true)
                ->where('stock', '>', 0)
                ->whereColumn('stock', '<=', 'low_stock_alert')
                ->select('id', 'name', 'sku', 'stock', 'low_stock_alert')
                ->get();

            $outOfStock = Product::where('business_id', $biz->id)
                ->where('track_stock', true)
                ->where('is_active', true)
                ->where('stock', '<=', 0)
                ->select('id', 'name', 'sku', 'stock')
                ->get();

            if ($lowStock->isEmpty() && $outOfStock->isEmpty()) {
                continue;
            }

            Log::channel('stack')->info("Low stock alert for [{$biz->name}]", [
                'low_stock_count'    => $lowStock->count(),
                'out_of_stock_count' => $outOfStock->count(),
            ]);

            $this->line("Business: {$biz->name}");
            if ($lowStock->isNotEmpty()) {
                $this->warn("  Low stock ({$lowStock->count()} products):");
                foreach ($lowStock as $p) {
                    $this->line("    - {$p->name} [{$p->sku}]: {$p->stock} pcs (alert at {$p->low_stock_alert})");
                }
            }
            if ($outOfStock->isNotEmpty()) {
                $this->error("  Out of stock ({$outOfStock->count()} products):");
                foreach ($outOfStock as $p) {
                    $this->line("    - {$p->name} [{$p->sku}]");
                }
            }

            // Send email to business owner if mail is configured
            if (config('mail.mailer') !== 'log' && config('mail.username')) {
                $ownerEmail = User::where('business_id', $biz->id)
                    ->where('role', 'owner')
                    ->value('email');

                if ($ownerEmail) {
                    try {
                        Mail::to($ownerEmail)->send(
                            new LowStockAlertMail($biz->name, $lowStock, $outOfStock)
                        );
                        $this->line("  → Alert email sent to {$ownerEmail}");
                    } catch (\Throwable $e) {
                        Log::error('Low stock alert email failed: ' . $e->getMessage());
                        $this->warn("  → Email failed: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info('Low stock alert check complete.');
    }
}
