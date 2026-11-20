<?php
// routes/console.php

use App\Models\PasswordSetupToken;
use App\Models\TwoFactorCode;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| These run only if the server cron calls "php artisan schedule:run" every
| minute (see /etc/cron.d/lukefield-archive). System Health shows a warning
| when the heartbeat below stops arriving.
*/

// Heartbeat: proves the scheduler is running.
Schedule::call(function () {
    Cache::forever('scheduler:heartbeat', now()->toIso8601String());
})->everyMinute()->name('scheduler-heartbeat')->withoutOverlapping();

// Remove spent sign-in codes and password links. Nothing of audit value is
// lost: the activity log already records when each was issued and used.
Schedule::call(function () {
    TwoFactorCode::where('expires_at', '<', now()->subDay())->delete();

    PasswordSetupToken::where(function ($query) {
        $query->whereNotNull('used_at')->orWhere('expires_at', '<', now());
    })->where('updated_at', '<', now()->subDays(30))->delete();
})->dailyAt('02:15')->name('prune-security-tokens')->withoutOverlapping();
