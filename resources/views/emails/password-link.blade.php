{{-- resources/views/emails/password-link.blade.php --}}
@extends('emails.layouts.security')

@php
    $isWelcome = $purpose === \App\Models\PasswordSetupToken::PURPOSE_WELCOME;
@endphp

@section('title', $isWelcome ? 'Set up your account' : 'Reset your password')

@section('body')
    <p style="margin:0 0 16px;">Hello {{ $user->name }},</p>

    @if ($isWelcome)
        <p style="margin:0 0 16px;">
            An Archive Repository account has been created for you with the email address
            <strong>{{ $user->email }}</strong>. Choose your password with the button below to finish setting it up.
        </p>
    @else
        <p style="margin:0 0 16px;">
            We received a request to reset the password for your Archive Repository account.
            Choose a new password with the button below.
        </p>
    @endif

    <table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px 0;">
        <tr>
            <td style="background-color:#0baf1a; border-radius:8px;">
                <a href="{{ $url }}" style="display:inline-block; padding:13px 26px; color:#ffffff; font-weight:bold; text-decoration:none; font-size:15px;">
                    {{ $isWelcome ? 'Set my password' : 'Reset my password' }}
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; color:#5b6b72;">
        The link works once and expires on
        <strong>{{ $expiresAt->timezone('Africa/Lagos')->format('j F Y \a\t g:i a') }}</strong> (Lagos time).
        If the button does not work, copy this address into your browser:<br>
        <span style="word-break:break-all;">{{ $url }}</span>
    </p>

    @if ($isWelcome)
        <p style="margin:0 0 16px; font-size:14px;">
            After that, sign in with your email address and password. Each time, a 6-digit code will also be emailed to you.
        </p>
    @endif

    <div style="background-color:#fff7e6; border-left:4px solid #f59e0b; padding:14px 16px; font-size:14px;">
        @if ($isWelcome)
            <strong>Not expecting this?</strong> Do not use the link. Tell the IT Unit.
        @else
            <strong>Did not ask for this?</strong> Ignore this email: your password stays the same. If it keeps happening, tell the IT Unit.
        @endif
    </div>
@endsection
