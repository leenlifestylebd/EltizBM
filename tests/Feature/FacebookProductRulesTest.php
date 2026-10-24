<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAutomation;
use App\Models\FacebookInboxEvent;
use App\Models\FacebookProductMapping;
use App\Models\Product;
use App\Models\User;
use App\Services\FacebookAutomaticReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookProductRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Product $product;

    private FacebookProductMapping $mapping;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.facebook.client_secret' => 'secret', 'services.facebook.version' => 'v24.0']);
        $b = Business::create(['name' => 'Rules', 'slug' => 'rules', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create(['business_id' => $b->id, 'name' => 'Owner', 'email' => 'rules@test.example', 'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true]);
        BusinessAutomation::create(['business_id' => $b->id, 'issued_by' => $this->owner->id, 'enabled' => true, 'token_hash' => hash('sha256', 'rules-token'), 'abilities' => ['automation:write'], 'settings' => ['facebook_page_id' => '1234'], 'secrets' => ['facebook_page_token' => 'page-secret']]);
        $this->product = Product::create(['business_id' => $b->id, 'name' => 'Fabric', 'slug' => 'fabric', 'sku' => 'FAB', 'price' => 116, 'stock' => 4, 'is_active' => true, 'track_stock' => true]);
        $this->mapping = FacebookProductMapping::create(['business_id' => $b->id, 'page_id' => '1234', 'source_type' => 'ad', 'external_id' => '555', 'product_id' => $this->product->id, 'auto_reply' => true]);
        Http::preventStrayRequests();
    }

    private function event(string $text = 'price'): FacebookInboxEvent
    {
        return FacebookInboxEvent::create(['business_id' => $this->owner->business_id, 'page_id' => '1234', 'event_key' => bin2hex(random_bytes(32)), 'kind' => 'message', 'status' => 'handover', 'draft' => 'Untrusted AI says 9999', 'payload' => ['sender_id' => '999', 'external_id' => 'mid-real', 'text' => $text, 'ad_id' => '555']]);
    }

    public function test_enabled_rule_uses_erp_price_not_ai_text(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['message_id' => 'out'])]);
        $e = $this->event();
        $result = app(FacebookAutomaticReply::class)->attempt($this->owner->business_id, $e->id);
        $this->assertTrue($result['sent']);
        Http::assertSent(fn ($r) => str_contains($r['message']['text'], '116.00') && ! str_contains($r['message']['text'], '9999'));
        $this->assertSame('mapping:'.$this->mapping->id, $e->fresh()->reply->source);
    }

    public function test_unknown_disabled_or_complex_questions_never_send(): void
    {
        $this->mapping->update(['auto_reply' => false]);
        $e = $this->event();
        $this->assertFalse(app(FacebookAutomaticReply::class)->attempt($this->owner->business_id, $e->id)['sent']);
        $this->mapping->update(['auto_reply' => true]);
        $e = $this->event('price and give me a discount');
        $this->assertFalse(app(FacebookAutomaticReply::class)->attempt($this->owner->business_id, $e->id)['sent']);
        $this->mapping->delete();
        $e = $this->event();
        $this->assertFalse(app(FacebookAutomaticReply::class)->attempt($this->owner->business_id, $e->id)['sent']);
        Http::assertNothingSent();
    }

    public function test_conflicting_ad_and_post_products_require_review(): void
    {
        $other = Product::create(['business_id' => $this->owner->business_id, 'name' => 'Other', 'slug' => 'other', 'price' => 500, 'is_active' => true]);
        FacebookProductMapping::create(['business_id' => $this->owner->business_id, 'page_id' => '1234', 'source_type' => 'post', 'external_id' => '1234_888', 'product_id' => $other->id, 'auto_reply' => true]);
        $e = $this->event();
        $e->update(['payload' => array_merge($e->payload, ['post_id' => '1234_888'])]);
        $this->assertNull(app(FacebookAutomaticReply::class)->context($e));
        Http::assertNothingSent();
    }

    public function test_owner_rule_rejects_other_business_product_and_auto_api_requires_scope(): void
    {
        $b = Business::create(['name' => 'Other', 'slug' => 'other-rules', 'trial_ends_at' => now()->addWeek()]);
        $p = Product::create(['business_id' => $b->id, 'name' => 'Other', 'slug' => 'other', 'price' => 500, 'is_active' => true]);
        $this->actingAs($this->owner)->post(route('settings.facebook.rules.store'), ['source_type' => 'ad', 'external_id' => '900', 'product_id' => $p->id, 'auto_reply' => 1])->assertSessionHasErrors('product_id');
        $e = $this->event();
        $this->withToken('rules-token')->postJson('/api/v1/bot/facebook-events/'.$e->id.'/auto-reply')->assertForbidden();
        Http::assertNothingSent();
    }
}
