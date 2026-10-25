<?php

namespace App\Services;

use App\Models\FacebookInboxEvent;
use App\Models\FacebookProductMapping;
use Illuminate\Validation\ValidationException;

class FacebookAutomaticReply
{
    public function context(FacebookInboxEvent $e): ?FacebookProductMapping
    {
        $matches = [];
        foreach (['ad' => 'ad_id', 'post' => 'post_id'] as $type => $field) {
            $external = $e->payload[$field] ?? '';
            if ($external === '') {
                continue;
            }
            $m = FacebookProductMapping::with('product')->where('business_id', $e->business_id)->where('page_id', $e->page_id)->where('source_type', $type)->where('external_id', $external)->first();
            if ($m && $m->product && $m->product->business_id === $e->business_id && $m->product->is_active) {
                $matches[] = $m;
            }
        }
        if (count(array_unique(array_map(fn ($m) => $m->product_id, $matches))) !== 1) {
            return null;
        }
        foreach ($matches as $m) {
            if (! $m->auto_reply) {
                return $m;
            }
        }

        return $matches[0] ?? null;
    }

    public function attempt(int $business, int $id): array
    {
        $e = FacebookInboxEvent::where('business_id', $business)->whereKey($id)->firstOrFail();
        if (! in_array($e->status, ['review', 'handover'], true)) {
            return ['sent' => false, 'reason' => 'Event is not ready for an automatic reply.'];
        }
        $mapping = $this->context($e);
        if (! $mapping || ! $mapping->auto_reply) {
            return ['sent' => false, 'reason' => 'No enabled and unambiguous product rule.'];
        }
        $question = mb_strtolower(trim((string) ($e->payload['text'] ?? '')));
        $question = preg_replace('/[?？!।.]+$/u', '', $question);
        $question = preg_replace('/\s+/u', ' ', $question);
        $price = ['price', 'price please', 'price pls', 'price koto', 'dam koto', 'দাম', 'দাম কত', 'কত দাম', 'প্রাইস', 'প্রাইস কত'];
        $stock = ['available', 'in stock', 'stock ache', 'available ache', 'আছে', 'স্টক আছে', 'পাওয়া যাবে', 'পাওয়া যাবে'];
        $p = $mapping->product;
        $bangla = preg_match('/[\x{0980}-\x{09FF}]/u', $question);
        if (in_array($question, $price, true)) {
            $text = $bangla ? $p->name.' — দাম '.number_format((float) $p->current_price, 2).' টাকা।' : $p->name.' — price BDT '.number_format((float) $p->current_price, 2).'.';
        } elseif (in_array($question, $stock, true)) {
            $available = ! $p->track_stock || (int) $p->stock > 0;
            $text = $bangla ? $p->name.' — '.($available ? 'বর্তমানে পাওয়া যাচ্ছে।' : 'বর্তমানে স্টকে নেই।') : $p->name.' — '.($available ? 'currently available.' : 'currently out of stock.');
        } else {
            return ['sent' => false, 'reason' => 'Question requires human review.'];
        }
        try {
            $r = app(FacebookReplySender::class)->send($business, $id, $text, null, 'mapping:'.$mapping->id);

            return ['sent' => $r->status === 'sent', 'status' => $r->status];
        } catch (ValidationException) {
            return ['sent' => false, 'reason' => 'Reply needs manual review or the messaging window has expired.'];
        }
    }
}
