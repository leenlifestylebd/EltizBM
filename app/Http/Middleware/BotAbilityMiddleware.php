<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BotAbilityMiddleware
{
    public function handle(Request $request, Closure $next, string $ability)
    {
        $record = $request->attributes->get('bot_automation');
        if (! $record || ! in_array($ability, $record->abilities ?? [], true)) {
            return response()->json(['error' => ['code' => 'forbidden', 'message' => 'This API token does not allow this action.']], 403);
        }

        return $next($request);
    }
}
