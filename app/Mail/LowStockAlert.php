<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string     $businessName,
        public Collection $lowStockProducts,
        public Collection $outOfStockProducts,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Low Stock Alert — ' . $this->businessName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-alert',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
