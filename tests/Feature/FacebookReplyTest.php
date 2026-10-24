<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAutomation;
use App\Models\FacebookInboxEvent;
use App\Models\FacebookReply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookReplyTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private BusinessAutomation $connection;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.facebook.client_secret' => 'secret', 'services.facebook.version' => 'v24.0']);
        $b = Business::create(['name' => 'Reply', 'slug' => 'reply', 'trial_ends_at' => now()->addWeek()]);
        $this->owner = User::create(['business_id' => $b->id, 'name' => 'Owner', 'email' => 'reply@test.example', 'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true]);
        $this->connection = BusinessAutomation::create(['business_id' => $b->id, 'issued_by' => $this->owner->id, 'settings' => ['facebook_page_id' => '1234'], 'secrets' => ['facebook_page_token' => 'page-secret']]);
        Http::preventStrayRequests();
    }

    private function event(array $attrs = []): FacebookInboxEvent
    {
        return FacebookInboxEvent::create(array_merge(['business_id' => $this->owner->business_id, 'page_id' => '1234', 'event_key' => bin2hex(random_bytes(32)), 'kind' => 'message', 'status' => 'review', 'draft' => 'Draft', 'payload' => ['text' => 'Price?', 'sender_id' => '999', 'external_id' => 'mid-real', 'occurred_at' => now()->timestamp]], $attrs));
    }

    private function send(FacebookInboxEvent $e, string $text = 'Approved reply')
    {
        return $this->actingAs($this->owner)->post(route('settings.facebook.reply', $e->id), ['reply' => $text]);
    }

    public function test_approved_message_sends_once_and_stores_encrypted_reply(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['message_id' => 'mid-out', 'recipient_id' => '999'])]);
        $e = $this->event();
        $this->send($e)->assertSessionHas('success');
        $this->assertSame('sent', $e->fresh()->status);
        $r = FacebookReply::firstOrFail();
        $this->assertSame($this->owner->id, $r->approved_by);
        $this->assertSame('Approved reply', $r->text);
        $this->assertStringNotContainsString('Approved reply', $r->getRawOriginal('text'));
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/1234/messages') && $r['recipient']['id'] === '999' && $r['message']['text'] === 'Approved reply' && $r['messaging_type'] === 'RESPONSE');
        $this->send($e)->assertSessionHasErrors('reply');
        Http::assertSentCount(1);
    }

    public function test_comment_reply_uses_comment_edge(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['id' => '100_200'])]);
        $e = $this->event(['kind' => 'comment', 'payload' => ['text' => 'Question', 'sender_id' => '999', 'external_id' => '100_101']]);
        $this->send($e)->assertSessionHas('success');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/100_101/comments') && $r['message'] === 'Approved reply');
    }

    public function test_expired_window_and_synthetic_event_cannot_send(): void
    {
        $e = $this->event(['payload' => ['sender_id' => '999', 'external_id' => 'mid-old', 'occurred_at' => now()->subHours(25)->timestamp]]);
        $this->send($e)->assertSessionHasErrors('reply');
        $e = $this->event(['payload' => ['sender_id' => '0', 'external_id' => 'setup-test-123']]);
        $this->send($e)->assertSessionHasErrors('reply');
        Http::assertNothingSent();
    }

    public function test_unknown_delivery_blocks_retry(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $e = $this->event();
        $this->send($e)->assertSessionHas('error');
        $this->assertSame('send_unknown', $e->fresh()->status);
        $this->assertSame('unknown', FacebookReply::first()->status);
        $this->send($e)->assertSessionHasErrors('reply');
        $this->assertDatabaseCount('facebook_replies', 1);
    }

    public function test_explicit_rejection_allows_owner_retry(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::sequence()->push(['error' => ['code' => 190]], 401)->push(['message_id' => 'ok'])]);
        $e = $this->event();
        $this->send($e)->assertSessionHas('error');
        $this->assertSame('failed', FacebookReply::first()->status);
        $this->send($e)->assertSessionHas('success');
        Http::assertSentCount(2);
        $this->assertDatabaseCount('facebook_replies', 1);
    }

    public function test_other_business_and_changed_page_cannot_send(): void
    {
        $b = Business::create(['name' => 'Other', 'slug' => 'other-reply', 'trial_ends_at' => now()->addWeek()]);
        $e = $this->event(['business_id' => $b->id]);
        $this->send($e)->assertNotFound();
        $e = $this->event(['page_id' => '5678']);
        $this->send($e)->assertSessionHasErrors('reply');
        Http::assertNothingSent();
    }

    public function test_busy_event_cannot_send(): void
    {
        $e = $this->event(['status' => 'processing']);
        $this->send($e)->assertSessionHasErrors('reply');
        Http::assertNothingSent();
    }
}
