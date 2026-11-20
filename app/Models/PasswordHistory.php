<?php
// app/Models/PasswordHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per password a user has had (bcrypt hash only), used to stop
 * people cycling back to a recent password.
 */
class PasswordHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['user_id', 'password', 'created_at'];

    protected $hidden = ['password'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
