<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FacebookInboxEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FacebookInboxController extends Controller
{
    public function claim(Request $request)
    {
        $business = $request->attributes->get('business_id');
        $connection = $request->attributes->get('bot_automation');
        if (empty($connection->settings['facebook_receive_enabled']) || empty($connection->secrets['facebook_page_token'])) {
            return response()->json(['event' => null]);
        }

        return DB::transaction(function () use ($business, $connection) {
            DB::table('businesses')->where('id', $business)->lockForUpdate()->first();
            FacebookInboxEvent::where('business_id', $business)->where('status', 'processing')->where('lease_until', '<', now())->where('attempts', '>=', 3)
                ->update(['status' => 'handover', 'reason' => 'AI processing did not finish after three attempts.', 'lease_hash' => null, 'lease_until' => null]);
            $event = FacebookInboxEvent::where('business_id', $business)->where('page_id', $connection->settings['facebook_page_id'])
                ->where('attempts', '<', 3)->where(fn ($q) => $q->where('status', 'queued')->orWhere(fn ($q) => $q->where('status', 'processing')->where('lease_until', '<', now())))
                ->orderBy('id')->lockForUpdate()->first();
            if (! $event) {
                return response()->json(['event' => null]);
            }
            $lease = bin2hex(random_bytes(32));
            $event->update(['status' => 'processing', 'attempts' => $event->attempts + 1, 'lease_hash' => hash('sha256', $lease), 'lease_until' => now()->addMinutes(10)]);

            $mapping = app(\App\Services\FacebookAutomaticReply::class)->context($event);
            $product = $mapping?->product;
            $context = $product ? ['id'=>$product->id,'name'=>$product->name,'sku'=>$product->sku,'price'=>(float)$product->current_price,'in_stock'=>!$product->track_stock || (int)$product->stock>0] : null;
            return response()->json(['event' => ['id' => $event->id, 'kind' => $event->kind, 'page_id' => $event->page_id, 'payload' => $event->payload, 'product_context'=>$context], 'lease_token' => $lease]);
        });
    }

    public function draft(Request $request, int $id)
    {
        $data = $request->validate(['lease_token' => 'required|string|size:64', 'decision' => ['required', Rule::in(['review', 'handover'])],
            'draft' => 'nullable|string|max:2000', 'reason' => 'required|string|max:500', 'confidence' => 'required|numeric|min:0|max:1']);

        return DB::transaction(function () use ($request, $id, $data) {
            $event = FacebookInboxEvent::where('business_id', $request->attributes->get('business_id'))->whereKey($id)->lockForUpdate()->firstOrFail();
            abort_unless($event->status === 'processing' && $event->lease_until?->isFuture() && hash_equals($event->lease_hash ?? '', hash('sha256', $data['lease_token'])), 409);
            $status = $data['decision'] === 'review' && $data['confidence'] >= 0.85 && ! empty($data['draft']) ? 'review' : 'handover';
            $event->update(['status' => $status, 'draft' => $data['draft'] ?? null, 'reason' => $data['reason'], 'lease_hash' => null, 'lease_until' => null]);

            return response()->json(['id' => $event->id, 'status' => $event->status, 'sent' => false]);
        });
    }
    public function automatic(Request $request, int $id, \App\Services\FacebookAutomaticReply $replies) { return response()->json($replies->attempt((int)$request->attributes->get('business_id'),$id)); }
}
