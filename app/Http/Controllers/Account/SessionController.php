<?php
// app/Http/Controllers/Account/SessionController.php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Called by the "Stay signed in" button in the inactivity warning. Reaching
 * it at all resets the idle timer (EnforceIdleTimeout runs first).
 */
class SessionController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'idle_timeout_seconds' => (int) config('security.idle_timeout', 15) * 60,
        ]);
    }
}
