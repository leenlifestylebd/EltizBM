<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ProductionEntry;
use App\Models\ProductionRate;
use App\Models\ProductionTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionRateHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Employee $worker;
    private ProductionRate $rate;

    protected function setUp(): void
    {
        parent::setUp();
        $business = Business::create(['name' => 'Rate Test', 'slug' => 'rate-test', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create([
            'business_id' => $business->id, 'name' => 'Owner', 'email' => 'rate-owner@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true,
        ]);
        $this->worker = Employee::create([
            'business_id' => $business->id, 'name' => 'Worker', 'salary' => 0,
            'type' => 'production', 'status' => 'active',
        ]);
        $this->rate = ProductionRate::create([
            'business_id' => $business->id, 'product_name' => 'Shirt', 'variant_name' => 'Full sleeve',
            'color' => 'Blue', 'size' => 'M', 'rate' => 10.25, 'unit' => 'piece', 'stage' => 'sewing', 'is_active' => true,
        ]);
        $this->actingAs($this->owner);
    }

    private function assign(ProductionRate $rate): ProductionTask
    {
        $this->post(route('production.tasks.store'), [
            'employee_id' => $this->worker->id, 'production_rate_id' => $rate->id,
            'product_name' => 'Shirt', 'assigned_quantity' => 10, 'stage' => $rate->stage,
        ])->assertSessionHasNoErrors()->assertRedirect();
        return ProductionTask::latest('id')->firstOrFail();
    }

    private function changeRate(ProductionRate $rate, float $amount): ProductionRate
    {
        $this->put(route('production.rates.update', $rate), ['rate' => $amount])
            ->assertSessionHasNoErrors()->assertRedirect();
        return ProductionRate::where('is_active', true)->latest('id')->firstOrFail();
    }

    private function submit(ProductionTask $task, int $quantity): void
    {
        $this->post(route('production.tasks.submit', $task), ['submitted_quantity' => $quantity])
            ->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_existing_partial_and_unstarted_work_keep_the_old_rate_and_new_work_uses_the_edited_rate(): void
    {
        $partial = $this->assign($this->rate);
        $unstarted = $this->assign($this->rate);
        $this->submit($partial, 2);
        $entry = ProductionEntry::firstOrFail();
        $newRate = $this->changeRate($this->rate, 15.75);
        $this->assertFalse((bool) $this->rate->fresh()->is_active);
        $this->assertSame(10.25, (float) $this->rate->fresh()->rate);
        $this->assertSame(20.5, (float) $entry->fresh()->amount);
        $this->assertSame($this->rate->id, $partial->fresh()->production_rate_id);
        foreach (['business_id', 'product_name', 'variant_name', 'color', 'size', 'unit', 'stage'] as $field) {
            $this->assertEquals($this->rate->$field, $newRate->$field);
        }
        $this->submit($partial, 8);
        $this->submit($unstarted, 10);
        $newTask = $this->assign($newRate);
        $this->submit($newTask, 10);
        $this->assertSame(102.5, (float) $partial->submissions()->sum('amount'));
        $this->assertSame(102.5, (float) $unstarted->submissions()->sum('amount'));
        $this->assertSame(157.5, (float) $newTask->submissions()->sum('amount'));
        $this->assertSame(362.5, (float) ProductionEntry::sum('amount'));
        $this->get(route('production.tasks.show', $unstarted))->assertOk()->assertSee('10.25');
        $this->get(route('production.tasks.create'))->assertOk()->assertViewHas('rates', fn ($rates) => $rates->modelKeys() === [$newRate->id]);
    }

    public function test_repeated_edits_and_zero_rates_preserve_each_assignment_version(): void
    {
        $first = $this->assign($this->rate);
        $secondRate = $this->changeRate($this->rate, 20);
        $second = $this->assign($secondRate);
        $zeroRate = $this->changeRate($secondRate, 0);
        $zero = $this->assign($zeroRate);
        $this->changeRate($zeroRate, 30);
        foreach ([$first, $second, $zero] as $task) {
            $this->submit($task, 1);
        }
        $this->assertSame(10.25, (float) $first->submissions()->sum('amount'));
        $this->assertSame(20.0, (float) $second->submissions()->sum('amount'));
        $this->assertSame(0.0, (float) $zero->submissions()->sum('amount'));
        $this->assertSame(1, ProductionRate::where('is_active', true)->count());
        $this->assertDatabaseCount('production_rates', 4);
    }

    public function test_archived_rate_cannot_be_reedited_or_selected_from_a_stale_assignment_form(): void
    {
        $newRate = $this->changeRate($this->rate, 15);
        $this->put(route('production.rates.update', $this->rate), ['rate' => 99, 'is_active' => true])
            ->assertSessionHasErrors('rate');
        $this->post(route('production.tasks.store'), [
            'employee_id' => $this->worker->id, 'production_rate_id' => $this->rate->id,
            'product_name' => 'Shirt', 'assigned_quantity' => 10,
        ])->assertSessionHasErrors('production_rate_id');
        $this->assertDatabaseCount('production_tasks', 0);
        $this->assertSame(15.0, (float) $newRate->fresh()->rate);
        $this->assertSame(10.25, (float) $this->rate->fresh()->rate);
    }

    public function test_forwarding_only_uses_current_rates(): void
    {
        $source = $this->assign($this->rate);
        $source->update(['completed_quantity' => 10, 'status' => 'completed']);
        $this->rate = $this->rate->replicate();
        $this->rate->stage = 'ironing';
        $this->rate->save();
        $newRate = $this->changeRate($this->rate, 18);
        $data = ['employee_id' => $this->worker->id, 'production_rate_id' => $this->rate->id, 'quantity' => 5];
        $this->post(route('production.tasks.forward', $source), $data)->assertSessionHasErrors('production_rate_id');
        $data['production_rate_id'] = $newRate->id;
        $this->post(route('production.tasks.forward', $source), $data)->assertSessionHasNoErrors()->assertRedirect();
        $forwarded = $source->forwardedTasks()->firstOrFail();
        $this->assertSame($newRate->id, $forwarded->production_rate_id);
        $this->submit($forwarded, 5);
        $this->assertSame(90.0, (float) $forwarded->submissions()->sum('amount'));
    }

    public function test_inventory_cutting_only_uses_current_rates(): void
    {
        // The production migration widens these columns only on MySQL.
        // Match that schema in this isolated SQLite test for the cutting stage.
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            foreach (['production_rates', 'production_tasks'] as $tableName) {
                \Illuminate\Support\Facades\Schema::table($tableName, function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('stage', 20)->default('sewing')->change();
                });
            }
        }
        $this->rate->update(['stage' => 'cutting']);
        $product = Product::create([
            'business_id' => $this->owner->business_id, 'name' => 'Shirt', 'slug' => 'shirt',
            'stock' => 0, 'low_stock_alert' => 5, 'price' => 100,
        ]);
        $newRate = $this->changeRate($this->rate, 12);
        $data = ['employee_id' => $this->worker->id, 'production_rate_id' => $this->rate->id, 'product_ids' => [$product->id]];
        $this->post(route('inventory.cutting-list.send'), $data)->assertSessionHasErrors('production_rate_id');
        $data['production_rate_id'] = $newRate->id;
        $this->post(route('inventory.cutting-list.send'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $task = ProductionTask::firstOrFail();
        $this->assertSame($newRate->id, $task->production_rate_id);
        $this->submit($task, 5);
        $this->assertSame(60.0, (float) $task->submissions()->sum('amount'));
    }

    public function test_archiving_keeps_earnings_and_all_existing_task_links(): void
    {
        $task = $this->assign($this->rate);
        $this->submit($task, 2);
        $this->delete(route('production.rates.destroy', $this->rate))->assertRedirect();
        $this->assertDatabaseCount('production_entries', 1);
        $this->assertSame($this->rate->id, $task->fresh()->production_rate_id);
        $this->assertFalse((bool) $this->rate->fresh()->is_active);
        $this->submit($task, 8);
        $this->assertSame(102.5, (float) $task->submissions()->sum('amount'));
    }

    public function test_invalid_amounts_are_rejected_and_unchanged_rate_does_not_create_a_version(): void
    {
        foreach ([null, '', -1, 'invalid', '10.256', '10000000000'] as $invalid) {
            $this->put(route('production.rates.update', $this->rate), ['rate' => $invalid])->assertSessionHasErrors('rate');
        }
        $this->changeRate($this->rate, 10.25);
        $this->assertDatabaseCount('production_rates', 1);
        $this->assertTrue((bool) $this->rate->fresh()->is_active);
    }

    public function test_another_business_cannot_edit_archive_or_assign_the_rate(): void
    {
        $other = Business::create(['name' => 'Other', 'slug' => 'other', 'trial_ends_at' => now()->addWeek()]);
        $foreign = ProductionRate::create(['business_id' => $other->id, 'product_name' => 'Other', 'rate' => 50, 'is_active' => true]);
        $this->put(route('production.rates.update', $foreign), ['rate' => 1])->assertForbidden();
        $this->delete(route('production.rates.destroy', $foreign))->assertForbidden();
        $this->post(route('production.tasks.store'), [
            'employee_id' => $this->worker->id, 'production_rate_id' => $foreign->id,
            'product_name' => 'Other', 'assigned_quantity' => 10,
        ])->assertSessionHasErrors('production_rate_id');
        $this->assertSame(50.0, (float) $foreign->fresh()->rate);
    }

    public function test_rates_page_has_edit_controls_only_for_active_rates(): void
    {
        $newRate = $this->changeRate($this->rate, 15.75);
        $this->get(route('production.rates'))->assertOk()
            ->assertSee('id="edit-rate-'.$newRate->id.'"', false)
            ->assertDontSee('id="edit-rate-'.$this->rate->id.'"', false)
            ->assertSee('15.75')->assertSee('10.25')->assertSee('Archived')
            ->assertSee('Save Rate')->assertSee('Existing work keeps its original rate.');
    }
}
