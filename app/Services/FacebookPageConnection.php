<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FacebookPageConnection
{
    public const PERMISSIONS = ['pages_show_list', 'pages_read_engagement', 'pages_read_user_content', 'pages_manage_engagement', 'pages_messaging', 'pages_manage_metadata'];

    public function ready(): bool
    {
        return ctype_digit((string) config('services.facebook.client_id'))
            && filled(config('services.facebook.client_secret'))
            && ctype_digit((string) config('services.facebook.config_id'))
            && preg_match('/^v\d+\.\d+$/', (string) config('services.facebook.version'))
            && str_starts_with((string) config('services.facebook.redirect'), 'https://');
    }

    public function loginUrl(string $state): string
    {
        return 'https://www.facebook.com/'.config('services.facebook.version').'/dialog/oauth?'.http_build_query([
            'client_id' => config('services.facebook.client_id'),
            'redirect_uri' => config('services.facebook.redirect'),
            'config_id' => config('services.facebook.config_id'),
            'response_type' => 'code', 'override_default_response_type' => 'true', 'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function get(string $path, array $query = [], ?string $token = null): array
    {
        $http = Http::acceptJson()->connectTimeout(5)->timeout(20)->withoutRedirecting();
        if ($token !== null) {
            $http = $http->withToken($token);
            $query['appsecret_proof'] = hash_hmac('sha256', $token, config('services.facebook.client_secret'));
        }
        // Never throw an HTTP exception containing token-bearing URLs or Meta's response body.
        try {
            $response = $http->get('https://graph.facebook.com/'.config('services.facebook.version').'/'.$path, $query);
        } catch (ConnectionException) {
            throw new RuntimeException('Facebook could not be reached. Please try again.');
        }
        if (! $response->successful() || ! is_array($response->json()) || isset($response->json()['error'])) {
            throw new RuntimeException('Facebook access could not be verified. Reconnect and allow the requested Page permissions.');
        }

        return $response->json();
    }

    public function pages(string $code): array
    {
        $credentials = ['client_id' => config('services.facebook.client_id'), 'client_secret' => config('services.facebook.client_secret')];
        $short = $this->get('oauth/access_token', $credentials + ['redirect_uri' => config('services.facebook.redirect'), 'code' => $code]);
        if (empty($short['access_token']) || ! is_string($short['access_token'])) {
            throw new RuntimeException('Facebook did not return an access token. Please reconnect.');
        }
        $long = $this->get('oauth/access_token', $credentials + ['grant_type' => 'fb_exchange_token', 'fb_exchange_token' => $short['access_token']]);
        $token = $long['access_token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Facebook did not confirm extended access. Please reconnect.');
        }
        $permissions = $this->get('me/permissions', [], $token);
        $granted = collect($permissions['data'] ?? [])->where('status', 'granted')->pluck('permission')->all();
        if (array_diff(self::PERMISSIONS, $granted)) {
            throw new RuntimeException('Some required Page permissions were not granted. Reconnect and allow messages and comments, or contact the service owner about Meta approval.');
        }
        $pages = [];
        $returned = 0;
        $missingToken = 0;
        $after = null;
        for ($batch = 0; $batch < 20; $batch++) {
            $data = $this->get('me/accounts', array_filter(['fields' => 'id,name,access_token,tasks', 'limit' => 100, 'after' => $after]), $token);
            foreach ($data['data'] ?? [] as $page) {
                $returned++;
                if (is_array($page) && empty($page['access_token'])) {
                    $missingToken++;
                }
                if (! is_array($page) || ! ctype_digit((string) ($page['id'] ?? '')) || empty($page['name']) || empty($page['access_token'])) {
                    continue;
                }
                if (! $this->hasMessagingAndModerationAccess($page['tasks'] ?? null)) {
                    continue;
                }
                $pages[(string) $page['id']] = ['id' => (string) $page['id'], 'name' => (string) $page['name'], 'token' => (string) $page['access_token']];
            }
            if (empty($data['paging']['next'])) {
                if (! $pages) {
                    if ($returned === 0) {
                        throw new RuntimeException('Facebook returned an empty Page list. Reconnect and review the Pages shared with this app. If the Page belongs to a Business Portfolio, ask the service owner to check its asset access and login configuration. [FB_PAGES_EMPTY]');
                    }
                    if ($missingToken === $returned) {
                        throw new RuntimeException('Facebook returned Pages without Page access tokens. Check your Page access and the Pages shared with this app, then reconnect. [FB_PAGE_TOKEN_MISSING]');
                    }
                    throw new RuntimeException('Facebook returned Pages, but none included both messaging and comment-moderation access. Check your Page tasks and reconnect. [FB_PAGE_TASKS_INSUFFICIENT]');
                }

                return array_values($pages);
            }
            // Ignore Meta's next URL: only send credentials to our fixed Graph host.
            $next = $data['paging']['cursors']['after'] ?? null;
            if (! is_string($next) || $next === '' || $next === $after) {
                break;
            }
            $after = $next;
        }
        throw new RuntimeException('The Page list could not be completed. Retry with fewer selected Pages.');
    }

    private function hasMessagingAndModerationAccess(mixed $tasks): bool
    {
        if (! is_array($tasks)) {
            return false;
        }
        // Meta's New Pages Experience uses PROFILE_PLUS_* task names.
        // Never treat generic Facebook access, ads or analytics as write access.
        if (in_array('MANAGE', $tasks, true) || in_array('PROFILE_PLUS_FULL_CONTROL', $tasks, true)) {
            return true;
        }
        $messages = in_array('MESSAGING', $tasks, true) || in_array('PROFILE_PLUS_MESSAGING', $tasks, true);
        $comments = in_array('MODERATE', $tasks, true) || in_array('PROFILE_PLUS_MODERATE', $tasks, true);

        return $messages && $comments;
    }

    public function subscribe(string $id, string $token): void
    {
        $this->verifyPage($id, $token);
        $current = $this->get($id.'/subscribed_apps', ['fields' => 'id,subscribed_fields'], $token);
        $fields = ['messages', 'feed'];
        foreach ($current['data'] ?? [] as $app) {
            if ((string) ($app['id'] ?? '') === (string) config('services.facebook.client_id')) {
                $fields = array_merge($fields, is_array($app['subscribed_fields'] ?? null) ? $app['subscribed_fields'] : []);
            }
        }
        try {
            $response = Http::acceptJson()->asForm()->withToken($token)->connectTimeout(5)->timeout(20)->withoutRedirecting()
                ->post('https://graph.facebook.com/'.config('services.facebook.version').'/'.$id.'/subscribed_apps', [
                    'subscribed_fields' => implode(',', array_unique($fields)),
                    'appsecret_proof' => hash_hmac('sha256', $token, config('services.facebook.client_secret')),
                ]);
        } catch (ConnectionException) {
            throw new RuntimeException('Facebook subscription could not be confirmed. Please try again.');
        }
        if (! $response->successful() || $response->json('success') !== true) {
            throw new RuntimeException('Facebook did not confirm the subscription. Check the Meta webhook configuration and Page permissions.');
        }
    }

    public function verifyPage(string $id, string $token): array
    {
        if (! ctype_digit($id)) {
            throw new RuntimeException('Invalid Page. Please reconnect.');
        }
        $page = $this->get($id, ['fields' => 'id,name'], $token);
        if ((string) ($page['id'] ?? '') !== $id || ! is_string($page['name'] ?? null)) {
            throw new RuntimeException('Facebook returned a different Page. Please reconnect.');
        }

        return ['id' => $id, 'name' => $page['name']];
    }
}
