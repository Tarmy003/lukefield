<?php
// app/Http/Controllers/Admin/SystemHealthController.php

namespace App\Http\Controllers\Admin;

use App\Mail\SystemTestMail;
use App\Models\Customer;
use App\Models\LoginAttempt;
use App\Models\NominalAccountsTrxPostingHistory;
use App\Models\SavingsAndCurrentAccount;
use App\Models\User;
use App\Services\PasswordService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * One page that answers "is the archive healthy and safe right now?".
 * Super Admin only (permission: system health).
 *
 * Each check returns a status: ok, warn, fail or info.
 */
class SystemHealthController extends Controller
{
    public function __construct(private PasswordService $passwords)
    {
        $this->middleware(['permission:system health']);
    }

    public function index(Request $request)
    {
        if ($request->boolean('refresh')) {
            Cache::forget('health:archive-stats');
            Cache::forget('health:ssl');
        }

        $sections = [
            'Application' => $this->applicationChecks(),
            'Database and archive data' => $this->databaseChecks(),
            'Email (sign-in codes depend on it)' => $this->mailChecks(),
            'Server storage' => $this->storageChecks(),
            'Certificate, scheduler and backups' => $this->operationsChecks(),
            'Access and sign-in security' => $this->securityChecks(),
        ];

        $overall = $this->worstStatus(collect($sections)->flatten(1)->pluck('status')->all());

        $lockedUsers = User::where('locked_until', '>', now())->orderBy('name')->get();

        $dormantUsers = $this->dormantUsersQuery()->orderBy('name')->get();

        $expiringUsers = User::where('is_active', true)
            ->whereNotNull('password_changed_at')
            ->where('password_changed_at', '<=', now()->subDays(
                (int) config('security.password.expiry_days', 90) - (int) config('security.password.warn_days', 14)
            ))
            ->orderBy('password_changed_at')
            ->get();

        $topFailedIps = LoginAttempt::where('successful', false)
            ->where('created_at', '>=', now()->subDay())
            ->select('ip_address', DB::raw('COUNT(*) as attempts'), DB::raw('COUNT(DISTINCT email) as emails'))
            ->groupBy('ip_address')
            ->orderByDesc('attempts')
            ->limit(5)
            ->get();

        return view('backend.system.health', [
            'sections' => $sections,
            'overall' => $overall,
            'lockedUsers' => $lockedUsers,
            'dormantUsers' => $dormantUsers,
            'expiringUsers' => $expiringUsers,
            'topFailedIps' => $topFailedIps,
            'recentErrors' => $this->recentLogErrors(),
            'lastTestEmail' => Cache::get('health:last-test-email'),
            'checkedAt' => now(),
            'passwords' => $this->passwords,
        ]);
    }

    public function sendTestEmail(Request $request)
    {
        $user = $request->user();
        $started = microtime(true);

        try {
            Mail::to($user->email)->send(new SystemTestMail($user, now()));
            $result = [
                'ok' => true,
                'at' => now()->toIso8601String(),
                'to' => $user->email,
                'by' => $user->name,
                'ms' => (int) round((microtime(true) - $started) * 1000),
                'error' => null,
            ];
        } catch (Throwable $e) {
            $result = [
                'ok' => false,
                'at' => now()->toIso8601String(),
                'to' => $user->email,
                'by' => $user->name,
                'ms' => (int) round((microtime(true) - $started) * 1000),
                'error' => Str::limit($e->getMessage(), 300),
            ];
        }

        Cache::put('health:last-test-email', $result, now()->addDays(30));

        activity()
            ->causedBy($user)
            ->withProperties(['ok' => $result['ok'], 'error' => $result['error'], 'ip' => $request->ip()])
            ->log('System Health test email '.($result['ok'] ? 'sent' : 'failed'));

        return redirect()->route('system.health')->with(
            $result['ok'] ? 'success' : 'error',
            $result['ok']
                ? "Test email sent to {$user->email} in {$result['ms']} ms. Check that it arrived."
                : 'The test email failed: '.$result['error']
        );
    }

    // ------------------------------------------------------------------
    // Checks
    // ------------------------------------------------------------------

