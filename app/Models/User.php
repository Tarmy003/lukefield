<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity, HasRoles, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'is_active',
        'two_factor_enabled',
        'last_login_at',
        'last_login_ip',
        'device_fingerprint',
        'trusted_ips',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'trusted_ips' => 'array',
        ];
    }

    // Activity Log Configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'last_login_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function twoFactorCodes()
    {
        return $this->hasMany(TwoFactorCode::class);
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    // Security Methods
    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        if ($this->failed_login_attempts >= 5) {
            $this->lockAccount();
        }
    }

    public function lockAccount(int $minutes = 30): void
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes)
        ]);
        
        activity()
            ->causedBy($this)
            ->log('Account locked due to multiple failed login attempts');
    }

    public function resetFailedAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);
    }

    public function isTrustedIp(string $ip): bool
    {
        if (!$this->trusted_ips) {
            return false;
        }
        
        return in_array($ip, $this->trusted_ips);
    }

    public function generateTwoFactorCode(): string
    {
        // Delete any existing unused codes
        $this->twoFactorCodes()
            ->where('used', false)
            ->delete();

        // Generate 6-digit code
        $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Create new code
        TwoFactorCode::create([
            'user_id' => $this->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(2),
            'ip_address' => request()->ip(),
        ]);

        activity()
            ->causedBy($this)
            ->withProperties(['ip' => request()->ip()])
            ->log('Two-factor code generated');

        return $code;
    }

    public function verifyTwoFactorCode(string $code): bool
    {
        $twoFactorCode = $this->twoFactorCodes()
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$twoFactorCode) {
            $this->incrementFailedAttempts();
            
            activity()
                ->causedBy($this)
                ->withProperties(['ip' => request()->ip(), 'code' => $code])
                ->log('Invalid or expired two-factor code attempt');
            
            return false;
        }

        // Mark code as used
        $twoFactorCode->update([
            'used' => true,
            'used_at' => now(),
        ]);

        $this->resetFailedAttempts();

        activity()
            ->causedBy($this)
            ->withProperties(['ip' => request()->ip()])
            ->log('Two-factor authentication successful');

        return true;
    }

    // In User model
    public function createApiToken($name = 'api-token')
    {
        return $this->createToken($name)->plainTextToken;
    }
}