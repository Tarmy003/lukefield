<?php
// app/Http/Middleware/EnforcePasswordChange.php

namespace App\Http\Middleware;

use App\Services\PasswordService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a signed-in user to the change-password page, and nowhere else, when
 * an administrator has required a change, the password has never been
 * changed in the archive (imported accounts), or it is older than 90 days.
 */
class EnforcePasswordChange
{
    /** Routes a user can still reach while a change is required. */
    private const ALLOWED_ROUTES = [
        'account.password.edit',
        'account.password.update',
        'session.ping',
        'logout',
    ];

    public function __construct(private PasswordService $passwords)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        if ($this->passwords->changeRequiredReason($user) === null) {
            return $next($request);
        }

        $message = 'You need to set a new password before you can continue.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'redirect' => route('account.password.edit')], 403);
        }

        return redirect()->route('account.password.edit');
    }
}
