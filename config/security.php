<?php
// config/security.php
//
// Security settings for the Archive Repository. Each value can be overridden
// in .env, but the defaults are the approved policy.

return [

    // Minutes of inactivity before a signed-in user is signed out.
    'idle_timeout' => (int) env('SECURITY_IDLE_TIMEOUT', 15),

    // Seconds before the timeout at which the "still there?" warning appears.
    'idle_warning_seconds' => 120,

    'password' => [
        // Days a password stays valid before it must be changed.
        'expiry_days' => (int) env('SECURITY_PASSWORD_EXPIRY_DAYS', 90),

        // Days before expiry at which users start being warned.
        'warn_days' => 14,

        // Number of previous passwords that cannot be reused.
        'history' => 5,

        'min_length' => 12,

        // Check new passwords against known data-breach lists (Have I Been Pwned,
        // k-anonymity: only the first 5 characters of a hash leave the server).
        'check_breached' => (bool) env('SECURITY_CHECK_BREACHED_PASSWORDS', true),
    ],

    'links' => [
        // Minutes a "reset your password" link stays valid.
        'reset_minutes' => 60,

        // Hours a "set up your account" link for a new user stays valid.
        'welcome_hours' => 24,
    ],

    // Days without a sign-in after which an account is flagged as dormant.
    'dormant_days' => 90,

    // Super Admin can see these many recent error lines from the Laravel log.
    'health_log_lines' => 25,
];
