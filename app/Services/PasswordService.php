<?php
// app/Services/PasswordService.php

namespace App\Services;

use App\Mail\PasswordChangedMail;
use App\Mail\PasswordLinkMail;
use App\Models\PasswordHistory;
use App\Models\PasswordSetupToken;
use App\Models\User;
use App\Rules\NotRecentlyUsedPassword;
use App\Rules\PasswordNotPersonal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Throwable;

/**
 * Every password change in the archive goes through here, whichever screen
 * it starts from, so the policy, the history, the session sign-out, the audit
 * entry and the "your password was changed" email can never be skipped.
 */
class PasswordService
{
    public const REASON_CHANGED = 'changed';
    public const REASON_FORCED = 'forced_change';
    public const REASON_RESET_LINK = 'reset_link';
    public const REASON_WELCOME = 'welcome_setup';

    private const REASON_LABELS = [
        self::REASON_CHANGED => 'Password changed by the user',
        self::REASON_FORCED => 'Password changed at a required change',
        self::REASON_RESET_LINK => 'Password reset with an emailed link',
        self::REASON_WELCOME => 'Password set up with a welcome link',
    ];

    /**
     * Validation rules for a new password for this user.
     */
    public function rules(User $user): array
    {
        $policy = Password::min((int) config('security.password.min_length', 12))
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();

        if (config('security.password.check_breached', true)) {
            $policy->uncompromised();
        }

        return [
            'required',
            'string',
            'max:128',
            'confirmed',
            $policy,
            new PasswordNotPersonal($user),
            new NotRecentlyUsedPassword($user),
        ];
    }

    /**
     * Plain-language summary of the policy for the forms.
     */
    public function policyLines(): array
    {
        $lines = [
            'At least '.config('security.password.min_length', 12).' characters',
            'Upper and lower case letters, a number and a symbol',
            'Not your name, email address or the company name',
            'Not one of your last '.config('security.password.history', 5).' passwords',
        ];

        if (config('security.password.check_breached', true)) {
            $lines[] = 'Not a password found in known data breaches';
        }

        return $lines;
    }

    /**
     * Set a new password (already validated) and apply every side effect.
     */
    public function setPassword(User $user, string $plainPassword, string $reason, ?User $actor = null): void
    {
        $currentSessionId = (auth()->id() === $user->id && request()->hasSession())
            ? request()->session()->getId()
            : null;

        DB::transaction(function () use ($user, $plainPassword) {
            $user->forceFill([
                'password' => $plainPassword, // hashed by the model cast
                'password_changed_at' => now(),
                'must_change_password' => false,
                'failed_login_attempts' => 0,
                'locked_until' => null,
                // A new remember token kills every "remember me" cookie.
                'remember_token' => Str::random(60),
            ])->save();

            PasswordHistory::create([
                'user_id' => $user->id,
                'password' => $user->getAuthPassword(),
                'created_at' => now(),
            ]);

            $this->pruneHistory($user);
        });

        // Other devices: the AuthenticateSession middleware signs them out on
        // their next request because the password hash changed. With database
        // sessions we also delete the rows so they end immediately.
        $this->endOtherSessions($user, $currentSessionId);

        activity()
            ->causedBy($actor ?? $user)
            ->performedOn($user)
            ->withProperties([
                'reason' => $reason,
                'ip' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 250),
            ])
            ->log(self::REASON_LABELS[$reason] ?? 'Password changed');

        try {
            Mail::to($user->email)->send(new PasswordChangedMail(
                $user,
                self::REASON_LABELS[$reason] ?? 'Password changed',
                (string) request()->ip(),
                now()
            ));
        } catch (Throwable $e) {
            Log::error('Password-changed email failed for user '.$user->id.': '.$e->getMessage());
        }
    }

    /**
     * Create a single-use link and email it. Any earlier unused link for the
     * user stops working. Throws if the email cannot be sent, so the admin
     * knows the user never received it.
     */
    public function issueLink(User $user, string $purpose, ?User $actor = null): PasswordSetupToken
    {
        $plainToken = Str::random(64);

        $expiresAt = $purpose === PasswordSetupToken::PURPOSE_WELCOME
            ? now()->addHours((int) config('security.links.welcome_hours', 24))
            : now()->addMinutes((int) config('security.links.reset_minutes', 60));

        $token = DB::transaction(function () use ($user, $purpose, $actor, $plainToken, $expiresAt) {
            PasswordSetupToken::where('user_id', $user->id)
                ->whereNull('used_at')
                ->delete();

            return PasswordSetupToken::create([
                'user_id' => $user->id,
                'token_hash' => PasswordSetupToken::hashToken($plainToken),
                'purpose' => $purpose,
                'expires_at' => $expiresAt,
                'created_by' => $actor?->id,
                'ip_address' => request()->ip(),
            ]);
        });

        try {
            Mail::to($user->email)->send(new PasswordLinkMail(
                $user,
                route('password.reset.form', ['token' => $plainToken]),
                $purpose,
                $expiresAt
            ));
        } catch (Throwable $e) {
            $token->delete();
            Log::error("Password {$purpose} link email failed for user {$user->id}: ".$e->getMessage());

            throw $e;
        }

        activity()
            ->causedBy($actor ?? $user)
            ->performedOn($user)
            ->withProperties([
                'purpose' => $purpose,
                'expires_at' => $expiresAt->toDateTimeString(),
                'ip' => request()->ip(),
            ])
            ->log($purpose === PasswordSetupToken::PURPOSE_WELCOME
                ? 'Account setup link sent'
                : 'Password reset link sent');

        return $token;
    }

    public function findUsableToken(string $plainToken): ?PasswordSetupToken
    {
        $token = PasswordSetupToken::with('user')
            ->where('token_hash', PasswordSetupToken::hashToken($plainToken))
            ->first();

        if (! $token || ! $token->isUsable() || ! $token->user || ! $token->user->is_active) {
            return null;
        }

        return $token;
    }

    public function expiresAt(User $user): ?CarbonInterface
    {
        return $user->password_changed_at?->copy()->addDays((int) config('security.password.expiry_days', 90));
    }

    public function isExpired(User $user): bool
    {
        $expiresAt = $this->expiresAt($user);

        return $expiresAt === null || $expiresAt->isPast();
    }

    /**
     * Must the user set a new password before using the archive?
     * Returns the reason, or null when no change is needed.
     */
    public function changeRequiredReason(User $user): ?string
    {
        if ($user->must_change_password) {
            return 'required';
        }

        if ($user->password_changed_at === null) {
            return 'never_changed';
        }

        if ($this->isExpired($user)) {
            return 'expired';
        }

        return null;
    }

    public function daysUntilExpiry(User $user): ?int
    {
        $expiresAt = $this->expiresAt($user);

        return $expiresAt ? (int) floor(now()->diffInDays($expiresAt, false)) : null;
    }

    private function pruneHistory(User $user): void
    {
        $keep = (int) config('security.password.history', 5);

        $keepIds = PasswordHistory::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($keep)
            ->pluck('id');

        PasswordHistory::where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    public function endOtherSessions(User $user, ?string $exceptSessionId = null): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        $table = config('session.table', 'sessions');

        return DB::table($table)
            ->where('user_id', $user->id)
            ->when($exceptSessionId, fn ($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }
}
