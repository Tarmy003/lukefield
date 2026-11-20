{{-- resources/views/emails/password-changed.blade.php --}}
@extends('emails.layouts.security')

@section('title', 'Your password was changed')

@section('body')
    <p style="margin:0 0 16px;">Hello {{ $user->name }},</p>

    <p style="margin:0 0 16px;">The password for your Archive Repository account was changed.</p>

    <table role="presentation" cellspacing="0" cellpadding="0" style="width:100%; margin:0 0 20px; font-size:14px; border-collapse:collapse;">
        <tr>
            <td style="padding:8px 0; color:#5b6b72; width:120px;">How</td>
            <td style="padding:8px 0; font-weight:bold;">{{ $how }}</td>
        </tr>
        <tr>
            <td style="padding:8px 0; color:#5b6b72; border-top:1px solid #e3e9e6;">When</td>
            <td style="padding:8px 0; font-weight:bold; border-top:1px solid #e3e9e6;">{{ $changedAt->timezone('Africa/Lagos')->format('j F Y, g:i a') }} (Lagos time)</td>
        </tr>
        <tr>
            <td style="padding:8px 0; color:#5b6b72; border-top:1px solid #e3e9e6;">IP address</td>
            <td style="padding:8px 0; font-weight:bold; border-top:1px solid #e3e9e6;">{{ $ipAddress }}</td>
        </tr>
    </table>

    <p style="margin:0 0 16px;">You have been signed out on any other computer or browser.</p>

    <div style="background-color:#fff7e6; border-left:4px solid #f59e0b; padding:14px 16px; margin:0 0 8px; font-size:14px;">
        <strong>Did not make this change?</strong> Contact the IT Unit immediately so your account can be locked and checked.
    </div>
@endsection
