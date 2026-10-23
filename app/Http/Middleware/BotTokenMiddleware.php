<?php

namespace App\Http\Middleware;

use App\Models\Business;
use App\Models\BusinessAutomation;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class BotTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        $provided = (string) ($request->bearerToken() ?: $request->header('X-Bot-Token'));
        if ($provided === '' || strlen($provided) > 512) {
            return response()->json(['error' => ['code' => 'unauthorized', 'message' => 'Invalid or missing bot token.']], 401);
        }
        $record = BusinessAutomation::where('token_hash', hash('sha256', $provided))->where('enabled', true)->first();
        $actor = $record ? User::where('id', $record->issued_by)->where('business_id', $record->business_id)
            ->where('is_active', true)->whereIn('role', ['owner', 'admin'])->first() : null;
        if (! $record || ! $actor || ! Business::whereKey($record->business_id)->exists()) {
            return response()->json(['error' => ['code' => 'unauthorized', 'message' => 'Invalid, disabled or revoked bot token.']], 401);
        }
        $request->attributes->set('business_id', (int) $record->business_id);
        $request->attributes->set('bot_actor_id', (int) $actor->id);
        $request->attributes->set('bot_automation', $record);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
