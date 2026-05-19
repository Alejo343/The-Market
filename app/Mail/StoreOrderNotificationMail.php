<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StoreOrderNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Sale $sale,
        public Order $order,
    ) {}

    public function envelope(): Envelope
    {
        $shortRef = substr($this->order->reference, -3);

        return new Envelope(
            subject: "🛒 Nueva venta online #{$shortRef}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.store-order-notification',
        );
    }
}
