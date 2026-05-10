<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\WooCommerceController;
use App\Http\Controllers\WebOrderController;
use App\Http\Controllers\SteadfastController;

// =============================================
// AUTH ROUTES
// =============================================
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password',[AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',        [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// =============================================
// AUTHENTICATED ROUTES
// =============================================
Route::middleware(['auth', 'business'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ---- ORDERS ----
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/',            [OrderController::class, 'index'])->name('index');
        Route::get('/create',      [OrderController::class, 'create'])->name('create');
        Route::post('/',           [OrderController::class, 'store'])->name('store');
        Route::get('/{order}',     [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit',[OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}',     [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}',  [OrderController::class, 'destroy'])->name('destroy');
        Route::post('/{order}/status', [OrderController::class, 'updateStatus'])->name('status');
        Route::get('/{order}/invoice', [OrderController::class, 'invoice'])->name('invoice');
        Route::get('/{order}/print',   [OrderController::class, 'printInvoice'])->name('print');
    });

    // ---- WEB ORDERS (WooCommerce) ----
    Route::prefix('web-orders')->name('web-orders.')->group(function () {
        Route::get('/',                [WebOrderController::class, 'index'])->name('index');
        Route::get('/{order}',         [WebOrderController::class, 'show'])->name('show');
        Route::post('/sync',           [WebOrderController::class, 'sync'])->name('sync');
        Route::post('/{order}/import', [WebOrderController::class, 'import'])->name('import');
    });

    // ---- PRODUCTS ----
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/',            [ProductController::class, 'index'])->name('index');
        Route::get('/create',      [ProductController::class, 'create'])->name('create');
        Route::post('/',           [ProductController::class, 'store'])->name('store');
        Route::get('/sync',        [ProductController::class, 'syncPage'])->name('sync');
        Route::post('/sync',       [ProductController::class, 'doSync'])->name('do-sync');
        Route::get('/{product}',     [ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit',[ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}',     [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}',  [ProductController::class, 'destroy'])->name('destroy');
    });

    Route::resource('categories', CategoryController::class);
    Route::resource('brands',     BrandController::class);
    Route::resource('attributes', AttributeController::class);

    // ---- COURIER & RETURNS ----
    Route::prefix('courier')->name('courier.')->group(function () {
        Route::get('/',        [CourierController::class, 'index'])->name('index');
        Route::get('/create',  [CourierController::class, 'create'])->name('create');
        Route::post('/',       [CourierController::class, 'store'])->name('store');
        Route::get('/{shipment}',       [CourierController::class, 'show'])->name('show');
        Route::put('/{shipment}',       [CourierController::class, 'update'])->name('update');
        Route::post('/{shipment}/track',[CourierController::class, 'track'])->name('track');
    });

    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/', [CourierController::class, 'returns'])->name('index');
    });

    // ---- INVENTORY ----
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/overview',         [InventoryController::class, 'overview'])->name('overview');
        Route::get('/purchase-orders',  [InventoryController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::post('/purchase-orders', [InventoryController::class, 'storePurchaseOrder'])->name('purchase-orders.store');
        Route::get('/stock-levels',     [InventoryController::class, 'stockLevels'])->name('stock-levels');
        Route::get('/adjustments',      [InventoryController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjustments',     [InventoryController::class, 'storeAdjustment'])->name('adjustments.store');
        Route::get('/barcode',          [InventoryController::class, 'barcode'])->name('barcode');
        Route::post('/barcode/print',   [InventoryController::class, 'printBarcode'])->name('barcode.print');
        Route::get('/transfers',        [InventoryController::class, 'transfers'])->name('transfers');
        Route::post('/transfers',       [InventoryController::class, 'storeTransfer'])->name('transfers.store');
    });

    // ---- ACCOUNTS ----
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/overview',          [AccountController::class, 'overview'])->name('overview');
        Route::get('/income',            [AccountController::class, 'income'])->name('income');
        Route::post('/income',           [AccountController::class, 'storeIncome'])->name('income.store');
        Route::get('/expenses',          [AccountController::class, 'expenses'])->name('expenses');
        Route::post('/expenses',         [AccountController::class, 'storeExpense'])->name('expenses.store');
        Route::get('/salary',            [AccountController::class, 'salary'])->name('salary');
        Route::post('/salary',           [AccountController::class, 'storeSalary'])->name('salary.store');
        Route::get('/payment-transfer',  [AccountController::class, 'paymentTransfer'])->name('payment-transfer');
        Route::post('/payment-transfer', [AccountController::class, 'storeTransfer'])->name('payment-transfer.store');
        Route::delete('/transactions/{transaction}', [AccountController::class, 'destroyTransaction'])->name('transactions.destroy');
    });

    // ---- REPORTS ----
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/all',               [ReportController::class, 'all'])->name('all');
        Route::get('/profit-loss',       [ReportController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/expense-category',  [ReportController::class, 'expenseCategory'])->name('expense-category');
        Route::get('/income-source',     [ReportController::class, 'incomeSource'])->name('income-source');
        Route::get('/order-analytics',   [ReportController::class, 'orderAnalytics'])->name('order-analytics');
        Route::get('/customer-analytics',[ReportController::class, 'customerAnalytics'])->name('customer-analytics');
        Route::get('/stock-inventory',   [ReportController::class, 'stockInventory'])->name('stock-inventory');
        Route::get('/business-overview', [ReportController::class, 'businessOverview'])->name('business-overview');
        Route::get('/customer-due',      [ReportController::class, 'customerDue'])->name('customer-due');
        Route::get('/supplier-due',      [ReportController::class, 'supplierDue'])->name('supplier-due');
        Route::get('/return-refund',     [ReportController::class, 'returnRefund'])->name('return-refund');
        Route::get('/purchase-orders',   [ReportController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::get('/account-balance',   [ReportController::class, 'accountBalance'])->name('account-balance');
    });

    // ---- PEOPLE ----
    Route::prefix('people')->name('people.')->group(function () {
        Route::get('/customers',               [PeopleController::class, 'customers'])->name('customers');
        Route::post('/customers',              [PeopleController::class, 'storeCustomer'])->name('customers.store');
        Route::get('/customers/{customer}',    [PeopleController::class, 'showCustomer'])->name('customers.show');
        Route::put('/customers/{customer}',    [PeopleController::class, 'updateCustomer'])->name('customers.update');
        Route::delete('/customers/{customer}', [PeopleController::class, 'destroyCustomer'])->name('customers.destroy');

        Route::get('/vendors',             [PeopleController::class, 'vendors'])->name('vendors');
        Route::post('/vendors',            [PeopleController::class, 'storeVendor'])->name('vendors.store');
        Route::get('/vendors/{vendor}',    [PeopleController::class, 'showVendor'])->name('vendors.show');
        Route::put('/vendors/{vendor}',    [PeopleController::class, 'updateVendor'])->name('vendors.update');
        Route::delete('/vendors/{vendor}', [PeopleController::class, 'destroyVendor'])->name('vendors.destroy');
    });

    // ---- EMPLOYEES ----
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/',                       [EmployeeController::class, 'index'])->name('index');
        Route::get('/create',                 [EmployeeController::class, 'create'])->name('create');
        Route::post('/',                      [EmployeeController::class, 'store'])->name('store');
        Route::get('/{employee}',             [EmployeeController::class, 'show'])->name('show');
        Route::get('/{employee}/edit',        [EmployeeController::class, 'edit'])->name('edit');
        Route::put('/{employee}',             [EmployeeController::class, 'update'])->name('update');
        Route::get('/{employee}/attendance',  [EmployeeController::class, 'attendance'])->name('attendance');
        Route::post('/{employee}/attendance', [EmployeeController::class, 'storeAttendance'])->name('attendance.store');
        Route::get('/{employee}/production',  [EmployeeController::class, 'production'])->name('production');
        Route::post('/{employee}/production', [EmployeeController::class, 'storeProduction'])->name('production.store');
    });

    Route::get('/attendance', [EmployeeController::class, 'attendanceOverview'])->name('attendance.index');

    // ---- PRODUCTION SYSTEM ----
    Route::prefix('production')->name('production.')->group(function () {
        Route::get('/',                [App\Http\Controllers\ProductionController::class, 'index'])->name('index');
        Route::post('/',               [App\Http\Controllers\ProductionController::class, 'store'])->name('store');
        Route::delete('/{entry}',      [App\Http\Controllers\ProductionController::class, 'destroy'])->name('destroy');
        Route::get('/rates',           [App\Http\Controllers\ProductionController::class, 'rates'])->name('rates');
        Route::post('/rates',          [App\Http\Controllers\ProductionController::class, 'storeRate'])->name('rates.store');
        Route::put('/rates/{rate}',    [App\Http\Controllers\ProductionController::class, 'updateRate'])->name('rates.update');
        Route::delete('/rates/{rate}', [App\Http\Controllers\ProductionController::class, 'destroyRate'])->name('rates.destroy');
        Route::get('/payroll',         [App\Http\Controllers\ProductionController::class, 'payroll'])->name('payroll');
        Route::post('/payment',        [App\Http\Controllers\ProductionController::class, 'storePayment'])->name('payment.store');
        Route::get('/my-dashboard',    [App\Http\Controllers\ProductionController::class, 'workerDashboard'])->name('worker.dashboard');
    });

    // ---- TASKS ----
    Route::resource('tasks', TaskController::class);

    // ---- POS ----
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/',                [PosController::class, 'index'])->name('index');
        Route::post('/checkout',       [PosController::class, 'checkout'])->name('checkout');
        Route::get('/receipt/{order}', [PosController::class, 'receipt'])->name('receipt');
        Route::get('/search-product',  [PosController::class, 'searchProduct'])->name('search-product');
        Route::get('/scan-barcode',    [PosController::class, 'scanBarcode'])->name('scan-barcode');
    });

    // ---- SETTINGS ----
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/general',        [SettingsController::class, 'general'])->name('general');
        Route::post('/general',       [SettingsController::class, 'updateGeneral'])->name('general.update');
        Route::get('/business',       [SettingsController::class, 'business'])->name('business');
        Route::post('/business',      [SettingsController::class, 'updateBusiness'])->name('business.update');
        Route::post('/logo',          [SettingsController::class, 'uploadLogo'])->name('logo.upload');
        Route::get('/subscription',   [SettingsController::class, 'subscription'])->name('subscription');
        Route::get('/payment-history',[SettingsController::class, 'paymentHistory'])->name('payment-history');
        Route::get('/user-management',[SettingsController::class, 'userManagement'])->name('user-management');
        Route::post('/users',         [SettingsController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}',   [SettingsController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}',[SettingsController::class, 'destroyUser'])->name('users.destroy');
        Route::post('/users/{user}/approve',[SettingsController::class, 'approveUser'])->name('users.approve');
        Route::delete('/users/{user}/reject',[SettingsController::class, 'rejectUser'])->name('users.reject');
        Route::get('/statuses',       [SettingsController::class, 'statuses'])->name('statuses');
        Route::get('/warehouses',     [SettingsController::class, 'warehouses'])->name('warehouses');
        Route::post('/warehouses',    [SettingsController::class, 'storeWarehouse'])->name('warehouses.store');
        Route::get('/tags',           [SettingsController::class, 'tags'])->name('tags');
        Route::get('/print-templates',[SettingsController::class, 'printTemplates'])->name('print-templates');
    });

    // ---- INTEGRATIONS ----
    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/',                   [IntegrationController::class, 'index'])->name('index');
        Route::get('/woocommerce',        [WooCommerceController::class, 'settings'])->name('woocommerce');
        Route::post('/woocommerce',       [WooCommerceController::class, 'saveSettings'])->name('woocommerce.save');
        Route::post('/woocommerce/test',  [WooCommerceController::class, 'testConnection'])->name('woocommerce.test');
        Route::post('/woocommerce/sync',  [WooCommerceController::class, 'syncAll'])->name('woocommerce.sync');
        Route::get('/courier-providers',  [IntegrationController::class, 'courierProviders'])->name('courier-providers');
        Route::post('/courier-providers', [IntegrationController::class, 'saveCourierProvider'])->name('courier-providers.save');
        Route::get('/sms',                [IntegrationController::class, 'sms'])->name('sms');
        Route::post('/sms',               [IntegrationController::class, 'saveSms'])->name('sms.save');
        Route::post('/sms/test',          [IntegrationController::class, 'testSms'])->name('sms.test');
    });

    // ---- STEADFAST ----
    Route::prefix('steadfast')->name('steadfast.')->group(function () {
        Route::get('/',                        [SteadfastController::class, 'index'])->name('index');
        Route::post('/send/{order}',           [SteadfastController::class, 'sendOrder'])->name('send-order');
        Route::post('/bulk-send',              [SteadfastController::class, 'bulkSend'])->name('bulk-send');
        Route::get('/check-status',            [SteadfastController::class, 'checkStatus'])->name('check-status');
        Route::post('/sync-status',            [SteadfastController::class, 'syncStatus'])->name('sync-status');
        Route::get('/balance',                 [SteadfastController::class, 'getBalance'])->name('get-balance');
        Route::get('/label/{shipment}',        [SteadfastController::class, 'printLabel'])->name('label');
        Route::post('/labels',                 [SteadfastController::class, 'printLabels'])->name('labels');
        Route::get('/invoice/{shipment}',      [SteadfastController::class, 'printInvoice'])->name('invoice');
    });

    // ---- API (AJAX) ----
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/products/search',  [ProductController::class, 'apiSearch'])->name('products.search');
        Route::get('/customers/search', [PeopleController::class, 'apiSearch'])->name('customers.search');
        Route::get('/dashboard/stats',  [DashboardController::class, 'apiStats'])->name('dashboard.stats');
    });

});

// ---- WooCommerce Webhook (no auth) ----
Route::post('/webhooks/woocommerce', [WooCommerceController::class, 'webhook'])
    ->name('webhooks.woocommerce')
    ->withoutMiddleware(['web']);

// ---- Steadfast Webhook (no auth) ----
Route::post('/webhook/steadfast', [SteadfastController::class, 'webhook'])
    ->name('steadfast.webhook')
    ->withoutMiddleware(['web']);