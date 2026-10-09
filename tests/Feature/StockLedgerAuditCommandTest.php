<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLedgerAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->business = Business::create(['name' => 'Stock Audit', 'slug' => 'stock-audit', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $this->business->id, 'name' => 'Owner', 'email' => 'stock-audit@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
    }

    public function test_the_stock_audit_flags_mismatches_and_changes_nothing(): void
    {
        $mismatch = Product::create([
            'business_id' => $this->business->id, 'name' => 'Ghost stock', 'slug' => 'ghost',
            'price' => 10, 'stock' => 5, 'track_stock' => true,
        ]);
        $matched = Product::create([
            'business_id' => $this->business->id, 'name' => 'Clean', 'slug' => 'clean',
            'price' => 10, 'stock' => 10, 'track_stock' => true,
        ]);
        StockAdjustment::create([
            'business_id' => $this->business->id, 'product_id' => $matched->id, 'created_by' => $this->owner->id,
            'type' => 'increase', 'quantity' => 10, 'quantity_before' => 0, 'quantity_after' => 10,
        ]);

        $before = Product::count();

        $this->artisan('erp:stock-ledger-audit')
            ->expectsOutputToContain('Read-only report')
            ->assertExitCode(0);

        // Nothing was written or altered.
        $this->assertSame($before, Product::count());
        $this->assertSame(5, (int) $mismatch->fresh()->stock);
        $this->assertSame(10, (int) $matched->fresh()->stock);
    }

    public function test_csv_export_writes_the_mismatch_file(): void
    {
        Product::create([
            'business_id' => $this->business->id, 'name' => 'Ghost', 'slug' => 'ghost-2',
            'price' => 10, 'stock' => 5, 'track_stock' => true,
        ]);
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eltiz-stock-audit-' . uniqid() . '.csv';

        $this->artisan('erp:stock-ledger-audit', ['--csv' => $path])->assertExitCode(0);

        $this->assertFileExists($path);
        $this->assertStringContainsString('product_id', (string) file_get_contents($path));
    }
}
