<?php
// app/Http/Controllers/Auth/PasswordResetController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use App\Models\PasswordSetupToken;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * "Forgot password" and the page behind every emailed link (reset and new
 * account setup). Signing in afterwards still needs the emailed code.
 */
class PasswordResetController extends Controller
{
    private const NEUTRAL_MESSAGE = 'If that email address belongs to an active Archive Repository account, a reset link has been sent to it. The link expires in :minutes minutes.';

    public function __construct(private PasswordService $passwords)
    {
    }

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $email = Str::lower(trim($request->input('email')));

        // Limit per IP and per address, so the form cannot be used to flood
        // someone's inbox or to test which addresses have accounts.
        foreach (['forgot-ip:'.$request->ip() => 10, 'forgot-email:'.sha1($email) => 3] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw ValidationException::withMessages([
                    'email' => 'Too many requests. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minute(s).',
                ]);
            }
            RateLimiter::hit($key, 900);
        }

        $user = User::where('email', $email)->first();

        if ($user && $user->is_active) {
            try {
                $this->passwords->issueLink($user, PasswordSetupToken::PURPOSE_RESET);
            } catch (Throwable $e) {
                Log::error('Self-service reset link failed for '.$email.': '.$e->getMessage());
            }
        }

        LoginAttempt::recordAttempt($email, false, $user && $user->is_active
            ? 'Password reset link requested'
            : 'Password reset requested for unknown or inactive account');

        // Same answer whether or not the account exists.
        return back()->with('status', str_replace(':minutes', (string) config('security.links.reset_minutes', 60), self::NEUTRAL_MESSAGE));
    }

    public function showResetForm(string $token)
    {
        $setupToken = $this->passwords->findUsableToken($token);

        if (! $setupToken) {
            return view('auth.reset-password', ['valid' => false]);
        }

        return view('auth.reset-password', [
            'valid' => true,
            'token' => $token,
            'email' => $setupToken->user->email,
            'isWelcome' => $setupToken->purpose === PasswordSetupToken::PURPOSE_WELCOME,
            'expiresAt' => $setupToken->expires_at,
            'policy' => $this->passwords->policyLines(),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => ['required', 'string', 'size:64']]);

        $key = 'reset-submit:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 20)) {
            abort(429, 'Too many attempts. Try again later.');
        }
        RateLimiter::hit($key, 900);

        $setupToken = $this->passwords->findUsableToken($request->input('token'));

        if (! $setupToken) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'That link has expired or has already been used. Request a new one below.']);
        }

        $user = $setupToken->user;

        $request->validate([
            'password' => $this->passwords->rules($user),
        ], [
            'password.confirmed' => 'The new password and its confirmation do not match.',
        ]);

        $isWelcome = $setupToken->purpose === PasswordSetupToken::PURPOSE_WELCOME;

        // Mark the link used first, so a double click cannot use it twice.
        $claimed = PasswordSetupToken::whereKey($setupToken->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        if ($claimed !== 1) {
            return redirect()->route('password.request')
                ->withErrors(['email' => 'That link has already been used. Request a new one below.']);
        }

        if ($isWelcome && $user->email_verified_at === null) {
            // The user proved they control the mailbox by opening the link.
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $this->passwords->setPassword(
            $user,
            $request->input('password'),
            $isWelcome ? PasswordService::REASON_WELCOME : PasswordService::REASON_RESET_LINK
        );

        return redirect()->route('login')->with('success', $isWelcome
            ? 'Your password is set. Sign in with your email address and new password.'
            : 'Your password has been reset. Sign in with your new password.');
    }
}
