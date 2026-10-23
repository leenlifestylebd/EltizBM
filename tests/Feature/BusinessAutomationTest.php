<?php

namespace Tests\Feature;

use App\Models\AccountCategory;
use App\Models\Business;
use App\Models\BusinessAutomation;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessAutomationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherOwner;

    private string $token = 'eltiz_test-token-for-business-one';

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->ownerFor('One');
        $this->otherOwner = $this->ownerFor('Two');
    }

    private function ownerFor(string $name): User
    {
        $business = Business::create(['name' => $name, 'slug' => strtolower($name), 'trial_ends_at' => now()->addWeek()]);

        return User::create(['business_id' => $business->id, 'name' => $name, 'email' => strtolower($name).'@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true]);
    }

    private function configure(array $abilities = ['catalog:read']): BusinessAutomation
    {
        return BusinessAutomation::create(['business_id' => $this->owner->business_id, 'issued_by' => $this->owner->id,
            'enabled' => true, 'token_hash' => hash('sha256', $this->token), 'abilities' => $abilities]);
    }

    private function headers(?string $key = null): array
    {
        return array_filter(['Authorization' => 'Bearer '.$this->token, 'Idempotency-Key' => $key]);
    }

    private function product(User $owner): Product
    {
        return Product::create(['business_id' => $owner->business_id, 'name' => 'Fabric '.$owner->name,
            'slug' => 'fabric-'.$owner->id, 'sku' => 'FAB-'.$owner->id, 'type' => 'simple', 'price' => 116, 'cost_price' => 50,
            'stock' => 5, 'track_stock' => true, 'is_active' => true]);
    }

    private function expense(): array
    {
        $category = AccountCategory::firstOrCreate(['business_id' => $this->owner->business_id, 'name' => 'Rent', 'type' => 'expense']);

        return ['type' => 'expense', 'title' => 'Office rent', 'category_id' => $category->id, 'amount' => '5000.00',
            'transaction_date' => '2026-10-23', 'payment_method' => 'cash'];
    }

    public function test_owner_can_save_encrypted_secrets_and_blank_keeps_them(): void
    {
        $this->actingAs($this->owner)->post(route('settings.automation.update'), [
            'openrouter_api_key' => 'private-value-one', 'openrouter_model' => 'provider/model:free',
            'abilities' => ['catalog:read'], 'enabled' => 1, 'business_id' => $this->otherOwner->business_id,
        ])->assertRedirect(route('settings.automation'));
        $record = BusinessAutomation::firstOrFail();
        $this->assertSame($this->owner->business_id, $record->business_id);
        $this->assertSame('private-value-one', $record->secrets['openrouter_api_key']);
        $this->assertStringNotContainsString('private-value-one', $record->getRawOriginal('secrets'));
        $this->get(route('settings.automation'))->assertOk()->assertSee('Saved')->assertDontSee('private-value-one');
        $this->post(route('settings.automation.update'), ['openrouter_api_key' => '', 'enabled' => 0]);
        $this->assertSame('private-value-one', $record->fresh()->secrets['openrouter_api_key']);
        $this->post(route('settings.automation.update'), ['clear_secrets' => ['openrouter_api_key']]);
        $this->assertArrayNotHasKey('openrouter_api_key', $record->fresh()->secrets);
    }

    public function test_validation_never_flashes_secret_input(): void
    {
        $this->actingAs($this->owner)->post(route('settings.automation.update'), [
            'n8n_webhook_url' => 'http://unsafe.example', 'openrouter_api_key' => 'never-flash-me',
            'facebook_app_secret' => 'never-flash-this', 'abilities' => ['made-up'],
        ])->assertSessionHasErrors(['n8n_webhook_url', 'abilities.0'])
            ->assertSessionMissing('_old_input.openrouter_api_key')->assertSessionMissing('_old_input.facebook_app_secret');
        $this->assertDatabaseCount('business_automations', 0);
    }

    public function test_staff_cannot_read_or_change_credentials_even_with_settings_permission(): void
    {
        $this->owner->update(['role' => 'staff', 'permissions' => ['settings']]);
        $this->actingAs($this->owner)->get(route('settings.automation'))->assertForbidden();
        $this->post(route('settings.automation.update'), [])->assertForbidden();
        $this->post(route('settings.automation.token'))->assertForbidden();
        $this->delete(route('settings.automation.revoke'))->assertForbidden();
    }

    public function test_new_token_is_shown_once_hashed_and_can_be_rotated_and_revoked(): void
    {
        $this->configure();
        $response = $this->actingAs($this->owner)->post(route('settings.automation.token'))->assertOk();
        $token = $response->viewData('newToken');
        $record = BusinessAutomation::firstOrFail();
        $this->assertSame(hash('sha256', $token), $record->token_hash);
        $this->get(route('settings.automation'))->assertDontSee($token);
        $this->getJson('/api/v1/bot/health', $this->headers())->assertUnauthorized();
        $this->withToken($token)->getJson('/api/v1/bot/health')->assertOk();
        $this->delete(route('settings.automation.revoke'))->assertRedirect();
        $this->getJson('/api/v1/bot/health', ['Authorization' => 'Bearer '.$token])->assertUnauthorized();
    }

    public function test_settings_are_isolated_between_businesses(): void
    {
        $this->configure()->update(['settings' => ['facebook_page_id' => '123123123'], 'secrets' => ['telegram_bot_token' => 'private-token']]);
        $this->actingAs($this->otherOwner)->get(route('settings.automation'))->assertOk()->assertDontSee('123123123')->assertDontSee('private-token');
        $this->post(route('settings.automation.update'), ['facebook_page_id' => '555']);
        $this->assertSame('123123123', BusinessAutomation::where('business_id', $this->owner->business_id)->first()->settings['facebook_page_id']);
    }

    public function test_token_scope_ignores_supplied_business_id_and_denies_cross_business_product(): void
    {
        $this->configure();
        $own = $this->product($this->owner);
        $other = $this->product($this->otherOwner);
        $this->getJson('/api/v1/bot/products?business_id='.$this->otherOwner->business_id, $this->headers())
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id);
        $this->getJson('/api/v1/bot/products/'.$other->id, $this->headers())->assertNotFound();
        $this->getJson('/api/v1/bot/stock?product_id='.$other->id, $this->headers())->assertNotFound();
    }

    public function test_legacy_environment_token_is_not_allowed_to_choose_a_business(): void
    {
        config(['services.bot.token' => 'old-global-token', 'services.bot.business_id' => $this->owner->business_id]);
        $this->getJson('/api/v1/bot/health', ['Authorization' => 'Bearer old-global-token'])->assertUnauthorized();
        $this->getJson('/api/v1/bot/health')->assertUnauthorized();
    }

    public function test_disabled_token_or_inactive_issuer_cannot_access_api(): void
    {
        $record = $this->configure();
        $this->getJson('/api/v1/bot/health', $this->headers())->assertOk()->assertJsonPath('business_id', $this->owner->business_id);
        $record->update(['enabled' => false]);
        $this->getJson('/api/v1/bot/health', $this->headers())->assertUnauthorized();
        $record->update(['enabled' => true]);
        $this->owner->update(['is_active' => false]);
        $this->getJson('/api/v1/bot/health', $this->headers())->assertUnauthorized();
    }

    public function test_read_token_cannot_write_or_fetch_provider_credentials(): void
    {
        $this->configure();
        $this->postJson('/api/v1/bot/orders', [], $this->headers())->assertForbidden();
        $this->getJson('/api/v1/bot/reports/summary', $this->headers())->assertForbidden();
        $this->getJson('/api/v1/bot/automation/config', $this->headers())->assertForbidden();
    }

    public function test_explicit_credential_permission_only_returns_own_configuration(): void
    {
        $this->configure(['automation:read'])->update(['secrets' => ['openrouter_api_key' => 'my-secret']]);
        $response = $this->getJson('/api/v1/bot/automation/config', $this->headers())->assertOk()->assertJsonPath('credentials.openrouter_api_key', 'my-secret');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response->assertJsonMissingPath('token_hash');
    }

    public function test_accounting_writes_replay_without_duplicate_and_reject_changed_request(): void
    {
        $this->configure(['transactions:write']);
        $data = $this->expense();
        $first = $this->postJson('/api/v1/bot/transactions', $data, $this->headers('expense-001'))->assertCreated();
        $this->postJson('/api/v1/bot/transactions', $data, $this->headers('expense-001'))->assertCreated()
            ->assertHeader('Idempotency-Replayed', 'true')->assertJsonPath('id', $first->json('id'));
        $data['amount'] = '6000.00';
        $this->postJson('/api/v1/bot/transactions', $data, $this->headers('expense-001'))->assertConflict();
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_accounting_rejects_foreign_category_missing_key_and_fractional_poisha(): void
    {
        $this->configure(['transactions:write']);
        $data = $this->expense();
        $this->postJson('/api/v1/bot/transactions', $data, $this->headers())->assertUnprocessable();
        $data['category_id'] = AccountCategory::create(['business_id' => $this->otherOwner->business_id, 'name' => 'Rent', 'type' => 'expense'])->id;
        $this->postJson('/api/v1/bot/transactions', $data, $this->headers('expense-002'))->assertUnprocessable();
        $data = $this->expense();
        $data['amount'] = '1.005';
        $this->postJson('/api/v1/bot/transactions', $data, $this->headers('expense-003'))->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('bot_api_requests', 0);
    }

    public function test_orders_are_business_scoped_and_retries_only_deduct_stock_once(): void
    {
        $this->configure(['orders:write']);
        $product = $this->product($this->owner);
        $data = ['customer' => ['name' => 'Customer', 'phone' => '01700000000'],
            'items' => [['product_id' => $product->id, 'quantity' => 2]], 'source' => 'telegram'];
        $first = $this->postJson('/api/v1/bot/orders', $data, $this->headers('order-0001'))->assertCreated();
        $this->postJson('/api/v1/bot/orders', $data, $this->headers('order-0001'))->assertCreated()->assertJsonPath('order_number', $first->json('order_number'));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('stock_adjustments', 1);
        $this->assertSame(3, $product->fresh()->stock);
        app(StockService::class)->restoreForOrder(Order::first()->load('items'));
        $this->assertSame(5, $product->fresh()->stock);
        $other = $this->product($this->otherOwner);
        $data['items'][0]['product_id'] = $other->id;
        $this->postJson('/api/v1/bot/orders', $data, $this->headers('order-0002'))->assertUnprocessable();
    }

    public function test_duplicate_order_lines_cannot_oversell_and_overpayment_is_rejected(): void
    {
        $this->configure(['orders:write']);
        $product = $this->product($this->owner);
        $data = ['customer' => ['name' => 'Customer', 'phone' => '01700000000'],
            'items' => [['product_id' => $product->id, 'quantity' => 3], ['product_id' => $product->id, 'quantity' => 3]]];
        $this->postJson('/api/v1/bot/orders', $data, $this->headers('order-0003'))->assertConflict();
        $data['items'] = [['product_id' => $product->id, 'quantity' => 1]];
        $data['paid_amount'] = 500;
        $this->postJson('/api/v1/bot/orders', $data, $this->headers('order-0004'))->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_rate_limits_are_independent_for_businesses_on_the_same_ip(): void
    {
        $this->configure();
        $secondToken = 'eltiz_other-business-token';
        BusinessAutomation::create(['business_id' => $this->otherOwner->business_id, 'issued_by' => $this->otherOwner->id,
            'enabled' => true, 'token_hash' => hash('sha256', $secondToken), 'abilities' => ['catalog:read']]);
        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/v1/bot/health', $this->headers())->assertOk();
        }
        $this->getJson('/api/v1/bot/health', $this->headers())->assertStatus(429);
        $this->getJson('/api/v1/bot/health', ['Authorization' => 'Bearer '.$secondToken])->assertOk();
    }

    public function test_malformed_accounting_type_returns_validation_error_not_server_error(): void
    {
        $this->configure(['transactions:write']);
        $data = $this->expense();
        $data['type'] = ['expense'];
        $this->postJson('/api/v1/bot/transactions', $data, $this->headers('expense-malformed'))->assertUnprocessable();
        $this->assertDatabaseCount('transactions', 0);
    }
}
