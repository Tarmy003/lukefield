<?php
// app/Http/Middleware/CheckAccountLocked.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountLocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->isLocked()) {
            // Read the lock time before signing out: after Auth::logout()
            // there is no user, and the old code crashed here with an error.
            $remainingMinutes = max(1, (int) ceil(now()->diffInMinutes($user->locked_until)));

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = "Your account is locked. Try again in {$remainingMinutes} minute(s) or contact the IT Unit.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'redirect' => route('login')], 401);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
