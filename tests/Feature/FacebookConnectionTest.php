<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessAutomation;
use App\Models\User;
use App\Services\FacebookPageConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FacebookConnectionTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private array $metaOverrides = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = $this->owner('One');
        $this->other = $this->owner('Two');
        config(['services.facebook.client_id' => '123', 'services.facebook.client_secret' => 'service-secret',
            'services.facebook.config_id' => '456', 'services.facebook.redirect' => 'https://app.example/settings/automation/facebook/callback',
            'services.facebook.version' => 'v24.0', 'services.facebook.cache_store' => 'array']);
        Http::preventStrayRequests();
    }

    private function owner(string $name): User
    {
        $business = Business::create(['name' => $name, 'slug' => strtolower($name), 'trial_ends_at' => now()->addWeek()]);

        return User::create(['business_id' => $business->id, 'name' => $name, 'email' => $name.'@example.test',
            'password' => 'password', 'role' => 'owner', 'is_active' => true, 'is_approved' => true]);
    }

    private function fakeMeta(?array $permissions = null): void
    {
        Http::fake(function ($request) use ($permissions) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if (isset($this->metaOverrides[$path])) {
                return ($this->metaOverrides[$path])($request);
            }

            return match ($path) {
                '/v24.0/oauth/access_token' => Http::response(['access_token' => 'private-user-token']),
                '/v24.0/me/permissions' => Http::response(['data' => $permissions ?? array_map(fn ($p) => ['permission' => $p, 'status' => 'granted'], FacebookPageConnection::PERMISSIONS)]),
                '/v24.0/me/accounts' => Http::response(['data' => [
                    ['id' => '1001', 'name' => 'First Page', 'access_token' => 'private-page-token', 'tasks' => ['MESSAGING', 'MODERATE']],
                    ['id' => '1002', 'name' => '<script>evil()</script>', 'access_token' => 'private-other-page-token', 'tasks' => ['MANAGE']],
                ]]),
                '/v24.0/1001' => Http::response(['id' => '1001', 'name' => 'First Page']),
                default => Http::response(['error' => ['message' => 'provider-secret-error']], 400),
            };
        });
    }

    private function start(): string
    {
        $response = $this->actingAs($this->owner)->get(route('settings.facebook.start'))->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('456', $query['config_id']);
        $this->assertSame('code', $query['response_type']);

        return $query['state'];
    }

    private function consent(): void
    {
        $state = $this->start();
        $this->get(route('settings.facebook.callback', ['state' => $state, 'code' => 'private-code']))
            ->assertRedirect(route('settings.facebook.pages'));
    }

    public function test_missing_configuration_is_explained_and_does_not_contact_meta(): void
    {
        config(['services.facebook.client_secret' => null]);
        $this->actingAs($this->owner)->get(route('settings.automation'))->assertOk()->assertSee('setup pending');
        $this->get(route('settings.facebook.start'))->assertRedirect(route('settings.automation'))->assertSessionHasErrors('facebook');
        Http::assertNothingSent();
        $this->assertDatabaseCount('business_automations', 0);
    }

    public function test_state_is_required_single_use_and_bound_to_user_and_business(): void
    {
        $state = $this->start();
        $this->actingAs($this->other)->get(route('settings.facebook.callback', ['state' => $state, 'code' => 'code']))->assertSessionHasErrors('facebook');
        Http::assertNothingSent();
        $this->fakeMeta();
        $state = $this->start();
        $this->get(route('settings.facebook.callback', ['state' => $state, 'code' => 'code']))->assertRedirect(route('settings.facebook.pages'));
        $this->get(route('settings.facebook.callback', ['state' => $state, 'code' => 'code']))->assertSessionHasErrors('facebook');
        Http::assertSentCount(4);
    }

    public function test_expired_and_malformed_states_fail_without_token_exchange(): void
    {
        $state = $this->start();
        $this->travel(11)->minutes();
        $this->get(route('settings.facebook.callback', ['state' => $state, 'code' => 'code']))->assertSessionHasErrors('facebook');
        $this->travelBack();
        $this->start();
        $this->get(route('settings.facebook.callback', ['state' => ['unexpected'], 'code' => 'code']))->assertSessionHasErrors('facebook');
        Http::assertNothingSent();
    }

    public function test_denial_preserves_existing_connection(): void
    {
        $record = BusinessAutomation::create(['business_id' => $this->owner->business_id,
            'settings' => ['facebook_page_id' => '99'], 'secrets' => ['facebook_page_token' => 'old-token']]);
        $state = $this->start();
        $this->get(route('settings.facebook.callback', ['state' => $state, 'error' => 'access_denied', 'error_description' => '<script>bad</script>']))->assertSessionHasErrors('facebook');
        $this->assertSame('old-token', $record->fresh()->secrets['facebook_page_token']);
        Http::assertNothingSent();
    }

    public function test_page_choice_hides_tokens_and_connects_only_the_selected_tenant(): void
    {
        $this->fakeMeta();
        $other = BusinessAutomation::create(['business_id' => $this->other->business_id, 'secrets' => ['facebook_page_token' => 'untouched']]);
        $this->consent();
        $flow = session('facebook_connect');
        $encrypted = Cache::store('array')->get($flow['cache']);
        $this->assertStringNotContainsString('private-page-token', $encrypted);
        $this->assertStringNotContainsString('private-page-token', json_encode(session()->all()));
        $this->get(route('settings.facebook.pages'))->assertOk()->assertSee('First Page')->assertDontSee('private-page-token')
            ->assertDontSee('<script>evil()</script>', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->post(route('settings.facebook.select'), ['page_id' => '1001', 'business_id' => $this->other->business_id])
            ->assertRedirect(route('settings.automation'));
        $record = BusinessAutomation::where('business_id', $this->owner->business_id)->firstOrFail();
        $this->assertSame('1001', $record->settings['facebook_page_id']);
        $this->assertSame('private-page-token', $record->secrets['facebook_page_token']);
        $this->assertStringNotContainsString('private-page-token', $record->getRawOriginal('secrets'));
        $this->assertFalse($record->enabled);
        $this->assertSame('untouched', $other->fresh()->secrets['facebook_page_token']);
        $this->assertNull(Cache::store('array')->get($flow['cache']));
        $this->post(route('settings.facebook.select'), ['page_id' => '1001'])->assertSessionHasErrors('facebook');
    }

    public function test_tampered_selection_and_expired_selection_do_not_write(): void
    {
        $this->fakeMeta();
        $this->consent();
        $this->post(route('settings.facebook.select'), ['page_id' => '9999'])->assertSessionHasErrors('facebook');
        $this->travel(11)->minutes();
        $this->post(route('settings.facebook.select'), ['page_id' => '1001'])->assertSessionHasErrors('facebook');
        $this->assertDatabaseCount('business_automations', 0);
    }

    public function test_missing_permissions_and_provider_errors_do_not_leak_or_save(): void
    {
        $this->fakeMeta([]);
        $state = $this->start();
        $this->get(route('settings.facebook.callback', ['state' => $state, 'code' => 'code']))->assertSessionHasErrors('facebook');
        $this->metaOverrides['/v24.0/oauth/access_token'] = fn () => Http::response(['error' => ['message' => 'private-provider-token']], 400);
        $state = $this->start();
        $this->get(route('settings.facebook.callback', ['state' => $state, 'code' => 'code']))->assertSessionHasErrors('facebook');
        $this->assertStringNotContainsString('private-provider-token', json_encode(session()->all()));
        $this->assertDatabaseCount('business_automations', 0);
    }

    public function test_saving_other_settings_cannot_overwrite_oauth_metadata_or_page_token(): void
    {
        $this->fakeMeta();
        $this->consent();
        $this->post(route('settings.facebook.select'), ['page_id' => '1001']);
        $this->post(route('settings.automation.update'), ['openrouter_model' => 'provider/model', 'facebook_page_id' => '999',
            'facebook_page_token' => 'forged-token', 'clear_secrets' => ['facebook_page_token']])->assertRedirect();
        $record = BusinessAutomation::where('business_id', $this->owner->business_id)->firstOrFail();
        $this->assertSame('1001', $record->settings['facebook_page_id']);
        $this->assertSame('oauth', $record->settings['facebook_connection_mode']);
        $this->assertSame('private-page-token', $record->secrets['facebook_page_token']);
        $this->get(route('settings.automation'))->assertOk()->assertSee('Connected: First Page')->assertDontSee('private-page-token')->assertDontSee('name="facebook_page_token"', false);
    }

    public function test_disconnect_preserves_other_integrations_and_businesses(): void
    {
        $record = BusinessAutomation::create(['business_id' => $this->owner->business_id, 'enabled' => true, 'token_hash' => hash('sha256', 'erp-token'),
            'abilities' => ['catalog:read'], 'settings' => ['facebook_connection_mode' => 'oauth', 'facebook_page_id' => '1001', 'openrouter_model' => 'free/model'],
            'secrets' => ['facebook_page_token' => 'remove-me', 'telegram_bot_token' => 'keep-me']]);
        $other = BusinessAutomation::create(['business_id' => $this->other->business_id, 'secrets' => ['facebook_page_token' => 'other-token']]);
        $this->actingAs($this->owner)->delete(route('settings.facebook.disconnect'))->assertRedirect();
        $record->refresh();
        $this->assertArrayNotHasKey('facebook_page_token', $record->secrets);
        $this->assertArrayNotHasKey('facebook_page_id', $record->settings);
        $this->assertSame('keep-me', $record->secrets['telegram_bot_token']);
        $this->assertSame('free/model', $record->settings['openrouter_model']);
        $this->assertTrue($record->enabled);
        $this->assertSame(hash('sha256', 'erp-token'), $record->token_hash);
        $this->assertSame('other-token', $other->fresh()->secrets['facebook_page_token']);
        Http::assertNothingSent();
    }

    public function test_staff_cannot_start_select_check_or_disconnect(): void
    {
        $this->owner->update(['role' => 'staff', 'permissions' => ['settings']]);
        $this->actingAs($this->owner)->get(route('settings.facebook.start'))->assertForbidden();
        $this->get(route('settings.facebook.callback'))->assertForbidden();
        $this->get(route('settings.facebook.pages'))->assertForbidden();
        $this->post(route('settings.facebook.select'), ['page_id' => '1001'])->assertForbidden();
        $this->post(route('settings.facebook.check'))->assertForbidden();
        $this->delete(route('settings.facebook.disconnect'))->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_page_verification_failure_does_not_replace_existing_token(): void
    {
        $record = BusinessAutomation::create(['business_id' => $this->owner->business_id, 'secrets' => ['facebook_page_token' => 'existing-token']]);
        $this->fakeMeta();
        $this->consent();
        $this->metaOverrides['/v24.0/1001'] = fn () => Http::response(['id' => 'different-page', 'name' => 'Unexpected Page']);
        $this->post(route('settings.facebook.select'), ['page_id' => '1001'])->assertSessionHasErrors('facebook');
        $this->assertSame('existing-token', $record->fresh()->secrets['facebook_page_token']);
    }

    public function test_access_check_does_not_send_messages_and_preserves_settings(): void
    {
        $this->fakeMeta();
        $this->consent();
        $this->post(route('settings.facebook.select'), ['page_id' => '1001']);
        $this->post(route('settings.facebook.check'))->assertRedirect()->assertSessionHas('success');
        Http::assertSent(fn ($r) => str_contains($r->url(), '/1001?') && $r->method() === 'GET' && $r->hasHeader('Authorization', 'Bearer private-page-token'));
        $this->metaOverrides['/v24.0/1001'] = fn () => Http::response([], 503);
        $this->post(route('settings.facebook.check'))->assertSessionHasErrors('facebook');
        $this->assertSame('private-page-token', BusinessAutomation::firstOrFail()->secrets['facebook_page_token']);
    }

    public function test_pagination_uses_only_fixed_graph_host_and_preserves_all_pages(): void
    {
        $this->fakeMeta();
        $this->metaOverrides['/v24.0/me/accounts'] = Http::sequence()
            ->push(['data' => [['id' => '1001', 'name' => 'First', 'access_token' => 'private-page-token', 'tasks' => ['MANAGE']]],
                'paging' => ['next' => 'https://untrusted.example/steal', 'cursors' => ['after' => 'next-cursor']]])
            ->push(['data' => [['id' => '1002', 'name' => 'Second', 'access_token' => 'second-token', 'tasks' => ['MANAGE']]]]);
        $this->consent();
        $this->get(route('settings.facebook.pages'))->assertOk()->assertSee('First')->assertSee('Second');
        Http::assertNotSent(fn ($r) => parse_url($r->url(), PHP_URL_HOST) !== 'graph.facebook.com');
    }

    public function test_new_pages_experience_tasks_are_supported_without_allowing_read_only_access(): void
    {
        $this->fakeMeta();
        $this->metaOverrides['/v24.0/me/accounts'] = fn () => Http::response(['data' => [
            ['id' => '2001', 'name' => 'Modern messaging page', 'access_token' => 'modern-token', 'tasks' => ['PROFILE_PLUS_MESSAGING', 'PROFILE_PLUS_MODERATE']],
            ['id' => '2002', 'name' => 'Full control page', 'access_token' => 'full-token', 'tasks' => ['PROFILE_PLUS_FULL_CONTROL']],
            ['id' => '2003', 'name' => 'Read only page', 'access_token' => 'read-token', 'tasks' => ['PROFILE_PLUS_ANALYZE', 'PROFILE_PLUS_ADVERTISE', 'PROFILE_PLUS_FACEBOOK_ACCESS']],
            ['id' => '2004', 'name' => 'Messaging only page', 'access_token' => 'message-token', 'tasks' => ['PROFILE_PLUS_MESSAGING']],
            ['id' => '2005', 'name' => 'Malformed tasks page', 'access_token' => 'malformed-token', 'tasks' => 'MANAGE'],
        ]]);
        $this->consent();
        $this->get(route('settings.facebook.pages'))->assertOk()->assertSee('Modern messaging page')->assertSee('Full control page')
            ->assertDontSee('Read only page')->assertDontSee('Messaging only page')->assertDontSee('Malformed tasks page')->assertDontSee('modern-token');
        $this->metaOverrides['/v24.0/2001'] = fn () => Http::response(['id' => '2001', 'name' => 'Modern messaging page']);
        $this->post(route('settings.facebook.select'), ['page_id' => '2001'])->assertRedirect(route('settings.automation'));
        $this->assertSame('2001', BusinessAutomation::firstOrFail()->settings['facebook_page_id']);
    }

    public function test_empty_missing_token_and_insufficient_tasks_have_distinct_safe_errors(): void
    {
        $this->fakeMeta();
        foreach ([
            [[], 'FB_PAGES_EMPTY'],
            [[['id' => '1001', 'name' => 'Page', 'tasks' => ['MANAGE']]], 'FB_PAGE_TOKEN_MISSING'],
            [[['id' => '1001', 'name' => 'Private Page Name', 'access_token' => 'private-token', 'tasks' => ['ANALYZE']]], 'FB_PAGE_TASKS_INSUFFICIENT'],
        ] as [$pages,$code]) {
            $this->metaOverrides['/v24.0/me/accounts'] = fn () => Http::response(['data' => $pages]);
            $state = $this->start();
            $this->get(route('settings.facebook.callback', ['state' => $state, 'code' => 'oauth-code']))->assertSessionHasErrors('facebook');
            $error = session('errors')->first('facebook');
            $this->assertStringContainsString($code, $error);
            $this->assertStringNotContainsString('private-token', $error);
            $this->assertStringNotContainsString('Private Page Name', $error);
        }
        $this->assertDatabaseCount('business_automations', 0);
    }
}
