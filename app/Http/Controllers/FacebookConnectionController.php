<?php

namespace App\Http\Controllers;

use App\Models\BusinessAutomation;
use App\Models\FacebookInboxEvent;
use App\Services\FacebookPageConnection;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FacebookConnectionController extends Controller
{
    private const SESSION_KEY = 'facebook_connect';

    private function back(string $message, bool $error = false)
    {
        $response = redirect()->route('settings.automation');

        return ($error ? $response->withErrors(['facebook' => $message]) : $response->with('success', $message))
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    private function clear(Request $request): void
    {
        $flow = $request->session()->pull(self::SESSION_KEY, []);
        if (! empty($flow['cache'])) {
            Cache::store(config('services.facebook.cache_store'))->forget($flow['cache']);
        }
    }

    private function flow(Request $request): ?array
    {
        $flow = $request->session()->get(self::SESSION_KEY);
        if (! is_array($flow) || ($flow['user'] ?? null) !== $request->user()->id
            || ($flow['business'] ?? null) !== $request->user()->business_id
            || ($flow['expires'] ?? 0) <= now()->timestamp) {
            $this->clear($request);

            return null;
        }

        return $flow;
    }

    public function start(Request $request, FacebookPageConnection $facebook)
    {
        $this->clear($request);
        if (! $facebook->ready()) {
            return $this->back('Facebook connection is awaiting service-owner setup. Your existing settings have not changed.', true);
        }
        $state = bin2hex(random_bytes(32));
        $request->session()->put(self::SESSION_KEY, [
            'state' => $state, 'user' => $request->user()->id, 'business' => $request->user()->business_id,
            'expires' => now()->addMinutes(10)->timestamp,
        ]);

        return redirect()->away($facebook->loginUrl($state))->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function callback(Request $request, FacebookPageConnection $facebook)
    {
        $flow = $this->flow($request);
        $state = $request->query('state');
        if (! $flow || ! is_string($state) || empty($flow['state']) || ! hash_equals($flow['state'], $state)) {
            $this->clear($request);

            return $this->back('The connection request expired or did not match this account. Please start again.', true);
        }
        // Consume state before any network request, including cancellation or failure.
        unset($flow['state']);
        $request->session()->put(self::SESSION_KEY, $flow);
        $code = $request->query('code');
        if ($request->has('error') || ! is_string($code) || $code === '' || strlen($code) > 4096 || ! $facebook->ready()) {
            $this->clear($request);

            return $this->back('Facebook connection was not completed. Your existing connection is unchanged.', true);
        }
        try {
            $pages = $facebook->pages($code);
        } catch (RuntimeException $exception) {
            $this->clear($request);

            return $this->back($exception->getMessage(), true);
        }
        if (! $pages) {
            $this->clear($request);

            return $this->back('No eligible Pages were returned. Check your Page access and select Pages with message and comment permissions on Facebook.', true);
        }
        $flow['cache'] = 'facebook-connect:'.bin2hex(random_bytes(32));
        Cache::store(config('services.facebook.cache_store'))->put($flow['cache'], Crypt::encryptString(json_encode($pages, JSON_THROW_ON_ERROR)), now()->addMinutes(10));
        $request->session()->put(self::SESSION_KEY, $flow);

        return redirect()->route('settings.facebook.pages')->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    private function candidates(Request $request): ?array
    {
        $flow = $this->flow($request);
        if (! $flow || empty($flow['cache'])) {
            return null;
        }
        $encrypted = Cache::store(config('services.facebook.cache_store'))->get($flow['cache']);
        if (! is_string($encrypted)) {
            return null;
        }
        try {
            return json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            return null;
        }
    }

    public function pages(Request $request)
    {
        $pages = $this->candidates($request);
        if (! $pages) {
            return $this->back('Your Page selection expired. Connect Facebook again.', true);
        }

        return response()->view('settings.facebook-pages', ['pages' => array_map(fn ($p) => ['id' => $p['id'], 'name' => $p['name']], $pages)])
            ->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
    }

    public function select(Request $request, FacebookPageConnection $facebook)
    {
        $pages = $this->candidates($request);
        $id = $request->input('page_id');
        $selected = is_string($id) ? collect($pages ?? [])->firstWhere('id', $id) : null;
        if (! $selected || ! $facebook->ready()) {
            return $this->back('Choose a Page from your current Facebook connection. If it expired, connect again.', true);
        }
        try {
            $page = $facebook->verifyPage($selected['id'], $selected['token']);
        } catch (RuntimeException $exception) {
            return $this->back($exception->getMessage(), true);
        }
        DB::transaction(function () use ($request, $selected, $page) {
            DB::table('businesses')->where('id', $request->user()->business_id)->lockForUpdate()->first();
            $record = BusinessAutomation::firstOrNew(['business_id' => $request->user()->business_id]);
            $record->settings = array_merge($record->settings ?? [], [
                'facebook_page_id' => $page['id'], 'facebook_page_name' => $page['name'],
                'facebook_connection_mode' => 'oauth', 'facebook_connection_status' => 'connected',
                'facebook_receive_enabled' => ($record->settings['facebook_page_id'] ?? null) === $page['id'] ? ($record->settings['facebook_receive_enabled'] ?? false) : false,
                'facebook_connected_at' => now()->toIso8601String(), 'facebook_checked_at' => now()->toIso8601String(),
            ]);
            $record->secrets = array_merge($record->secrets ?? [], ['facebook_page_token' => $selected['token']]);
            $record->save();
        });
        $this->clear($request);

        return $this->back('Facebook Page connected. Automatic replies remain off until your automation service is configured.');
    }

    public function check(Request $request, FacebookPageConnection $facebook)
    {
        $record = BusinessAutomation::where('business_id', $request->user()->business_id)->first();
        $settings = $record?->settings ?? [];
        $token = $record?->secrets['facebook_page_token'] ?? null;
        if (($settings['facebook_connection_mode'] ?? '') !== 'oauth' || ! $token || ! $facebook->ready()) {
            return $this->back('Connect Facebook before checking Page access.', true);
        }
        try {
            $page = $facebook->verifyPage($settings['facebook_page_id'], $token);
        } catch (RuntimeException) {
            return $this->back('Page access could not be verified. Facebook may be unavailable or permissions may have changed. Try again or reconnect.', true);
        }
        DB::transaction(function () use ($record, $token, $page) {
            DB::table('businesses')->where('id', $record->business_id)->lockForUpdate()->first();
            $fresh = $record->fresh();
            if (($fresh?->secrets['facebook_page_token'] ?? null) !== $token) {
                return;
            }
            $fresh->settings = array_merge($fresh->settings ?? [], ['facebook_page_name' => $page['name'], 'facebook_checked_at' => now()->toIso8601String()]);
            $fresh->save();
        });

        return $this->back('Page access verified. This check does not send messages or test automatic replies.');
    }

    public function subscribe(Request $request, FacebookPageConnection $facebook)
    {
        if (! $facebook->ready() || strlen((string) config('services.facebook.webhook_verify_token')) < 32) {
            return $this->back('The service owner must configure the Facebook webhook first.', true);
        }
        try {
            return Cache::store(config('services.facebook.cache_store'))->lock('facebook-page-routing', 60)->block(5, function () use ($request, $facebook) {
                return DB::transaction(function () use ($request, $facebook) {
                    DB::table('businesses')->where('id', $request->user()->business_id)->lockForUpdate()->first();
                    $record = BusinessAutomation::where('business_id', $request->user()->business_id)->first();
                    $page = $record?->settings['facebook_page_id'] ?? null;
                    $token = $record?->secrets['facebook_page_token'] ?? null;
                    if (! $page || ! $token) {
                        return $this->back('Connect a Facebook Page first.', true);
                    }
                    $duplicate = BusinessAutomation::where('business_id', '!=', $record->business_id)->where('settings->facebook_page_id', $page)->where('settings->facebook_receive_enabled', true)->exists();
                    if ($duplicate) {
                        return $this->back('This Page already receives events for another business. Pause that connection first.', true);
                    }
                    $facebook->subscribe($page, $token);
                    $record->settings = array_merge($record->settings ?? [], ['facebook_receive_enabled' => true]);
                    $record->save();

                    return $this->back('Facebook subscription confirmed. New messages and comments can enter the review inbox once Meta webhook setup is complete. No replies are sent automatically.');
                });
            });
        } catch (RuntimeException|LockTimeoutException $e) {
            return $this->back('Facebook subscription could not be completed. Check the webhook setup and Page permissions, then retry.', true);
        }
    }

    public function pause(Request $request)
    {
        DB::transaction(function () use ($request) {
            DB::table('businesses')->where('id', $request->user()->business_id)->lockForUpdate()->first();
            $record = BusinessAutomation::where('business_id', $request->user()->business_id)->first();
            if ($record) {
                $record->settings = array_merge($record->settings ?? [], ['facebook_receive_enabled' => false]);
                $record->save();
            }
        });

        return $this->back('Facebook intake paused. Saved messages remain available for review.');
    }

    public function inbox(Request $request)
    {
        $events = FacebookInboxEvent::where('business_id', $request->user()->business_id)->latest('id')->paginate(20);

        return response()->view('settings.facebook-inbox', ['events' => $events])->header('Cache-Control', 'no-store, private');
    }

    public function disconnect(Request $request)
    {
        DB::transaction(function () use ($request) {
            DB::table('businesses')->where('id', $request->user()->business_id)->lockForUpdate()->first();
            $record = BusinessAutomation::where('business_id', $request->user()->business_id)->first();
            if (! $record) {
                return;
            }
            $settings = $record->settings ?? [];
            foreach (['facebook_receive_enabled', 'facebook_page_id', 'facebook_page_name', 'facebook_connection_mode', 'facebook_connection_status', 'facebook_connected_at', 'facebook_checked_at'] as $key) {
                unset($settings[$key]);
            }
            $secrets = $record->secrets ?? [];
            unset($secrets['facebook_page_token']);
            $record->fill(['settings' => $settings, 'secrets' => $secrets])->save();
        });
        $this->clear($request);

        return $this->back('Facebook disconnected from this business. You can also remove the app in Facebook Business Integrations. Other businesses and their settings are unchanged.');
    }
}
