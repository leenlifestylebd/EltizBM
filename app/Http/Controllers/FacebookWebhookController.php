<?php

namespace App\Http\Controllers;

use App\Models\BusinessAutomation;
use App\Models\FacebookInboxEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacebookWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $expected = config('services.facebook.webhook_verify_token');
        $provided = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));
        abort_unless(is_string($expected) && strlen($expected) >= 32 && is_string($provided) && hash_equals($expected, $provided) && $mode === 'subscribe' && is_string($challenge) && strlen($challenge) <= 512, 403);

        return response($challenge)->header('Content-Type', 'text/plain')->header('Cache-Control', 'no-store');
    }

    public function receive(Request $request)
    {
        $raw = $request->getContent();
        abort_if(strlen($raw) > 2097152, 413);
        $secret = config('services.facebook.client_secret');
        $signature = $request->header('X-Hub-Signature-256');
        abort_unless(is_string($secret) && $secret !== '' && is_string($signature) && hash_equals('sha256='.hash_hmac('sha256', $raw, $secret), $signature), 403);
        try {
            $body = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            abort(400);
        }
        abort_unless(is_array($body), 400);
        if (($body['object'] ?? '') !== 'page') {
            return response()->json(['received' => true]);
        }
        $entries = $body['entry'] ?? [];
        abort_unless(is_array($entries) && count($entries) <= 100, 400);
        DB::transaction(function () use ($entries) {
            foreach ($entries as $entry) {
                if (! is_array($entry) || ! is_scalar($entry['id'] ?? null) || strlen((string) $entry['id']) > 64 || ! ctype_digit((string) $entry['id'])) {
                    continue;
                }
                $page = (string) $entry['id'];
                $matches = BusinessAutomation::where('settings->facebook_page_id', $page)->where('settings->facebook_receive_enabled', true)->get();
                // Ambiguous Page ownership must never leak events across clients.
                if ($matches->count() !== 1 || empty($matches[0]->secrets['facebook_page_token'])) {
                    continue;
                }
                $record = $matches[0];
                foreach ((is_array($entry['messaging'] ?? null) ? $entry['messaging'] : []) as $item) {
                    if (! is_array($item) || ! empty($item['message']['is_echo']) || (string) ($item['sender']['id'] ?? '') === $page) {
                        continue;
                    }
                    if ((string) ($item['recipient']['id'] ?? '') !== $page) {
                        continue;
                    }
                    $sender = (string) ($item['sender']['id'] ?? '');
                    $ad = (string) ($item['message']['referral']['ad_id'] ?? $item['referral']['ad_id'] ?? '');
                    if (ctype_digit($sender) && strlen($sender) <= 64) {
                        $key = ['business_id' => $record->business_id, 'page_id' => $page, 'sender_hash' => hash('sha256', $sender)];
                        if (ctype_digit($ad) && strlen($ad) <= 64) {
                            DB::table('facebook_conversation_contexts')->updateOrInsert($key, ['ad_id' => $ad, 'seen_at' => now()]);
                        } elseif ($ad === '') {
                            $ad = (string) (DB::table('facebook_conversation_contexts')->where($key)->where('seen_at', '>=', now()->subHours(24))->value('ad_id') ?? '');
                        }
                    }

                    if (isset($item['message']['mid'])) {
                        $text = $item['message']['text'] ?? '';
                        $this->store($record, $page, 'message', (string) $item['message']['mid'], [
                            'text' => is_string($text) ? mb_substr($text, 0, 8000) : '',
                            'sender_id' => (string) ($item['sender']['id'] ?? ''),
                            'ad_id' => $ad,
                            'occurred_at' => is_numeric($item['timestamp'] ?? null) ? (int) floor((float) $item['timestamp'] / 1000) : null,
                            'post_id' => '', 'external_id' => (string) $item['message']['mid'],
                        ]);
                    }
                }
                foreach ((is_array($entry['changes'] ?? null) ? $entry['changes'] : []) as $change) {
                    if (! is_array($change) || ! is_array($change['value'] ?? null)) {
                        continue;
                    }
                    $v = $change['value'];
                    if (($change['field'] ?? '') !== 'feed' || ($v['item'] ?? '') !== 'comment' || ($v['verb'] ?? '') !== 'add' || empty($v['comment_id']) || (string) ($v['from']['id'] ?? '') === $page) {
                        continue;
                    }
                    $text = $v['message'] ?? '';
                    $this->store($record, $page, 'comment', (string) $v['comment_id'], [
                        'text' => is_string($text) ? mb_substr($text, 0, 8000) : '',
                        'sender_id' => (string) ($v['from']['id'] ?? ''), 'ad_id' => '',
                        'post_id' => (string) ($v['post_id'] ?? ''), 'external_id' => (string) $v['comment_id'],
                    ]);
                }
            }
        });

        return response()->json(['received' => true]);
    }

    private function store(BusinessAutomation $record, string $page, string $kind, string $id, array $payload): void
    {
        if ($id === '' || strlen($id) > 512) {
            return;
        }
        $event = new FacebookInboxEvent(['business_id' => $record->business_id, 'page_id' => $page,
            'event_key' => hash('sha256', $page.'|'.$kind.'|'.$id), 'kind' => $kind, 'payload' => $payload,
            'status' => $payload['text'] === '' ? 'handover' : 'queued',
            'reason' => $payload['text'] === '' ? 'Non-text event requires human review.' : null]);
        $event->created_at = now();
        $event->updated_at = now();
        // Model casts encrypt customer content before the atomic duplicate check.
        DB::table('facebook_inbox_events')->insertOrIgnore($event->getAttributes());
    }
}
