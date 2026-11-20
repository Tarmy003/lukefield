<?php
// app/Http/Middleware/EnforceIdleTimeout.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a user out after a period without activity (15 minutes by default),
 * whatever SESSION_LIFETIME is set to, so an unlocked screen left at a desk
 * cannot be used to read customer records. Also stops "remember me" from
 * keeping a session alive past the limit.
 */
class EnforceIdleTimeout
{
    public const SESSION_KEY = 'last_activity_at';

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check() || ! $request->hasSession()) {
            return $next($request);
        }

        $timeoutSeconds = max(1, (int) config('security.idle_timeout', 15)) * 60;
        $lastActivity = (int) $request->session()->get(self::SESSION_KEY, 0);

        if ($lastActivity > 0 && (time() - $lastActivity) > $timeoutSeconds) {
            $user = Auth::user();

            $user->loginHistories()
                ->whereNull('logged_out_at')
                ->latest('logged_in_at')
                ->first()
                ?->update(['logged_out_at' => now()]);

            activity()
                ->causedBy($user)
                ->withProperties(['ip' => $request->ip(), 'idle_minutes' => (int) config('security.idle_timeout', 15)])
                ->log('Signed out after inactivity');

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'You were signed out after '.config('security.idle_timeout', 15).' minutes without activity. Please sign in again.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'redirect' => route('login')], 401);
            }

            return redirect()->route('login')->with('error', $message);
        }

        $request->session()->put(self::SESSION_KEY, time());

        return $next($request);
    }
}
