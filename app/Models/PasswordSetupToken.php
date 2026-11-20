<?php
// app/Models/PasswordSetupToken.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single-use emailed link to set a password: "reset" for an existing user,
 * "welcome" for a new one. Only a SHA-256 hash of the token is stored, so a
 * copy of the database cannot be used to take over an account.
 */
class PasswordSetupToken extends Model
{
    public const PURPOSE_RESET = 'reset';
    public const PURPOSE_WELCOME = 'welcome';

    protected $fillable = [
        'user_id',
        'token_hash',
        'purpose',
        'expires_at',
        'used_at',
        'created_by',
        'ip_address',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function issuer()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }

    public static function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
