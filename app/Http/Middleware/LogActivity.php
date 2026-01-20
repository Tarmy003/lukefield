<?php
// app/Http/Middleware/LogActivity.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();
            
            // Log significant actions
            $route = $request->route()->getName();
            $method = $request->method();
            
            if ($this->shouldLog($route, $method)) {
                activity()
                    ->causedBy($user)
                    ->withProperties([
                        'route' => $route,
                        'method' => $method,
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl(),
                    ])
                    ->log("User accessed: {$route}");
            }
        }

        return $response;
    }

    private function shouldLog(string $route, string $method): bool
    {
        // Log all POST, PUT, PATCH, DELETE requests
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return true;
        }

        // Log specific GET routes
        $loggedRoutes = [
            'accounts.show',
            'transactions.index',
            'customers.show',
            'reports.generate',
        ];

        return in_array($route, $loggedRoutes);
    }
}