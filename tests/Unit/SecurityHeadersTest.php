<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * Guards the response-header hardening from Phase 8.
 */
class SecurityHeadersTest extends TestCase
{
    private function responseFor(string $url): Response
    {
        $middleware = new SecurityHeaders();

        return $middleware->handle(
            Request::create($url, 'GET'),
            fn () => new Response('ok')
        );
    }

    public function test_baseline_headers_are_present(): void
    {
        $headers = $this->responseFor('http://localhost/')->headers;

        $this->assertSame('nosniff', $headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $headers->get('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $headers->get('Referrer-Policy'));
        $this->assertNotEmpty($headers->get('Permissions-Policy'));
    }

    public function test_csp_locks_down_framing_objects_and_base_uri(): void
    {
        $csp = $this->responseFor('http://localhost/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_csp_allows_only_the_known_script_hosts(): void
    {
        $csp = $this->responseFor('http://localhost/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('https://cdn.jsdelivr.net', $csp);
        $this->assertStringContainsString('https://unpkg.com', $csp);
        $this->assertStringContainsString('https://cdn.tailwindcss.com', $csp);
        // An arbitrary host must not be allowlisted.
        $this->assertStringNotContainsString('evil.example', $csp);
    }

    public function test_hsts_is_sent_only_over_tls(): void
    {
        $this->assertNull($this->responseFor('http://localhost/')->headers->get('Strict-Transport-Security'));
        $this->assertNotNull($this->responseFor('https://localhost/')->headers->get('Strict-Transport-Security'));
    }
}
