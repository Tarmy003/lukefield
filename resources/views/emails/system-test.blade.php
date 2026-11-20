{{-- resources/views/emails/system-test.blade.php --}}
@extends('emails.layouts.security')

@section('title', 'Test email')

@section('body')
    <p style="margin:0 0 16px;">Hello {{ $user->name }},</p>

    <p style="margin:0 0 16px;">
        This is a test from the System Health page. If you are reading it, the Archive Repository can send email,
        so sign-in codes and password emails will be delivered.
    </p>

    <p style="margin:0; font-size:14px; color:#5b6b72;">
        Sent {{ $sentAt->timezone('Africa/Lagos')->format('j F Y, g:i:s a') }} (Lagos time) from {{ config('app.url') }}.
    </p>
@endsection