    private function applicationChecks(): array
    {
        $checks = [];

        $env = app()->environment();
        $checks[] = $this->check('Environment', $env === 'production' ? 'ok' : 'warn', $env,
            $env === 'production' ? null : 'Set APP_ENV=production in .env on the live server.');

        $debug = (bool) config('app.debug');
        $checks[] = $this->check('Debug mode', $debug ? 'fail' : 'ok', $debug ? 'ON' : 'Off',
            $debug ? 'APP_DEBUG=true shows code, settings and data to anyone who triggers an error. Set APP_DEBUG=false now.' : null);

        $url = (string) config('app.url');
        $https = Str::startsWith($url, 'https://');
        $checks[] = $this->check('Site address', $https ? 'ok' : 'warn', $url,
            $https ? null : 'APP_URL should start with https:// so links in emails are secure.');

        $checks[] = $this->check('Encryption key', config('app.key') ? 'ok' : 'fail',
            config('app.key') ? 'Set' : 'Missing', config('app.key') ? null : 'Run php artisan key:generate.');

        $checks[] = $this->check('Software versions', 'info', 'PHP '.PHP_VERSION.' / Laravel '.app()->version());

        $checks[] = $this->check('Time zone', 'info', config('app.timezone').' (server time '.now()->format('j M Y H:i').')');

        $idle = (int) config('security.idle_timeout', 15);
        $checks[] = $this->check('Inactivity sign-out', $idle <= 15 ? 'ok' : 'warn', "{$idle} minutes");

        return $checks;
    }

