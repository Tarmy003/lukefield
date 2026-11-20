<?php
// bootstrap/app.php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'account.active' => \App\Http\Middleware\CheckAccountActive::class,
            'account.locked' => \App\Http\Middleware\CheckAccountLocked::class,
            'log.activity' => \App\Http\Middleware\LogActivity::class,
            'password.current' => \App\Http\Middleware\EnforcePasswordChange::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        // Applied to every web request, in this order:
        //  - deactivated or locked accounts are signed out at once
        //  - AuthenticateSession signs out other devices when a password changes
        //  - the idle timeout signs out an unattended session
        //  - a required password change blocks everything else
        $middleware->web(append: [
            \App\Http\Middleware\CheckAccountActive::class,
            \App\Http\Middleware\CheckAccountLocked::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\EnforceIdleTimeout::class,
            \App\Http\Middleware\EnforcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
