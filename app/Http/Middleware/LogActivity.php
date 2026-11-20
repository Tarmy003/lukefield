<?php
// app/Http/Middleware/LogActivity.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes an audit-log entry for every change and for every look at customer
 * data: statements previewed or exported, customers searched or opened,
 * account details read. Records who, when, from which IP, and which account
 * or search, but never passwords, tokens or uploaded files.
 */
class LogActivity
{
    /** GET routes that show customer data and must be logged. */
    private const LOGGED_GET_ROUTES = [
        'customers.search' => 'Searched customers',
        'customers.show' => 'Viewed customer record',
        'customers.export' => 'Exported customer list',
        'archive.accounts.search' => 'Searched accounts',
        'archive.accounts.details' => 'Viewed account details',
        'audit.activity.export' => 'Exported the audit log',
        'audit.logins.export' => 'Exported login history',
    ];

    /** Clear descriptions for the main actions. */
    private const DESCRIPTIONS = [
        'archive.statement.preview' => 'Previewed statement',
        'archive.statement.excel' => 'Exported statement to Excel',
        'archive.statement.pdf' => 'Exported statement to PDF',
        'archive.import.accounts.post' => 'Imported accounts',
        'archive.import.transactions.post' => 'Imported transactions',
        'archive.clear.data' => 'Cleared archive data',
        'archive.logs.clear' => 'Cleared import logs',
        'customers.import.post' => 'Imported customers',
        'customers.update' => 'Updated customer record',
    ];

    /** Routes that log themselves in more detail, or are just keep-alives. */
    private const SKIPPED_ROUTES = [
        'session.ping',
        'logout',
        'account.password.update',
    ];

    /** Request fields that are safe and useful to keep. Everything else is dropped. */
    private const KEPT_INPUTS = [
        'account_number', 'accountNumber', 'start_date', 'end_date',
        'search', 'q', 'term', 'customer_name', 'customer_id', 'account_no',
        'bvn', 'phone', 'email', 'customer_type', 'branch', 'pep', 'sector',
        'account_officer', 'format', 'type', 'status', 'action', 'user_ids',
        'date_from', 'date_to',
    ];

    /** Search pages are only logged when something was actually searched for. */
    private const SEARCH_ROUTES = ['customers.search', 'archive.accounts.search'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();
        $routeName = $request->route()?->getName();

        if (! $user || ! $routeName || in_array($routeName, self::SKIPPED_ROUTES, true)) {
            return $response;
        }

        $isChange = in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
        $isLoggedView = array_key_exists($routeName, self::LOGGED_GET_ROUTES);

        if (! $isChange && ! $isLoggedView) {
            return $response;
        }

        $parameters = collect($request->route()->parameters())
            ->map(fn ($value) => is_object($value) && method_exists($value, 'getKey') ? $value->getKey() : $value)
            ->filter(fn ($value) => is_scalar($value))
            ->all();

        $inputs = collect($request->only(self::KEPT_INPUTS))
            ->map(fn ($value) => is_string($value) ? Str::limit($value, 120) : $value)
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if (! $isChange && in_array($routeName, self::SEARCH_ROUTES, true) && $inputs === []) {
            return $response;
        }

        $description = self::DESCRIPTIONS[$routeName]
            ?? self::LOGGED_GET_ROUTES[$routeName]
            ?? 'Action: '.$routeName;

        activity()
            ->causedBy($user)
            ->withProperties(array_filter([
                'route' => $routeName,
                'method' => $request->method(),
                'status' => $response->getStatusCode(),
                'ip' => $request->ip(),
                'parameters' => $parameters ?: null,
                'inputs' => $inputs ?: null,
            ], fn ($value) => $value !== null))
            ->log($description);

        return $response;
    }
}