    private function databaseChecks(): array
    {
        $checks = [];

        try {
            $started = microtime(true);
            DB::select('select 1');
            $ms = (int) round((microtime(true) - $started) * 1000);
            $checks[] = $this->check('Connection', $ms < 200 ? 'ok' : 'warn',
                DB::connection()->getDriverName().' / '.DB::connection()->getDatabaseName()." ({$ms} ms)");
        } catch (Throwable $e) {
            return [$this->check('Connection', 'fail', 'Cannot connect', Str::limit($e->getMessage(), 200))];
        }

        try {
            $size = $this->databaseSizeBytes();
            if ($size !== null) {
                $checks[] = $this->check('Database size', 'info', $this->bytes($size));
            }
        } catch (Throwable $e) {
            // Size is informational only.
        }

        $stats = Cache::remember('health:archive-stats', now()->addMinutes(10), function () {
            $stats = [
                'accounts' => SavingsAndCurrentAccount::count(),
                'transactions' => NominalAccountsTrxPostingHistory::count(),
                'customers' => Customer::count(),
                'oldest' => NominalAccountsTrxPostingHistory::min('transaction_date'),
                'newest' => NominalAccountsTrxPostingHistory::max('transaction_date'),
            ];

            return $stats;
        });

        $checks[] = $this->check('Archived accounts', $stats['accounts'] > 0 ? 'ok' : 'warn', number_format($stats['accounts']),
            $stats['accounts'] > 0 ? null : 'No accounts are loaded.');
        $checks[] = $this->check('Archived transactions', $stats['transactions'] > 0 ? 'ok' : 'warn', number_format($stats['transactions']),
            $stats['transactions'] > 0 ? null : 'No transactions are loaded.');
        $checks[] = $this->check('Customers', 'info', number_format($stats['customers']));

        if ($stats['oldest'] && $stats['newest']) {
            $checks[] = $this->check('Transaction dates held', 'info',
                Carbon::parse($stats['oldest'])->format('j M Y').' to '.Carbon::parse($stats['newest'])->format('j M Y'));
        }

        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));

            $checks[] = $this->check('Database changes (migrations)', $pending === [] ? 'ok' : 'warn',
                $pending === [] ? 'All applied' : count($pending).' not recorded as run',
                $pending === [] ? null : implode(', ', $pending));
        } catch (Throwable $e) {
            // Not critical.
        }

        return $checks;
    }

    private function mailChecks(): array
    {
        $checks = [];

        $mailer = (string) config('mail.default');
        $badMailer = in_array($mailer, ['log', 'array'], true);
        $checks[] = $this->check('Mail driver', $badMailer ? 'fail' : 'ok', $mailer,
            $badMailer ? 'With this driver no email leaves the server, so nobody can receive a sign-in code.' : null);

        $host = config("mail.mailers.{$mailer}.host");
        if ($host) {
            $checks[] = $this->check('Mail server', 'info', $host.':'.config("mail.mailers.{$mailer}.port"));
        }

        $from = (string) config('mail.from.address');
        $checks[] = $this->check('Sent from', Str::endsWith($from, 'example.com') || $from === '' ? 'warn' : 'info', $from ?: 'Not set');

        $last = Cache::get('health:last-test-email');
        if ($last) {
            $at = Carbon::parse($last['at']);
            $checks[] = $this->check('Last test email', $last['ok'] ? 'ok' : 'fail',
                ($last['ok'] ? 'Sent' : 'Failed').' '.$at->diffForHumans().' by '.$last['by'],
                $last['ok'] ? null : $last['error']);
        } else {
            $checks[] = $this->check('Last test email', 'info', 'Not yet tested', 'Use "Send test email" below.');
        }

        return $checks;
    }

    private function storageChecks(): array
    {
        $checks = [];

        $total = @disk_total_space(base_path());
        $free = @disk_free_space(base_path());
        if ($total && $free !== false) {
            $usedPercent = (int) round((1 - $free / $total) * 100);
            $status = $usedPercent < 80 ? 'ok' : ($usedPercent < 90 ? 'warn' : 'fail');
            $checks[] = $this->check('Disk space', $status,
                "{$usedPercent}% used, ".$this->bytes((int) $free).' free of '.$this->bytes((int) $total),
                $status === 'ok' ? null : 'The archive and its imports need room. Clear old files or add storage.');
        }

        foreach (['Storage folder' => storage_path(), 'Cache folder' => base_path('bootstrap/cache')] as $label => $path) {
            $writable = is_writable($path);
            $checks[] = $this->check($label.' writable', $writable ? 'ok' : 'fail', $writable ? 'Yes' : 'No',
                $writable ? null : "The web server cannot write to {$path}.");
        }

        $log = storage_path('logs/laravel.log');
        if (is_file($log)) {
            $size = filesize($log);
            $checks[] = $this->check('Error log size', $size < 100 * 1024 * 1024 ? 'ok' : 'warn', $this->bytes($size),
                $size < 100 * 1024 * 1024 ? null : 'The log is large. Archive and clear it.');
        }

        return $checks;
    }

    private function operationsChecks(): array
    {
        $checks = [];

        $checks[] = $this->sslCheck();

        $heartbeat = Cache::get('scheduler:heartbeat');
        if ($heartbeat && Carbon::parse($heartbeat)->gt(now()->subMinutes(5))) {
            $checks[] = $this->check('Scheduled tasks', 'ok', 'Running (last '.Carbon::parse($heartbeat)->diffForHumans().')');
        } else {
            $checks[] = $this->check('Scheduled tasks', 'warn',
                $heartbeat ? 'Stopped '.Carbon::parse($heartbeat)->diffForHumans() : 'Not running',
                'The server cron job that runs "php artisan schedule:run" every minute is missing or failing.');
        }

        $checks[] = $this->backupCheck();

        $queue = (string) config('queue.default');
        $failedJobs = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0;
        $checks[] = $this->check('Background jobs', $failedJobs === 0 ? 'ok' : 'warn',
            "Queue: {$queue}, failed jobs: {$failedJobs}",
            $failedJobs === 0 ? null : 'Review with php artisan queue:failed.');

        return $checks;
    }

    private function securityChecks(): array
    {
        $checks = [];

        $failed24h = LoginAttempt::where('successful', false)->where('created_at', '>=', now()->subDay())->count();
        $checks[] = $this->check('Failed sign-ins (24 hours)', $failed24h < 20 ? 'ok' : 'warn', number_format($failed24h),
            $failed24h < 20 ? null : 'Higher than usual. Check the IP addresses below.');

        $locked = User::where('locked_until', '>', now())->count();
        $checks[] = $this->check('Locked accounts', $locked === 0 ? 'ok' : 'warn', (string) $locked);

        $mustChange = User::where('is_active', true)->where(function ($q) {
            $q->where('must_change_password', true)->orWhereNull('password_changed_at');
        })->count();
        $checks[] = $this->check('Users yet to set a new password', $mustChange === 0 ? 'ok' : 'info', (string) $mustChange);

        $dormant = $this->dormantUsersQuery()->count();
        $checks[] = $this->check('Dormant accounts', $dormant === 0 ? 'ok' : 'warn', (string) $dormant,
            $dormant === 0 ? null : 'Active accounts with no sign-in for '.config('security.dormant_days', 90).' days. Deactivate any that are no longer needed.');

        $superAdmins = User::role('Super Admin')->where('is_active', true)->pluck('name');
        $checks[] = $this->check('Active Super Admins', $superAdmins->count() <= 2 ? 'ok' : 'warn',
            $superAdmins->count().': '.$superAdmins->implode(', '),
            $superAdmins->count() <= 2 ? null : 'Keep Super Admin to as few people as possible.');

        $tokens = Schema::hasTable('personal_access_tokens') ? DB::table('personal_access_tokens')->count() : 0;
        $checks[] = $this->check('API tokens issued', $tokens === 0 ? 'ok' : 'warn', (string) $tokens,
            $tokens === 0 ? null : 'Each API token gives access without the emailed sign-in code. Confirm each one is approved.');

        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            $activeSessions = DB::table(config('session.table', 'sessions'))
                ->whereNotNull('user_id')
                ->where('last_activity', '>=', now()->subMinutes((int) config('security.idle_timeout', 15))->getTimestamp())
                ->distinct()
                ->count('user_id');
            $checks[] = $this->check('Users signed in now', 'info', (string) $activeSessions);
        }

        return $checks;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function sslCheck(): array
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $host || ! Str::startsWith((string) config('app.url'), 'https://') || in_array($host, ['localhost', '127.0.0.1'], true)) {
            return $this->check('SSL certificate', 'info', 'Not checked (site address is not https)');
        }

        $result = Cache::remember('health:ssl', now()->addHour(), function () use ($host) {
            try {
                $context = stream_context_create(['ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'SNI_enabled' => true,
                    'peer_name' => $host,
                ]]);

                $client = @stream_socket_client("ssl://{$host}:443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
                if (! $client) {
                    return ['error' => $errstr ?: 'Could not connect'];
                }

                $params = stream_context_get_params($client);
                fclose($client);

                $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate'] ?? '');

                return $cert ? ['expires' => $cert['validTo_time_t'], 'issuer' => $cert['issuer']['O'] ?? ($cert['issuer']['CN'] ?? '')] : ['error' => 'Unreadable certificate'];
            } catch (Throwable $e) {
                return ['error' => $e->getMessage()];
            }
        });

        if (isset($result['error'])) {
            return $this->check('SSL certificate', 'warn', 'Could not check', Str::limit($result['error'], 200));
        }

        $expires = Carbon::createFromTimestamp($result['expires']);
        $days = (int) floor(now()->diffInDays($expires, false));
        $status = $days > 30 ? 'ok' : ($days > 14 ? 'warn' : 'fail');

        return $this->check('SSL certificate', $status,
            'Expires '.$expires->format('j M Y')." ({$days} days)".($result['issuer'] ? ', '.$result['issuer'] : ''),
            $status === 'ok' ? null : 'Renew the certificate before it expires, or staff will see a security warning and cannot sign in safely.');
    }

    private function backupCheck(): array
    {
        try {
            $name = (string) config('backup.backup.name', config('app.name'));
            $disks = (array) config('backup.backup.destination.disks', ['local']);
            $newest = null;

            foreach ($disks as $disk) {
                $storage = Storage::disk($disk);
                foreach ($storage->files($name) as $file) {
                    if (Str::endsWith($file, '.zip')) {
                        $time = $storage->lastModified($file);
                        $newest = max($newest ?? 0, $time);
                    }
                }
            }

            if ($newest === null) {
                return $this->check('Application backups', 'warn', 'None found',
                    'No database backup made by the application was found. Make sure DigitalOcean droplet backups are on, and plan a daily database backup.');
            }

            $at = Carbon::createFromTimestamp($newest);

            return $this->check('Application backups', $at->gt(now()->subDays(2)) ? 'ok' : 'warn', 'Latest '.$at->diffForHumans());
        } catch (Throwable $e) {
            return $this->check('Application backups', 'warn', 'Could not check', Str::limit($e->getMessage(), 200));
        }
    }

    private function recentLogErrors(): array
    {
        $log = storage_path('logs/laravel.log');
        if (! is_file($log) || ! is_readable($log)) {
            return [];
        }

        $size = filesize($log);
        $handle = fopen($log, 'rb');
        fseek($handle, max(0, $size - 262144));
        $chunk = stream_get_contents($handle);
        fclose($handle);

        preg_match_all('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})[^\]]*\]\s+\w+\.(ERROR|CRITICAL|ALERT|EMERGENCY):\s+(.*)$/m', $chunk, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(fn ($m) => [
                'at' => $m[1],
                'level' => $m[2],
                'message' => Str::limit(trim(preg_replace('/\s*\{.*$/s', '', $m[3])), 300),
            ])
            ->reverse()
            ->take((int) config('security.health_log_lines', 25))
            ->values()
            ->all();
    }

    private function dormantUsersQuery()
    {
        $cutoff = now()->subDays((int) config('security.dormant_days', 90));

        return User::where('is_active', true)
            ->where(function ($q) use ($cutoff) {
                $q->where('last_login_at', '<', $cutoff)
                    ->orWhere(function ($q) use ($cutoff) {
                        $q->whereNull('last_login_at')->where('created_at', '<', $cutoff);
                    });
            });
    }

    private function databaseSizeBytes(): ?int
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $row = DB::selectOne('SELECT SUM(data_length + index_length) AS size FROM information_schema.tables WHERE table_schema = DATABASE()');

            return $row && $row->size ? (int) $row->size : null;
        }

        if ($driver === 'sqlite') {
            $path = DB::connection()->getDatabaseName();

            return is_file($path) ? filesize($path) : null;
        }

        return null;
    }

    private function check(string $label, string $status, string $value, ?string $detail = null): array
    {
        return compact('label', 'status', 'value', 'detail');
    }

    private function worstStatus(array $statuses): string
    {
        foreach (['fail', 'warn'] as $status) {
            if (in_array($status, $statuses, true)) {
                return $status;
            }
        }

        return 'ok';
    }

    private function bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return round($value, $i >= 3 ? 2 : 1).' '.$units[$i];
    }
}
