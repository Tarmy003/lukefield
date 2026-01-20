<?php
// app/Models/LoginHistory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;

class LoginHistory extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'location_data',
        'logged_in_at',
        'logged_out_at',
    ];

    protected $casts = [
        'location_data' => 'array',
        'logged_in_at' => 'datetime',
        'logged_out_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function recordLogin(User $user): void
    {
        $agent = new Agent();
        
        self::create([
            'user_id' => $user->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'device_type' => $agent->device(),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'logged_in_at' => now(),
        ]);
    }
}