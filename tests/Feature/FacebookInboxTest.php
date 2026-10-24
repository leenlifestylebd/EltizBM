<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAutomation;
use App\Models\FacebookInboxEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookInboxTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private BusinessAutomation $connection;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.facebook.client_secret' => 'app-secret', 'services.facebook.webhook_verify_token' => str_repeat('v', 40), 'services.facebook.client_id' => '123', 'services.facebook.config_id' => '456', 'services.facebook.redirect' => 'https://app.example/callback', 'services.facebook.cache_store' => 'array']);
        $b = Business::create(['name' => 'One', 'slug' => 'one', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create(['business_id' => $b->id, 'name' => 'Owner', 'email' => 'one@test.example', 'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true]);
        $this->connection = BusinessAutomation::create(['business_id' => $b->id, 'issued_by' => $this->owner->id, 'enabled' => true, 'token_hash' => hash('sha256', 'erp-test'), 'abilities' => ['automation:write'],
            'settings' => ['facebook_page_id' => '1234', 'facebook_connection_mode' => 'oauth', 'facebook_receive_enabled' => true], 'secrets' => ['facebook_page_token' => 'page-secret']]);
        Http::preventStrayRequests();
    }

    private function body(): array
    {
        return ['object' => 'page', 'entry' => [['id' => '1234', 'messaging' => [['sender' => ['id' => '999'], 'recipient' => ['id' => '1234'], 'message' => ['mid' => 'mid1', 'text' => 'Private customer text']]]]]];
    }

    private function deliver(array $body, bool $valid = true)
    {
        $raw = json_encode($body);

        return $this->call('POST', '/api/webhooks/facebook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $valid ? 'sha256='.hash_hmac('sha256', $raw, 'app-secret') : 'sha256=bad'], $raw);
    }

    private function claim()
    {
        return $this->withToken('erp-test')->postJson('/api/v1/bot/facebook-events/claim');
    }

    public function test_verification_requires_correct_token_and_returns_plain_challenge(): void
    {
        $this->get('/api/webhooks/facebook?'.http_build_query(['hub.mode' => 'subscribe', 'hub.verify_token' => str_repeat('v', 40), 'hub.challenge' => '12345']))->assertOk()->assertContent('12345');
        $this->get('/api/webhooks/facebook?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=123')->assertForbidden();
    }

    public function test_signature_duplicate_delivery_and_encrypted_customer_text(): void
    {
        $this->deliver($this->body(), false)->assertForbidden();
        $this->assertDatabaseCount('facebook_inbox_events', 0);
        $this->deliver($this->body())->assertOk();
        $this->deliver($this->body())->assertOk();
        $this->assertDatabaseCount('facebook_inbox_events', 1);
        $e = FacebookInboxEvent::firstOrFail();
        $this->assertSame('Private customer text', $e->payload['text']);
        $this->assertStringNotContainsString('Private customer text', $e->getRawOriginal('payload'));
        Http::assertNothingSent();
    }

    public function test_echoes_unknown_pages_and_paused_intake_are_ignored(): void
    {
        $body = $this->body();
        $body['entry'][0]['messaging'][0]['message']['is_echo'] = true;
        $this->deliver($body)->assertOk();
        $body = $this->body();
        $body['entry'][0]['id'] = '9999';
        $this->deliver($body)->assertOk();
        $this->connection->update(['settings' => ['facebook_page_id' => '1234', 'facebook_receive_enabled' => false]]);
        $this->deliver($this->body())->assertOk();
        $this->assertDatabaseCount('facebook_inbox_events', 0);
    }

    public function test_comments_keep_post_context_and_nontext_events_need_humans(): void
    {
        $this->deliver(['object' => 'page', 'entry' => [['id' => '1234', 'changes' => [['field' => 'feed', 'value' => ['item' => 'comment', 'verb' => 'add', 'comment_id' => 'c1', 'post_id' => 'p1', 'message' => 'Price?', 'from' => ['id' => '999']]], ['field' => 'feed', 'value' => ['item' => 'comment', 'verb' => 'add', 'comment_id' => 'c2', 'from' => ['id' => '1234'], 'message' => 'Page own reply']]]]]])->assertOk();
        $this->assertSame('p1', FacebookInboxEvent::firstOrFail()->payload['post_id']);
        $body = $this->body();
        unset($body['entry'][0]['messaging'][0]['message']['text']);
        $this->deliver($body)->assertOk();
        $this->assertSame('handover', FacebookInboxEvent::latest('id')->first()->status);
        $this->assertDatabaseCount('facebook_inbox_events', 2);
    }

    public function test_claim_is_leased_and_draft_does_not_send_messages(): void
    {
        $this->deliver($this->body());
        $claim = $this->claim()->assertOk()->json();
        $this->claim()->assertJson(['event' => null]);
        $this->withToken('erp-test')->postJson('/api/v1/bot/facebook-events/'.$claim['event']['id'].'/draft', ['lease_token' => $claim['lease_token'], 'decision' => 'review', 'draft' => 'Suggested reply', 'reason' => 'Catalog matches', 'confidence' => 0.95])->assertOk()->assertJson(['status' => 'review', 'sent' => false]);
        $event = FacebookInboxEvent::firstOrFail();
        $this->assertSame('Suggested reply', $event->draft);
        $this->assertStringNotContainsString('Suggested reply', $event->getRawOriginal('draft'));
        Http::assertNothingSent();
    }

    public function test_low_confidence_and_expired_leases_cannot_auto_approve(): void
    {
        $this->deliver($this->body());
        $first = $this->claim()->json();
        $this->travel(11)->minutes();
        $next = $this->claim()->json();
        $data = ['lease_token' => $first['lease_token'], 'decision' => 'review', 'draft' => 'Guess', 'reason' => 'Uncertain', 'confidence' => 0.2];
        $url = '/api/v1/bot/facebook-events/'.$first['event']['id'].'/draft';
        $this->withToken('erp-test')->postJson($url, $data)->assertStatus(409);
        $data['lease_token'] = $next['lease_token'];
        $this->postJson($url, $data)->assertOk()->assertJson(['status' => 'handover', 'sent' => false]);
    }

    public function test_exhausted_attempts_go_to_handover(): void
    {
        $this->deliver($this->body());
        for ($i = 0; $i < 3; $i++) {
            $this->claim()->assertOk();
            $this->travel(11)->minutes();
        }
        $this->claim()->assertJson(['event' => null]);
        $this->assertSame('handover', FacebookInboxEvent::firstOrFail()->status);
    }

    public function test_api_permissions_and_business_isolation(): void
    {
        $this->deliver($this->body());
        $claim = $this->claim()->json();
        $b = Business::create(['name' => 'Other', 'slug' => 'other', 'trial_ends_at' => now()->addWeek()]);
        $other = User::create(['business_id' => $b->id, 'name' => 'Other', 'email' => 'other@test.example', 'password' => 'password', 'role' => 'owner', 'is_active' => true]);
        BusinessAutomation::create(['business_id' => $b->id, 'issued_by' => $other->id, 'enabled' => true, 'token_hash' => hash('sha256', 'other-token'), 'abilities' => ['automation:write'], 'settings' => ['facebook_receive_enabled' => true, 'facebook_page_id' => '555'], 'secrets' => ['facebook_page_token' => 'other-page-token']]);
        $this->withToken('other-token')->postJson('/api/v1/bot/facebook-events/claim')->assertJson(['event' => null]);
        $this->postJson('/api/v1/bot/facebook-events/'.$claim['event']['id'].'/draft', ['lease_token' => $claim['lease_token'], 'decision' => 'handover', 'reason' => 'Test', 'confidence' => 0])->assertNotFound();
        $this->actingAs($other)->get(route('settings.facebook.inbox'))->assertOk()->assertDontSee('Private customer text');
        $this->connection->update(['abilities' => ['catalog:read']]);
        $this->withToken('erp-test')->postJson('/api/v1/bot/facebook-events/claim')->assertForbidden();
    }

    public function test_subscription_preserves_existing_fields_and_pause_stops_processing(): void
    {
        Http::fake(function ($request) {
            if ($request->method() === 'POST') {
                return Http::response(['success' => true]);
            }
            if (str_contains($request->url(), 'subscribed_apps')) {
                return Http::response(['data' => [['id' => '123', 'subscribed_fields' => ['leadgen']]]]);
            }

            return Http::response(['id' => '1234', 'name' => 'Page']);
        });
        $this->actingAs($this->owner)->post(route('settings.facebook.subscribe'))->assertRedirect();
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_contains($r['subscribed_fields'], 'leadgen') && str_contains($r['subscribed_fields'], 'messages') && str_contains($r['subscribed_fields'], 'feed'));
        $this->post(route('settings.facebook.pause'))->assertRedirect();
        $this->assertFalse($this->connection->fresh()->settings['facebook_receive_enabled']);
    }
}
