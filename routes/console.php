<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
| To activate on Windows: Add a Windows Task Scheduler entry to run
|   C:\xampp\php83\php.exe C:\laragon\www\EltizBM\artisan schedule:run
| every minute.
|
| On Linux/cPanel: Add to crontab:
|   * * * * * cd /path/to/EltizBM && php artisan schedule:run >> /dev/null 2>&1
*/

// Sync WooCommerce orders every 15 minutes for all active businesses
Schedule::command('woo:sync --type=orders')->everyFifteenMinutes()
    ->withoutOverlapping()
    ->name('woo-order-sync')
    ->onFailure(fn() => \Illuminate\Support\Facades\Log::error('WooCommerce order sync failed'));

// Sync WooCommerce stock every 30 minutes
Schedule::command('woo:sync --type=stock')->everyThirtyMinutes()
    ->withoutOverlapping()
    ->name('woo-stock-sync');

// Low stock alert — check every morning at 8 AM
Schedule::command('erp:low-stock-alert')->dailyAt('08:00')
    ->name('low-stock-alert');

// Clean up expired sessions/cache daily at midnight
Schedule::command('cache:prune-stale-tags')->daily();

// Process queued jobs without a persistent worker daemon. On shared hosting (Hostinger)
// there is no supervisor to keep `queue:work` running, so the scheduler drains the
// queue once a minute. Covers the POS->WooCommerce stock push (dispatched on 'low').
Schedule::command('queue:work --queue=default,low --stop-when-empty --max-time=55 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->name('process-queue');
