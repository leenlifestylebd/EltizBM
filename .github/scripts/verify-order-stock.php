<?php
// Private, read-only verification. Do not log rows, customer data, or credentials.
require $argv[1].'/vendor/autoload.php';
$app = require $argv[1].'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$mode = $argv[2];
$path = $argv[3];
$tables = ['orders', 'order_items', 'products', 'warehouse_stocks', 'warehouses', 'stock_adjustments'];
$snapshot = [];
$locationHash = hash_init('sha256');
$hasSource = Illuminate\Support\Facades\Schema::hasColumn('orders', 'warehouse_id');
foreach ($tables as $table) {
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
$locations = $hasSource ? hash_final($locationHash) : null;
if ($mode === 'before') {
    $snapshot['_locations'] = $locations;
    file_put_contents($path, json_encode($snapshot, JSON_THROW_ON_ERROR));
    chmod($path, 0600);
    echo "Order and stock data fingerprint captured privately\n";
    exit;
}
$previous = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
$previousLocations = $previous['_locations'];
unset($previous['_locations']);
if ($snapshot !== $previous) throw new RuntimeException('Order or stock data changed during migration');
if (!Illuminate\Support\Facades\Schema::hasColumn('orders', 'warehouse_id')) throw new RuntimeException('Order source column missing');
if ($previousLocations !== null && $locations !== $previousLocations) throw new RuntimeException('Existing order source changed');
if ($previousLocations === null && App\Models\Order::withTrashed()->whereNotNull('warehouse_id')->exists()) throw new RuntimeException('Historical order source was unexpectedly changed');
$user = App\Models\User::whereIn('role', ['owner', 'admin'])->whereNotNull('business_id')->first();
if ($user) {
    Illuminate\Support\Facades\Auth::setUser($user);
    $controller = app(App\Http\Controllers\OrderController::class);
    $view = $controller->create()->with('errors', new Illuminate\Support\ViewErrorBag());
    $html = $view->render();
    if (!str_contains($html, 'name="warehouse_id"') || !str_contains($html, 'stockLabel')) throw new RuntimeException('Order stock selector did not render');
}
echo "Order location schema and form verified; existing order and stock data unchanged\n";
