<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Agency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeAgencyMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Agency $agency;

    public function __construct(User $user, Agency $agency)
    {
        $this->user = $user;
        $this->agency = $agency;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🏢 Selamat Datang di GoMad Agency, ' . $this->agency->agency_name . '!',
            to: [$this->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-agency',
            with: [
                'user' => $this->user,
                'agency' => $this->agency,
            ],
        );
    }
}