<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public string $reason;

    public function __construct(Booking $booking, string $reason = 'Dibatalkan')
    {
        $this->booking = $booking;
        $this->reason = $reason;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '❌ Booking Dibatalkan: ' . $this->booking->booking_code . ' - GoMad',
            to: [$this->booking->customer->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-cancelled',
            with: [
                'booking' => $this->booking,
                'reason' => $this->reason,
            ],
        );
    }
}