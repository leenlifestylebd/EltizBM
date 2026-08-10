<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers.
 *
 * The CSP is deliberately permissive about inline code: the UI is built on
 * CDN Tailwind + Alpine (which needs 'unsafe-eval'), 40 inline <script> blocks
 * and 300+ inline style attributes, so 'unsafe-inline'/'unsafe-eval' must stay
 * or every page breaks. It still earns its place by pinning script/style to a
 * known host allowlist, forbidding plugins and framing, and locking base-uri
 * and form-action — an injected <script src="//evil"> is refused.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://cdn.tailwindcss.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
            "font-src 'self' data: https://fonts.gstatic.com",
            // Product images can be remote (WooCommerce), so allow any https image.
            "img-src 'self' data: blob: https:",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
            "form-action 'self'",
            // Keep the directive the edge (Cloudflare/LiteSpeed) already sends, so
            // overriding its header below loses nothing.
            'upgrade-insecure-requests',
        ]);

        $headers = [
            'Content-Security-Policy' => $csp,
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'SAMEORIGIN',
            'Referrer-Policy'         => 'strict-origin-when-cross-origin',
            'Permissions-Policy'      => 'geolocation=(), microphone=(), camera=(), payment=()',
        ];

        // Only advertise HSTS over a real TLS connection — sending it on plain HTTP
        // is ignored by browsers and would lock out a local http:// dev host.
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        // The edge already emits a bare "Content-Security-Policy:
        // upgrade-insecure-requests". Verified live on app.leen.com.bd: skipping
        // headers that already exist meant our real policy was silently dropped, so
        // the CSP is set unconditionally (our value re-includes that directive).
        // The rest are only filled in when absent.
        $response->headers->set('Content-Security-Policy', $headers['Content-Security-Policy']);
        unset($headers['Content-Security-Policy']);

        foreach ($headers as $key => $value) {
            if (!$response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
