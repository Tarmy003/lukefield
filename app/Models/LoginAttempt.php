<?php
// app/Models/LoginAttempt.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'failure_reason',
        'location_data',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'location_data' => 'array',
    ];

    public static function recordAttempt(
        string $email, 
        bool $successful, 
        ?string $reason = null
    ): void {
        self::create([
            'email' => $email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'successful' => $successful,
            'failure_reason' => $reason,
        ]);
    }
}