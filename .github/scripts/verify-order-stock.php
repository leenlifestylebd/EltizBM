<?php
// Private, read-only verification. Do not log rows, customer data, or credentials.

// Bootstrapping Laravel's console kernel installs an exception handler that lets
// an uncaught exception exit 0, which would let `set -e` continue past a failed
// check. Fail loudly with a real non-zero status instead.
function verification_failed(string $message): void
{
    fwrite(STDERR, 'VERIFICATION FAILED: '.$message."\n");
    exit(1);
}

require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$mode = $argv[2];
$path = $argv[3];
// The `warehouses` table is deliberately excluded from the byte-for-byte
// fingerprint: the single-main-warehouse migration normalises it (renames the
// surviving default and adds one row per business that had none). Every other
// table, products.stock included, must be identical before and after.
$strict_tables = ['orders', 'order_items', 'products', 'warehouse_stocks', 'stock_adjustments'];
$snapshot = [];
$warehouse_ids = [];
$locationHash = hash_init('sha256');
$hasSource = Illuminate\Support\Facades\Schema::hasColumn('orders', 'warehouse_id');
foreach ($strict_tables as $table) {
    $hash = hash_init('sha256');
    $count = 0;
    foreach (Illuminate\Support\Facades\DB::table($table)->orderBy('id')->cursor() as $row) {
        $data = (array) $row;
        if ($table === 'orders') {
            if ($hasSource) hash_update($locationHash, json_encode([$row->id, $row->warehouse_id], JSON_THROW_ON_ERROR));
            unset($data['warehouse_id']);
        }
        ksort($data);
        hash_update($hash, json_encode($data, JSON_THROW_ON_ERROR));
        $count++;
    }
    $snapshot[$table] = ['rows' => $count, 'sha256' => hash_final($hash)];
}
foreach (Illuminate\Support\Facades\DB::table('warehouses')->orderBy('id')->cursor() as $row) {
    $warehouse_ids[] = (int) $row->id;
}
$snapshot['warehouses'] = ['rows' => count($warehouse_ids)];
$locations = $hasSource ? hash_final($locationHash) : null;
if ($mode === 'before') {
    $snapshot['_locations'] = $locations;
    $snapshot['_warehouse_ids'] = $warehouse_ids;
    file_put_contents($path, json_encode($snapshot, JSON_THROW_ON_ERROR));
    chmod($path, 0600);
    echo "Order and stock data fingerprint captured privately\n";
    exit;
}
$previous = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
$previousLocations = $previous['_locations'];
$previousWarehouseIds = $previous['_warehouse_ids'];
unset($previous['_locations'], $previous['_warehouse_ids']);
foreach ($strict_tables as $table) {
    if (($snapshot[$table] ?? null) !== ($previous[$table] ?? null)) {
        verification_failed("{$table} data changed during migration");
    }
}
// Warehouses may be added or renamed, but no existing one may disappear.
$missing = array_diff($previousWarehouseIds, $warehouse_ids);
if ($missing || count($warehouse_ids) < count($previousWarehouseIds)) {
    verification_failed('Warehouse rows were removed during migration');
}
if (!Illuminate\Support\Facades\Schema::hasColumn('orders', 'warehouse_id')) verification_failed('Order source column missing');
if ($previousLocations !== null && $locations !== $previousLocations) verification_failed('Existing order source changed');
if ($previousLocations === null && App\Models\Order::withTrashed()->whereNotNull('warehouse_id')->exists()) verification_failed('Historical order source was unexpectedly changed');
$user = App\Models\User::whereIn('role', ['owner', 'admin'])->whereNotNull('business_id')->first();
if ($user) {
    Illuminate\Support\Facades\Auth::setUser($user);
    $controller = app(App\Http\Controllers\OrderController::class);
    $view = $controller->create()->with('errors', new Illuminate\Support\ViewErrorBag());
    $html = $view->render();
    // Stock availability must still be shown, and it must come from the single
    // main warehouse: no per-order stock-location selector may reappear.
    if (str_contains($html, 'name="warehouse_id"')) verification_failed('Order stock selector still rendered');
    if (!str_contains($html, 'stockLabel')) verification_failed('Order stock availability did not render');
}
echo "Order stock schema and form verified; existing order and stock data unchanged\n";
