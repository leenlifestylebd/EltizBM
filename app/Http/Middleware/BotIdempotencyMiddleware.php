<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BotIdempotencyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = (string) $request->header('Idempotency-Key');
        if (! preg_match('/^[a-zA-Z0-9_.:\-]{8,128}$/D', $key)) {
            return response()->json(['error' => ['code' => 'idempotency_key_required', 'message' => 'Supply an 8–128 character Idempotency-Key for every write.']], 422);
        }
        $businessId = $request->attributes->get('business_id');
        $hash = hash('sha256', $request->method().' '.$request->path().' '.$request->getContent());

        return DB::transaction(function () use ($request, $next, $businessId, $key, $hash) {
            // Serialize API writes per business, including stock checks and saved responses.
            DB::table('businesses')->where('id', $businessId)->lockForUpdate()->first();
            $prior = DB::table('bot_api_requests')->where('business_id', $businessId)->where('request_key', $key)->first();
            if ($prior) {
                if (! hash_equals($prior->payload_hash, $hash)) {
                    return response()->json(['error' => ['code' => 'idempotency_conflict', 'message' => 'This key was used with a different request.']], 409);
                }

                return response($prior->response, $prior->status)->header('Content-Type', 'application/json')->header('Idempotency-Replayed', 'true');
            }
            $response = $next($request);
            if ($response->isSuccessful()) {
                DB::table('bot_api_requests')->insert([
                    'business_id' => $businessId, 'request_key' => $key, 'payload_hash' => $hash,
                    'response' => $response->getContent(), 'status' => $response->getStatusCode(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            return $response;
        });
    }
}
