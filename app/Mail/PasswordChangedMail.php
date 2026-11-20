<?php
// app/Mail/PasswordChangedMail.php

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the user their password was changed, so a change they did not make
 * is noticed straight away.
 */
class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $how,
        public string $ipAddress,
        public CarbonInterface $changedAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Archive Repository password was changed',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-changed',
        );
    }
}
