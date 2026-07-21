<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

use App\Models\Sale;

class ReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Sale $sale)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Receipt from ' . ($this->sale->branch->shop->name ?? 'Our Shop'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt', // we'll create this view
            with: ['sale' => $this->sale],
        );
    }
}