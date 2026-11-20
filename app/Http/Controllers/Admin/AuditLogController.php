<?php
// app/Http/Controllers/Admin/AuditLogController.php

namespace App\Http\Controllers\Admin;

use App\Models\LoginAttempt;
use App\Models\LoginHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Read-only audit screens: everything users did (activity log) and every
 * sign-in and failed attempt. Exports are CSV so they can go straight into
 * an audit request. Permission: system audit.
 */
class AuditLogController extends Controller
{
    private const EXPORT_LIMIT = 100000;

    public function __construct()
    {
        $this->middleware(['permission:system audit']);
    }

    // ------------------------------------------------------------------
    // Activity log
    // ------------------------------------------------------------------

    public function activity(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $activities = $this->activityQuery($filters)
            ->with(['causer', 'subject'])
            ->paginate(50)
            ->withQueryString();

        return view('backend.audit.activity', [
            'activities' => $activities,
            'filters' => $filters,
            'users' => User::withTrashed()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function exportActivity(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        $query = $this->activityQuery($filters)->with('causer')->limit(self::EXPORT_LIMIT);

        return $this->csv('audit-log', [
            'Date and time', 'User', 'User email', 'Action', 'IP address', 'Route', 'Account or search', 'Subject', 'Details',
        ], function ($write) use ($query) {
            foreach ($query->cursor() as $activity) {
                $properties = $activity->properties?->toArray() ?? [];

                $write([
                    $activity->created_at?->timezone('Africa/Lagos')->format('Y-m-d H:i:s'),
                    $activity->causer?->name ?? ($activity->causer_id ? 'Deleted user #'.$activity->causer_id : 'System'),
                    $activity->causer?->email ?? '',
                    $activity->description,
                    $properties['ip'] ?? '',
                    $properties['route'] ?? '',
                    $this->summariseInputs($properties),
                    $activity->subject_type ? class_basename($activity->subject_type).' #'.$activity->subject_id : '',
                    json_encode(collect($properties)->except(['ip', 'route', 'inputs'])->all(), JSON_UNESCAPED_SLASHES),
                ]);
            }
        });
    }

    // ------------------------------------------------------------------
    // Sign-ins
    // ------------------------------------------------------------------

    public function logins(Request $request)
    {
        $filters = $this->validatedFilters($request);
        $tab = $request->get('tab') === 'attempts' ? 'attempts' : 'sessions';

        $sessions = $tab === 'sessions'
            ? $this->sessionQuery($filters)->with('user')->paginate(50)->withQueryString()
            : null;

        $attempts = $tab === 'attempts'
            ? $this->attemptQuery($filters)->paginate(50)->withQueryString()
            : null;

        return view('backend.audit.logins', [
            'tab' => $tab,
            'sessions' => $sessions,
            'attempts' => $attempts,
            'filters' => $filters,
            'users' => User::withTrashed()->orderBy('name')->get(['id', 'name', 'email']),
            'failedLast24h' => LoginAttempt::where('successful', false)->where('created_at', '>=', now()->subDay())->count(),
            'signInsLast24h' => LoginHistory::where('logged_in_at', '>=', now()->subDay())->count(),
        ]);
    }

    public function exportLogins(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);

        if ($request->get('tab') === 'attempts') {
            $query = $this->attemptQuery($filters)->limit(self::EXPORT_LIMIT);

            return $this->csv('sign-in-attempts', [
                'Date and time', 'Email entered', 'IP address', 'Outcome', 'Reason', 'Browser',
            ], function ($write) use ($query) {
                foreach ($query->cursor() as $attempt) {
                    $write([
                        $attempt->created_at?->timezone('Africa/Lagos')->format('Y-m-d H:i:s'),
                        $attempt->email,
                        $attempt->ip_address,
                        $attempt->successful ? 'Passed password step' : 'Failed',
                        $attempt->failure_reason,
                        Str::limit((string) $attempt->user_agent, 200),
                    ]);
                }
            });
        }

        $query = $this->sessionQuery($filters)->with('user')->limit(self::EXPORT_LIMIT);

        return $this->csv('sign-ins', [
            'Signed in', 'Signed out', 'User', 'User email', 'IP address', 'Browser', 'Platform', 'Device',
        ], function ($write) use ($query) {
            foreach ($query->cursor() as $login) {
                $write([
                    $login->logged_in_at?->timezone('Africa/Lagos')->format('Y-m-d H:i:s'),
                    $login->logged_out_at?->timezone('Africa/Lagos')->format('Y-m-d H:i:s') ?? '',
                    $login->user?->name ?? 'Deleted user #'.$login->user_id,
                    $login->user?->email ?? '',
                    $login->ip_address,
                    $login->browser,
                    $login->platform,
                    $login->device_type,
                ]);
            }
        });
    }

    // ------------------------------------------------------------------
    // Queries
    // ------------------------------------------------------------------

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
            'ip' => ['nullable', 'string', 'max:45'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'outcome' => ['nullable', 'in:failed,passed'],
        ]);

        return [
            'user_id' => $validated['user_id'] ?? null,
            'search' => trim((string) ($validated['search'] ?? '')),
            'ip' => trim((string) ($validated['ip'] ?? '')),
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'outcome' => $validated['outcome'] ?? null,
        ];
    }

