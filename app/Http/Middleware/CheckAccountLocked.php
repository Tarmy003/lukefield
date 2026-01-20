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
        if (Auth::check() && Auth::user()->isLocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $remainingTime = Auth::user()->locked_until->diffInMinutes(now());

            return redirect()->route('login')
                ->withErrors(['email' => "Your account is locked. Try again in {$remainingTime} minutes."]);
        }

        return $next($request);
    }
}