<?php

namespace App\Mail;

use App\Models\User;
use App\Models\PaymentAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomePaymentAgentMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public PaymentAgent $agent;

    public function __construct(User $user, PaymentAgent $agent)
    {
        $this->user = $user;
        $this->agent = $agent;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🏪 Selamat Datang di GoMad Warung, ' . $this->agent->agent_name . '!',
            to: [$this->user->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-payment-agent',
            with: [
                'user' => $this->user,
                'agent' => $this->agent,
            ],
        );
    }
}