<?php
// app/Mail/PasswordLinkMail.php

namespace App\Mail;

use App\Models\PasswordSetupToken;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A single-use link to set a password: a reset for an existing user or the
 * account setup for a new one.
 */
class PasswordLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $url,
        public string $purpose,
        public CarbonInterface $expiresAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === PasswordSetupToken::PURPOSE_WELCOME
                ? 'Set up your Archive Repository account'
                : 'Reset your Archive Repository password',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-link',
        );
    }
}
