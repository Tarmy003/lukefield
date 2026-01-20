<?php
// app/Http/Controllers/Auth/TwoFactorController.php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LoginAttempt;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    /**
     * Show the login form
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request - Step 1: Verify credentials and send 2FA code
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limiting
        $this->checkRateLimit($request);

        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            LoginAttempt::recordAttempt($request->email, false, 'User not found');
            
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Check if account is active
        if (!$user->is_active) {
            LoginAttempt::recordAttempt($request->email, false, 'Account inactive');
            
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Contact administrator.'],
            ]);
        }

        // Check if account is locked
        if ($user->isLocked()) {
            LoginAttempt::recordAttempt($request->email, false, 'Account locked');
            
            $remainingTime = $user->locked_until->diffInMinutes(now());
            
            throw ValidationException::withMessages([
                'email' => ["Account locked. Try again in {$remainingTime} minutes."],
            ]);
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            $user->incrementFailedAttempts();
            LoginAttempt::recordAttempt($request->email, false, 'Invalid password');
            
            $remaining = 5 - $user->failed_login_attempts;
            
            if ($remaining > 0) {
                throw ValidationException::withMessages([
                    'email' => ["Invalid credentials. {$remaining} attempts remaining."],
                ]);
            } else {
                throw ValidationException::withMessages([
                    'email' => ['Account locked due to multiple failed attempts.'],
                ]);
            }
        }

        // Generate and send 2FA code
        $code = $user->generateTwoFactorCode();
        
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user, $code));
        } catch (\Exception $e) {
            \Log::error('Failed to send 2FA code: ' . $e->getMessage());
            
            throw ValidationException::withMessages([
                'email' => ['Failed to send verification code. Please try again.'],
            ]);
        }

        // Store user ID in session
        $request->session()->put('2fa_user_id', $user->id);
        $request->session()->put('2fa_remember', $request->has('remember'));

        LoginAttempt::recordAttempt($request->email, true, '2FA code sent');

        activity()
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('Login initiated - 2FA code sent');

        return redirect()->route('2fa.verify.form')
            ->with('success', 'A verification code has been sent to your email.');
    }

    /**
     * Show 2FA verification form
     */
    public function showVerifyForm(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        $user = User::find($request->session()->get('2fa_user_id'));
        
        return view('auth.two-factor-verify', [
            'email' => $user->email,
        ]);
    }

    /**
     * Verify 2FA code - Step 2: Complete authentication
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        $userId = $request->session()->get('2fa_user_id');
        $user = User::find($userId);

        if (!$user) {
            $request->session()->forget(['2fa_user_id', '2fa_remember']);
            
            return redirect()->route('login')
                ->with('error', 'Invalid session. Please login again.');
        }

        // Verify the code
        if (!$user->verifyTwoFactorCode($request->code)) {
            $remaining = 5 - $user->failed_login_attempts;
            
            if ($remaining > 0) {
                return back()->withErrors([
                    'code' => "Invalid or expired code. {$remaining} attempts remaining."
                ]);
            } else {
                $request->session()->forget(['2fa_user_id', '2fa_remember']);
                
                return redirect()->route('login')->withErrors([
                    'email' => 'Account locked due to multiple failed attempts.'
                ]);
            }
        }

        // Update last login info
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Record login history
        \App\Models\LoginHistory::recordLogin($user);

        // Log in the user
        $remember = $request->session()->get('2fa_remember', false);
        Auth::login($user, $remember);

        // Clear 2FA session data
        $request->session()->forget(['2fa_user_id', '2fa_remember']);

        // Regenerate session
        $request->session()->regenerate();

        activity()
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('User logged in successfully');

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    /**
     * Resend 2FA code
     */
    public function resend(Request $request)
    {
        if (!$request->session()->has('2fa_user_id')) {
            return redirect()->route('login')
                ->with('error', 'Session expired. Please login again.');
        }

        $user = User::find($request->session()->get('2fa_user_id'));

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Invalid session. Please login again.');
        }

        // Rate limit resends
        $key = 'resend-2fa:' . $user->id;
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            
            return back()->withErrors([
                'code' => "Too many resend attempts. Try again in {$seconds} seconds."
            ]);
        }

        RateLimiter::hit($key, 60); // 1 minute decay

        // Generate new code
        $code = $user->generateTwoFactorCode();
        
        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail($user, $code));
        } catch (\Exception $e) {
            return back()->withErrors([
                'code' => 'Failed to resend code. Please try again.'
            ]);
        }

        activity()
            ->causedBy($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('2FA code resent');

        return back()->with('success', 'A new verification code has been sent to your email.');
    }

    /**
     * Rate limiting check
     */
    protected function checkRateLimit(Request $request)
    {
        $key = 'login:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        RateLimiter::hit($key, 300); // 5 minutes decay
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if ($user) {
            // Update last login history
            $lastHistory = $user->loginHistories()
                ->whereNull('logged_out_at')
                ->latest()
                ->first();
            
            if ($lastHistory) {
                $lastHistory->update(['logged_out_at' => now()]);
            }

            activity()
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('User logged out');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}