    private function activityQuery(array $filters)
    {
        return Activity::query()
            ->when($filters['user_id'], fn ($q, $id) => $q->where('causer_type', (new User())->getMorphClass())->where('causer_id', $id))
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $term = '%'.$filters['search'].'%';
                $q->where(function ($q) use ($term) {
                    $q->where('description', 'like', $term)->orWhere('properties', 'like', $term);
                });
            })
            ->when($filters['ip'] !== '', fn ($q) => $q->where('properties', 'like', '%"ip":"'.addcslashes($filters['ip'], '%_').'"%'))
            ->when($filters['date_from'], fn ($q, $d) => $q->where('created_at', '>=', $this->fromLagos($d)))
            ->when($filters['date_to'], fn ($q, $d) => $q->where('created_at', '<', $this->fromLagos($d)->addDay()))
            ->latest()
            ->orderByDesc('id');
    }

    private function sessionQuery(array $filters)
    {
        return LoginHistory::query()
            ->when($filters['user_id'], fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['ip'] !== '', fn ($q) => $q->where('ip_address', $filters['ip']))
            ->when($filters['date_from'], fn ($q, $d) => $q->where('logged_in_at', '>=', $this->fromLagos($d)))
            ->when($filters['date_to'], fn ($q, $d) => $q->where('logged_in_at', '<', $this->fromLagos($d)->addDay()))
            ->orderByDesc('logged_in_at')
            ->orderByDesc('id');
    }

    private function attemptQuery(array $filters)
    {
        $email = $filters['user_id'] ? User::withTrashed()->find($filters['user_id'])?->email : null;

        return LoginAttempt::query()
            ->when($email, fn ($q) => $q->where('email', $email))
            ->when($filters['search'] !== '', fn ($q) => $q->where('email', 'like', '%'.$filters['search'].'%'))
            ->when($filters['ip'] !== '', fn ($q) => $q->where('ip_address', $filters['ip']))
            ->when($filters['outcome'] === 'failed', fn ($q) => $q->where('successful', false))
            ->when($filters['outcome'] === 'passed', fn ($q) => $q->where('successful', true))
            ->when($filters['date_from'], fn ($q, $d) => $q->where('created_at', '>=', $this->fromLagos($d)))
            ->when($filters['date_to'], fn ($q, $d) => $q->where('created_at', '<', $this->fromLagos($d)->addDay()))
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * Dates are picked in Lagos time; convert to the app's stored time zone.
     */
    private function fromLagos(string $date): Carbon
    {
        return Carbon::parse($date, 'Africa/Lagos')->startOfDay()->timezone(config('app.timezone'));
    }

    public static function summariseInputs(array $properties): string
    {
        $inputs = $properties['inputs'] ?? [];
        if (! is_array($inputs) || $inputs === []) {
            return '';
        }

        return collect($inputs)
            ->map(fn ($value, $key) => $key.': '.(is_array($value) ? implode(',', $value) : $value))
            ->implode('; ');
    }

    private function csv(string $name, array $header, callable $rows): StreamedResponse
    {
        $filename = $name.'-'.now()->timezone('Africa/Lagos')->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 marker so Excel shows names correctly
            fputcsv($out, $header, ',', '"', '');

            $rows(function (array $row) use ($out) {
                // Stop Excel treating a cell that starts with = + - @ as a formula.
                $row = array_map(function ($value) {
                    $value = (string) $value;

                    return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
                }, $row);

                fputcsv($out, $row, ',', '"', '');
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
