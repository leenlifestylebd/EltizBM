<?php

namespace App\Services;

use App\Models\BusinessAutomation;
use App\Models\FacebookInboxEvent;
use App\Models\FacebookReply;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

class FacebookReplySender
{
    public function send(int $business, int $eventId, string $text, ?int $actor, string $source = 'human'): FacebookReply
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > 2000) {
            throw ValidationException::withMessages(['reply' => 'Write a reply of 1 to 2000 characters.']);
        }
        [$reply,$event,$token] = DB::transaction(function () use ($business, $eventId, $text, $actor, $source) {
            DB::table('businesses')->where('id', $business)->lockForUpdate()->first();
            $e = FacebookInboxEvent::where('business_id', $business)->whereKey($eventId)->lockForUpdate()->firstOrFail();
            $old = FacebookReply::where('event_id', $e->id)->first();
            if ($old && $old->status !== 'failed') {
                throw ValidationException::withMessages(['reply' => 'This reply was already sent or its delivery is uncertain. Check Meta Business Suite before taking further action.']);
            }
            if (! in_array($e->status, ['review', 'handover'], true)) {
                throw ValidationException::withMessages(['reply' => 'Wait for processing to finish before approving this reply.']);
            }
            $c = BusinessAutomation::where('business_id', $business)->first();
            if (! $c || ($c->settings['facebook_page_id'] ?? '') !== $e->page_id || empty($c->secrets['facebook_page_token'])) {
                throw ValidationException::withMessages(['reply' => 'Reconnect this event’s original Facebook Page before replying.']);
            }
            $payload = $e->payload;
            $external = (string) ($payload['external_id'] ?? '');
            $sender = (string) ($payload['sender_id'] ?? '');
            if (str_starts_with($external, 'setup-test-') || $sender === '0') {
                throw ValidationException::withMessages(['reply' => 'Setup test events cannot send real replies.']);
            }
            if ($e->kind === 'message') {
                if (! ctype_digit($sender) || $sender === $e->page_id) {
                    throw ValidationException::withMessages(['reply' => 'This event has no valid customer recipient.']);
                }
                $time = $e->created_at->timestamp;
                if (is_numeric($payload['occurred_at'] ?? null)) {
                    $time = min($time, (int) $payload['occurred_at']);
                }
                if ($time < now()->subHours(24)->timestamp) {
                    throw ValidationException::withMessages(['reply' => 'The standard Messenger reply window has expired. Handle this conversation in Meta Business Suite.']);
                }
            } elseif ($e->kind !== 'comment' || ! preg_match('/^\d+(?:_\d+)?$/D', $external)) {
                throw ValidationException::withMessages(['reply' => 'This event cannot receive a supported reply.']);
            }
            $r = $old ?? new FacebookReply(['business_id' => $business, 'event_id' => $e->id]);
            $r->fill(['approved_by' => $actor, 'source' => $source, 'text' => $text, 'status' => 'sending', 'note' => null])->save();
            $e->update(['status' => 'sending']);

            return [$r, $e, $c->secrets['facebook_page_token']];
        });
        $payload = $event->payload;
        $secret = (string) config('services.facebook.client_secret');
        $base = 'https://graph.facebook.com/'.config('services.facebook.version');
        $path = $event->kind === 'message' ? $event->page_id.'/messages' : $payload['external_id'].'/comments';
        $body = $event->kind === 'message' ? ['recipient' => ['id' => (string) $payload['sender_id']], 'messaging_type' => 'RESPONSE', 'message' => ['text' => $text]] : ['message' => $text];
        $body['appsecret_proof'] = hash_hmac('sha256', $token, $secret);
        $state = 'unknown';
        $provider = null;
        $note = 'Delivery could not be confirmed. Check Meta Business Suite; do not resend automatically.';
        try {
            $res = Http::acceptJson()->withToken($token)->connectTimeout(5)->timeout(20)->withoutRedirecting()->post($base.'/'.$path, $body);
            $provider = $res->json($event->kind === 'message' ? 'message_id' : 'id');
            if ($res->successful() && is_string($provider) && $provider !== '') {
                $state = 'sent';
                $note = null;
            } elseif ($res->clientError() && is_array($res->json('error'))) {
                $state = 'failed';
                $provider = null;
                $note = 'Facebook rejected the reply (code '.(int) $res->json('error.code').'). Check Page access and permissions before retrying.';
            }
        } catch (Throwable) {/* A timeout may follow delivery. Never retry an uncertain send. */
        }
        DB::transaction(function () use ($reply, $event, $state, $provider, $note) {
            $r = FacebookReply::whereKey($reply->id)->lockForUpdate()->firstOrFail();
            if ($r->status !== 'sending') {
                return;
            }
            $r->update(['status' => $state, 'provider_id' => $state === 'sent' ? mb_substr($provider, 0, 255) : null, 'note' => $note, 'sent_at' => $state === 'sent' ? now() : null]);
            FacebookInboxEvent::whereKey($event->id)->update(['status' => $state === 'sent' ? 'sent' : ($state === 'unknown' ? 'send_unknown' : 'handover'), 'reason' => $note]);
        });

        return $reply->fresh();
    }
}
