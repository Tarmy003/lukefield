<?php
// app/Mail/SystemTestMail.php

namespace App\Mail;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent from System Health to prove the mail server works. Sign-in codes
 * depend on email, so if this fails nobody can sign in.
 */
class SystemTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public CarbonInterface $sentAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Archive Repository: test email',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.system-test',
        );
    }
}
