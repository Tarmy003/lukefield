<?php
// app/Http/Controllers/Account/PasswordController.php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\PasswordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * "Password & security" page for the signed-in user: change password, see
 * when it expires and see recent sign-ins to the account.
 */
class PasswordController extends Controller
{
    public function __construct(private PasswordService $passwords)
    {
    }

    public function edit(Request $request)
    {
        $user = $request->user();

        return view('backend.account.password', [
            'user' => $user,
            'reason' => $this->passwords->changeRequiredReason($user),
            'expiresAt' => $this->passwords->expiresAt($user),
            'daysLeft' => $this->passwords->daysUntilExpiry($user),
            'policy' => $this->passwords->policyLines(),
            'recentLogins' => $user->loginHistories()->latest('logged_in_at')->limit(10)->get(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        // Five wrong current passwords in 15 minutes stops the form, so a
        // session left open cannot be used to guess the password.
        $key = 'change-password:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'current_password' => 'Too many attempts. Try again in '.ceil(RateLimiter::availableIn($key) / 60).' minute(s).',
            ]);
        }

        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! \Illuminate\Support\Facades\Hash::check($request->input('current_password'), $user->getAuthPassword())) {
            RateLimiter::hit($key, 900);

            activity()
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('Password change failed: wrong current password');

            throw ValidationException::withMessages([
                'current_password' => 'Your current password is not correct.',
            ]);
        }

        $request->validate([
            'password' => $this->passwords->rules($user),
        ], [
            'password.confirmed' => 'The new password and its confirmation do not match.',
        ]);

        RateLimiter::clear($key);

        $wasRequired = $this->passwords->changeRequiredReason($user) !== null;

        $this->passwords->setPassword(
            $user,
            $request->input('password'),
            $wasRequired ? PasswordService::REASON_FORCED : PasswordService::REASON_CHANGED
        );

        $request->session()->regenerate();

        return redirect()
            ->route($wasRequired ? 'dashboard' : 'account.password.edit')
            ->with('success', 'Your password has been changed. You have been signed out on any other device.');
    }
}
