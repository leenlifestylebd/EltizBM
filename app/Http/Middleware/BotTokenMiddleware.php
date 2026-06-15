<?php

namespace App\Http\Middleware;

use App\Models\Business;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the SufiAI bot (n8n) by a static bearer token and pins the
 * request to a single ERP business. No session, no DB token table — the token
 * lives in config/services.php (env BOT_API_TOKEN).
 */
class BotTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.bot.token');
        $provided = $request->bearerToken() ?: (string) $request->header('X-Bot-Token');

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json([
                'error' => ['code' => 'unauthorized', 'message' => 'Invalid or missing bot token.'],
            ], 401);
        }

        $businessId = config('services.bot.business_id') ?: Business::min('id');

        if (! $businessId) {
            return response()->json([
                'error' => ['code' => 'no_business', 'message' => 'No business configured for the bot.'],
            ], 500);
        }

        $request->attributes->set('business_id', (int) $businessId);

        return $next($request);
    }
}
