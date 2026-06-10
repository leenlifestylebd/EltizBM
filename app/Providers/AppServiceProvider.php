<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\MaterialPurchase;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\Transaction;
use App\Observers\AuditObserver;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StockService::class);
        $this->app->singleton(OrderService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register audit observer on all key models
        // This activates the ActivityLog for all create/update/delete on these models
        Order::observe(AuditObserver::class);
        Transaction::observe(AuditObserver::class);
        Employee::observe(AuditObserver::class);
        Product::observe(AuditObserver::class);
        MaterialPurchase::observe(AuditObserver::class);
        ProductionEntry::observe(AuditObserver::class);
        EmployeePayment::observe(AuditObserver::class);
        Customer::observe(AuditObserver::class);
    }
}
