<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking->load([
            'schedule.route', 
            'schedule.agency', 
            'schedule.vehicle',
            'originStop', 
            'destinationStop', 
            'passengers'
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎫 Booking Baru: ' . $this->booking->booking_code . ' - GoMad',
            to: [$this->booking->customer->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-created',
            with: [
                'booking' => $this->booking,
            ],
        );
    }
}