<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\CourierController;
use App\Http\Controllers\PeopleController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\WooCommerceController;
use App\Http\Controllers\WebOrderController;
use App\Http\Controllers\SteadfastController;
use App\Http\Controllers\ProductionTaskController;
use App\Http\Controllers\BomController;
use App\Http\Controllers\TailoringController;

// =============================================
// AUTH ROUTES
// =============================================
Route::middleware('guest')->group(function () {
    Route::get('/login',    [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login',   [AuthController::class, 'login']);
    // Worker login (phone + PIN) — low-hassle entry for factory workers
    Route::get('/worker-login',  [AuthController::class, 'showWorkerLogin'])->name('worker.login');
    Route::post('/worker-login', [AuthController::class, 'workerLogin']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register',[AuthController::class, 'register']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password',[AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password',        [AuthController::class, 'resetPassword'])->name('password.update');
});

// ── Google OAuth (no guest middleware — callback must be open) ──
Route::get('/auth/google',          [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

// ── PWA ──
Route::get('/offline', fn() => view('pwa.offline'))->name('pwa.offline');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// =============================================
// SUPER ADMIN GATE (secret URL login)
// =============================================
Route::get('/sa/{secret}',  [\App\Http\Controllers\SuperAdminGateController::class, 'show'])->name('super-admin.gate');
Route::post('/sa/{secret}', [\App\Http\Controllers\SuperAdminGateController::class, 'login'])->name('super-admin.gate.login');

// =============================================
// SUPER ADMIN ROUTES (selected Gmail only)
// =============================================
Route::middleware(['auth', 'superadmin'])->prefix('super-admin')->name('super-admin.')->group(function () {
    Route::get('/',                                      [\App\Http\Controllers\SuperAdminController::class, 'index'])->name('index');
    Route::get('/businesses',                            [\App\Http\Controllers\SuperAdminController::class, 'businesses'])->name('businesses');
    Route::post('/businesses',                           [\App\Http\Controllers\SuperAdminController::class, 'storeBusiness'])->name('businesses.store');
    Route::post('/businesses/{business}/toggle',         [\App\Http\Controllers\SuperAdminController::class, 'toggleBusiness'])->name('businesses.toggle');
    Route::post('/businesses/{business}/extend-trial',   [\App\Http\Controllers\SuperAdminController::class, 'extendTrial'])->name('businesses.extend-trial');
    Route::delete('/businesses/{business}',              [\App\Http\Controllers\SuperAdminController::class, 'destroyBusiness'])->name('businesses.destroy');
    Route::post('/businesses/{business}/reset-data',     [\App\Http\Controllers\SuperAdminController::class, 'resetData'])->name('businesses.reset-data');
    Route::get('/users',                                 [\App\Http\Controllers\SuperAdminController::class, 'users'])->name('users');
    Route::post('/users/{user}/approve',                 [\App\Http\Controllers\SuperAdminController::class, 'approveUser'])->name('users.approve');
    Route::delete('/users/{user}/reject',                [\App\Http\Controllers\SuperAdminController::class, 'rejectUser'])->name('users.reject');
    Route::post('/users/{user}/reset-password',          [\App\Http\Controllers\SuperAdminController::class, 'resetPassword'])->name('users.reset-password');
});

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
        Route::get('/export',      [OrderController::class, 'export'])->name('export');
        Route::get('/{order}',     [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit',[OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}',     [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}',  [OrderController::class, 'destroy'])->name('destroy');
        Route::post('/{order}/status', [OrderController::class, 'updateStatus'])->name('status');
        Route::post('/{order}/partial-return', [OrderController::class, 'partialReturn'])->name('partial-return');
        Route::get('/{order}/exchange',  [OrderController::class, 'exchangeForm'])->name('exchange');
        Route::post('/{order}/exchange', [OrderController::class, 'exchangeStore'])->name('exchange.store');
        Route::get('/{order}/invoice',          [OrderController::class, 'invoice'])->name('invoice');
        Route::get('/{order}/invoice/pdf',      [OrderController::class, 'downloadPdf'])->name('invoice.pdf');
        Route::get('/{order}/invoice/receipt',  [OrderController::class, 'receipt'])->name('receipt');
        Route::get('/{order}/print',            [OrderController::class, 'printInvoice'])->name('print');
    });

    // ---- WHOLESALE (reuses OrderController, filtered to source=wholesale) ----
    Route::prefix('wholesale')->name('wholesale.')->group(function () {
        Route::get('/',        [OrderController::class, 'index'])->name('index');
        Route::get('/create',  [OrderController::class, 'create'])->name('create');
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
        Route::get('/export',          [ProductController::class, 'export'])->name('export');
        Route::get('/import',          [ProductController::class, 'importPage'])->name('import');
        Route::get('/import/template', [ProductController::class, 'downloadTemplate'])->name('import.template');
        Route::post('/import',         [ProductController::class, 'importCsv'])->name('import.process');
        Route::get('/sync',        [ProductController::class, 'syncPage'])->name('sync');
        Route::post('/sync',       [ProductController::class, 'doSync'])->name('do-sync');
        Route::get('/{product}',     [ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit',[ProductController::class, 'edit'])->name('edit');
        Route::get('/{product}/quick-edit',[ProductController::class, 'quickEdit'])->name('quick-edit');
        Route::put('/{product}',     [ProductController::class, 'update'])->name('update');
        Route::delete('/{product}',  [ProductController::class, 'destroy'])->name('destroy');
        Route::post('/{product}/generate-barcode', [ProductController::class, 'generateBarcode'])->name('generate-barcode');
        Route::post('/generate-barcodes-bulk',     [ProductController::class, 'generateBarcodesBulk'])->name('generate-barcodes-bulk');
        // Bill of Materials
        Route::post('/{product}/bom',              [BomController::class, 'store'])->name('bom.store');
        Route::delete('/{product}/bom/{bom}',      [BomController::class, 'destroy'])->name('bom.destroy');
        Route::patch('/{product}/bom/{bom}/toggle',[BomController::class, 'toggle'])->name('bom.toggle');
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
        Route::get('/',                                   [\App\Http\Controllers\ReturnController::class, 'index'])->name('index');
        Route::post('/orders/{order}/request',            [\App\Http\Controllers\ReturnController::class, 'requestReturn'])->name('request');
        Route::post('/orders/{order}/approve',            [\App\Http\Controllers\ReturnController::class, 'approve'])->name('approve');
        Route::post('/orders/{order}/receive',            [\App\Http\Controllers\ReturnController::class, 'receive'])->name('receive');
        Route::post('/orders/{order}/refund',             [\App\Http\Controllers\ReturnController::class, 'refund'])->name('refund');
        Route::post('/orders/{order}/reject',             [\App\Http\Controllers\ReturnController::class, 'reject'])->name('reject');
    });

    // ---- INVENTORY ----
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/overview',         [InventoryController::class, 'overview'])->name('overview');
        Route::get('/purchase-orders',  [InventoryController::class, 'purchaseOrders'])->name('purchase-orders');
        Route::post('/purchase-orders', [InventoryController::class, 'storePurchaseOrder'])->name('purchase-orders.store');
        Route::get('/stock-levels',     [InventoryController::class, 'stockLevels'])->name('stock-levels');
        Route::get('/export-stock',     [InventoryController::class, 'exportStock'])->name('export-stock');
        Route::get('/cutting-list',        [InventoryController::class, 'cuttingList'])->name('cutting-list');
        Route::get('/cutting-list/export', [InventoryController::class, 'exportCuttingList'])->name('cutting-list.export');
        Route::post('/cutting-list/send',  [InventoryController::class, 'sendToCutting'])->name('cutting-list.send');
        Route::get('/adjustments',      [InventoryController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjustments',     [InventoryController::class, 'storeAdjustment'])->name('adjustments.store');
        Route::post('/quick-adjust',    [InventoryController::class, 'quickAdjust'])->name('quick-adjust');
        Route::get('/barcode',          [InventoryController::class, 'barcode'])->name('barcode');
        Route::post('/barcode/print',   [InventoryController::class, 'printBarcode'])->name('barcode.print');
        Route::get('/transfers',        [InventoryController::class, 'transfers'])->name('transfers');
        Route::post('/transfers',       [InventoryController::class, 'storeTransfer'])->name('transfers.store');
        Route::get('/warehouse-stock',  [InventoryController::class, 'warehouseStock'])->name('warehouse-stock');
    });

    // ---- ACCOUNTS ----
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/overview',          [AccountController::class, 'overview'])->name('overview');
        Route::get('/income',            [AccountController::class, 'income'])->name('income');
        Route::post('/income',           [AccountController::class, 'storeIncome'])->name('income.store');
        Route::get('/expenses',          [AccountController::class, 'expenses'])->name('expenses');
        Route::post('/expenses',         [AccountController::class, 'storeExpense'])->name('expenses.store');
        // Salary moved to Production > Payroll: that flow records the employee ledger
        // entry AND the expense transaction together, so the cash book and worker
        // ledgers always agree. Old bookmarks land on payroll instead of a 404.
        Route::get('/salary', fn() => redirect()->route('production.payroll'))->name('salary');
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
        Route::get('/account-balance',       [ReportController::class, 'accountBalance'])->name('account-balance');
        Route::get('/employee-production',   [ReportController::class, 'employeeProduction'])->name('employee-production');
        Route::get('/sales-channel',         [ReportController::class, 'salesChannel'])->name('sales-channel');

        // Export routes
        Route::get('/export/{type}', [ReportExportController::class, 'export'])->name('export');
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
        Route::post('/{employee}/set-pin',    [EmployeeController::class, 'setPin'])->name('set-pin');
    });

    Route::get('/attendance', [EmployeeController::class, 'attendanceOverview'])->name('attendance.index');

    // ---- PRODUCTION SYSTEM ----
    Route::prefix('production')->name('production.')->group(function () {
        Route::get('/admin-overview',  [App\Http\Controllers\ProductionController::class, 'adminOverview'])->name('admin-overview');
        Route::get('/rates',           [App\Http\Controllers\ProductionController::class, 'rates'])->name('rates');
        Route::post('/rates',          [App\Http\Controllers\ProductionController::class, 'storeRate'])->name('rates.store');
        Route::put('/rates/{rate}',    [App\Http\Controllers\ProductionController::class, 'updateRate'])->name('rates.update');
        Route::delete('/rates/{rate}', [App\Http\Controllers\ProductionController::class, 'destroyRate'])->name('rates.destroy');
        Route::get('/payroll',                           [App\Http\Controllers\ProductionController::class, 'payroll'])->name('payroll');
        Route::post('/payment',                          [App\Http\Controllers\ProductionController::class, 'storePayment'])->name('payment.store');
        Route::delete('/payment/{payment}',              [App\Http\Controllers\ProductionController::class, 'destroyPayment'])->name('payment.destroy');
        Route::post('/payroll/{employee}/finalize',      [App\Http\Controllers\ProductionController::class, 'finalizePayroll'])->name('payroll.finalize');
        Route::post('/payroll/{payrollPeriod}/unfinalize',[App\Http\Controllers\ProductionController::class, 'unfinalizePayroll'])->name('payroll.unfinalize');
        Route::get('/my-dashboard',    [App\Http\Controllers\ProductionController::class, 'workerDashboard'])->name('worker.dashboard');
        Route::post('/notifications/mark-read', [App\Http\Controllers\ProductionController::class, 'markNotificationsRead'])->name('notifications.read');

        // ---- WORKER EFFICIENCY REPORT ----
        Route::get('/efficiency',  [App\Http\Controllers\ProductionController::class, 'efficiency'])->name('efficiency');

        // ---- WORKER LEDGER ----
        Route::get('/worker-ledger',                              [App\Http\Controllers\ProductionController::class, 'workerLedger'])->name('worker.ledger');
        Route::get('/worker-ledger/{employee}',                   [App\Http\Controllers\ProductionController::class, 'workerLedgerDetail'])->name('worker.ledger.detail');
        Route::get('/worker-ledger/{employee}/export',            [App\Http\Controllers\ProductionController::class, 'workerLedgerExport'])->name('worker.ledger.export');

        // ---- PRODUCTION TASKS ----
        Route::get('/tasks',                  [ProductionTaskController::class, 'index'])->name('tasks.index');
        Route::get('/tasks/create',           [ProductionTaskController::class, 'create'])->name('tasks.create');
        Route::post('/tasks',                 [ProductionTaskController::class, 'store'])->name('tasks.store');
        Route::get('/tasks/{task}',           [ProductionTaskController::class, 'show'])->name('tasks.show');
        Route::put('/tasks/{task}',           [ProductionTaskController::class, 'update'])->name('tasks.update');
        Route::delete('/tasks/{task}',        [ProductionTaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('/tasks/{task}/submit',   [ProductionTaskController::class, 'submit'])->name('tasks.submit');
        Route::post('/tasks/{task}/forward',  [ProductionTaskController::class, 'forward'])->name('tasks.forward');
        Route::put('/tasks/submissions/{submission}',    [ProductionTaskController::class, 'updateSubmission'])->name('tasks.submission.update');
        Route::delete('/tasks/submissions/{submission}', [ProductionTaskController::class, 'deleteSubmission'])->name('tasks.submission.delete');
        Route::get('/pipeline',               [ProductionTaskController::class, 'pipeline'])->name('pipeline');
        Route::get('/my-tasks',               [ProductionTaskController::class, 'myTasks'])->name('tasks.my');
    });

    // ---- MATERIALS ----
    Route::prefix('materials')->name('materials.')->group(function () {
        Route::get('/',               [MaterialController::class, 'index'])->name('index');
        Route::post('/',              [MaterialController::class, 'store'])->name('store');
        Route::delete('/{material}',  [MaterialController::class, 'destroy'])->name('destroy');
        Route::get('/export',         [MaterialController::class, 'export'])->name('export');
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
        Route::get('/payment-history',[SettingsController::class, 'paymentHistory'])->name('payment-history');
        Route::get('/user-management',[SettingsController::class, 'userManagement'])->name('user-management');
        Route::post('/users',         [SettingsController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}',   [SettingsController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}',[SettingsController::class, 'destroyUser'])->name('users.destroy');
        Route::post('/users/{user}/approve',[SettingsController::class, 'approveUser'])->name('users.approve');
        Route::delete('/users/{user}/reject',[SettingsController::class, 'rejectUser'])->name('users.reject');
        Route::get('/statuses',       [SettingsController::class, 'statuses'])->name('statuses');
        Route::post('/statuses',      [SettingsController::class, 'updateStatuses'])->name('statuses.update');
        Route::get('/warehouses',                          [SettingsController::class, 'warehouses'])->name('warehouses');
        Route::post('/warehouses',                         [SettingsController::class, 'storeWarehouse'])->name('warehouses.store');
        Route::put('/warehouses/{warehouse}',              [SettingsController::class, 'updateWarehouse'])->name('warehouses.update');
        Route::delete('/warehouses/{warehouse}',           [SettingsController::class, 'destroyWarehouse'])->name('warehouses.destroy');
        Route::get('/tags',           [SettingsController::class, 'tags'])->name('tags');
        Route::post('/tags',          [SettingsController::class, 'storeTag'])->name('tags.store');
        Route::delete('/tags',        [SettingsController::class, 'destroyTag'])->name('tags.destroy');
        Route::get('/print-templates',  [SettingsController::class, 'printTemplates'])->name('print-templates');
        Route::post('/print-templates', [SettingsController::class, 'updatePrintTemplates'])->name('print-templates.update');

        // Account Categories
        Route::get('/account-categories',          [SettingsController::class, 'accountCategories'])->name('account-categories');
        Route::post('/account-categories',         [SettingsController::class, 'storeAccountCategory'])->name('account-categories.store');
        Route::delete('/account-categories/{cat}', [SettingsController::class, 'destroyAccountCategory'])->name('account-categories.destroy');

        // Theme
        Route::get('/theme',  [SettingsController::class, 'theme'])->name('theme');
        Route::post('/theme', [SettingsController::class, 'updateTheme'])->name('theme.update');
        Route::post('/theme/reset', [SettingsController::class, 'resetTheme'])->name('theme.reset');
    });

    // ---- INTEGRATIONS ----
    // Payment Gateways
    Route::post('/payment/settings',           [\App\Http\Controllers\PaymentController::class, 'saveSettings'])->name('payment.settings.save');
    Route::post('/payment/disable',            [\App\Http\Controllers\PaymentController::class, 'disableGateway'])->name('payment.disable');
    Route::post('/payment/bkash/{order}',      [\App\Http\Controllers\PaymentController::class, 'bkashCreate'])->name('payment.bkash.create');
    Route::get('/payment/bkash/{order}/callback', [\App\Http\Controllers\PaymentController::class, 'bkashCallback'])->name('payment.bkash.callback');
    Route::post('/payment/nagad/{order}',      [\App\Http\Controllers\PaymentController::class, 'nagadCreate'])->name('payment.nagad.create');
    Route::get('/payment/nagad/{order}/callback', [\App\Http\Controllers\PaymentController::class, 'nagadCallback'])->name('payment.nagad.callback');

    Route::prefix('integrations')->name('integrations.')->group(function () {
        Route::get('/',                   [IntegrationController::class, 'index'])->name('index');
        Route::get('/woocommerce',        [WooCommerceController::class, 'settings'])->name('woocommerce');
        Route::get('/payment-gateways',   [IntegrationController::class, 'paymentGateways'])->name('payment-gateways');
        Route::post('/woocommerce',       [WooCommerceController::class, 'saveSettings'])->name('woocommerce.save');
        Route::post('/woocommerce/test',  [WooCommerceController::class, 'testConnection'])->name('woocommerce.test');
        Route::post('/woocommerce/sync',  [WooCommerceController::class, 'syncAll'])->name('woocommerce.sync');
        Route::get('/courier-providers',  [IntegrationController::class, 'courierProviders'])->name('courier-providers');
        Route::post('/courier-providers', [IntegrationController::class, 'saveCourierProvider'])->name('courier-providers.save');
        Route::post('/courier-providers/disconnect', [IntegrationController::class, 'disconnectCourierProvider'])->name('courier-providers.disconnect');
        Route::get('/sms',                [IntegrationController::class, 'sms'])->name('sms');
        Route::post('/sms',               [IntegrationController::class, 'saveSms'])->name('sms.save');
        Route::post('/sms/test',          [IntegrationController::class, 'testSms'])->name('sms.test');
    });

    // ---- TAILORING ----
    Route::prefix('tailoring')->name('tailoring.')->group(function () {
        Route::get('/',                                    [TailoringController::class, 'index'])->name('index');
        Route::get('/create',                              [TailoringController::class, 'create'])->name('create');
        Route::post('/',                                   [TailoringController::class, 'store'])->name('store');
        Route::get('/{tailoring}',                         [TailoringController::class, 'show'])->name('show');
        Route::get('/{tailoring}/edit',                    [TailoringController::class, 'edit'])->name('edit');
        Route::put('/{tailoring}',                         [TailoringController::class, 'update'])->name('update');
        Route::delete('/{tailoring}',                      [TailoringController::class, 'destroy'])->name('destroy');
        Route::post('/{tailoring}/advance-stage',          [TailoringController::class, 'advanceStage'])->name('advance-stage');
        Route::post('/{tailoring}/set-stage',              [TailoringController::class, 'setStage'])->name('set-stage');
        Route::post('/{tailoring}/assign-worker',          [TailoringController::class, 'assignWorker'])->name('assign-worker');
        Route::post('/{tailoring}/receive-payment',        [TailoringController::class, 'receivePayment'])->name('receive-payment');
        Route::get('/{tailoring}/slip',                    [TailoringController::class, 'slip'])->name('slip');
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
        Route::get('/check-phone',             [SteadfastController::class, 'checkPhone'])->name('check-phone');
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