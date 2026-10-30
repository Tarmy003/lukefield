<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

/**
 * Makes archive access match an approved memo exactly.
 *
 * Works from whatever state the archive is in:
 *  - an approved person who is missing is created (with their LMS password hash);
 *  - an approved person who exists is given exactly the approved role and made active;
 *  - anyone active who is NOT on the memo is listed, and with --deactivate-others
 *    is deactivated, stripped of roles and signed out. Nobody is deleted, so the
 *    record of who had access, and when, is kept for audit;
 *  - roles from the earlier draft that the memo no longer uses are removed once
 *    nobody holds them.
 *
 * Everything runs in one database transaction, and the run is refused if it
 * would leave no active Super Admin. An evidence CSV (no password hashes) is
 * saved to storage/app/evidence/.
 *
 * Usage:
 *   php artisan archive:reconcile-users <file> --approval-ref="LFC/MEMO/2026/0014" --dry-run
 *   php artisan archive:reconcile-users <file> --approval-ref="LFC/MEMO/2026/0014" --deactivate-others
 */
class ArchiveReconcileUsers extends Command
{
    protected $signature = 'archive:reconcile-users
        {file : Tab-delimited approved list: name, email, phone, department, role, password_hash}
        {--approval-ref= : Reference of the approved memo (required)}
        {--deactivate-others : Deactivate active users who are not on the approved list}
        {--dry-run : Show what would change, without changing anything}';

    protected $description = 'Bring archive users and roles exactly in line with an approved access memo';

    private const GUARD = 'web';

    private const VIEWER_PERMISSIONS = ['archive index', 'archive statements', 'customers search', 'customers show'];

    private const NEW_PERMISSIONS = ['archive statements', 'archive manage'];

    /** Roles that existed before onboarding; they keep their own rights. */
    private const EXISTING_ROLES = ['Audit and Control', 'Fincon', 'Super Admin'];

    /** Roles created by the earlier 32-person draft that the approved memo does not use. */
    private const RETIRED_ROLES = ['Corporate Sales', 'Retail Sales', 'Public Sector', 'Legal', 'Human Resources', 'Risk'];

    private const REQUIRED_COLUMNS = ['name', 'email', 'phone', 'department', 'role', 'password_hash'];

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        $approvalRef = trim((string) $this->option('approval-ref'));

        if ($approvalRef === '') {
            $this->error('--approval-ref is required: give the reference of the approved memo.');
            return self::FAILURE;
        }
        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Cannot read the file: {$path}");
            return self::FAILURE;
        }

        try {
            $approved = $this->readFile($path);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $approvedByEmail = collect($approved)->keyBy('email');

        $users = User::withTrashed()->with('roles')->get()->keyBy(fn ($u) => strtolower($u->email));

        // ---------- Plan ----------
        $plan = [];

        foreach ($approved as $row) {
            $user = $users->get($row['email']);

            if (! $user) {
                $plan[] = ['action' => 'create', 'row' => $row, 'user' => null, 'from' => '-'];
                continue;
            }

            $currentRoles = $user->roles->pluck('name')->sort()->values()->all();
            $needsRole = $currentRoles !== [$row['role']];
            $needsActive = ! $user->is_active || $user->trashed();

            $plan[] = [
                'action' => ($needsRole || $needsActive) ? 'update' : 'keep',
                'row' => $row,
                'user' => $user,
                'from' => ($currentRoles === [] ? '(none)' : implode(', ', $currentRoles)) . ($needsActive ? ' [inactive]' : ''),
            ];
        }

        $others = $users->filter(fn ($u) => ! $approvedByEmail->has(strtolower($u->email)) && $u->is_active && ! $u->trashed());

        foreach ($others as $user) {
            $plan[] = [
                'action' => $this->option('deactivate-others') ? 'deactivate' : 'not on memo (left active)',
                'row' => ['name' => $user->name, 'email' => strtolower($user->email), 'role' => '-', 'department' => '-'],
                'user' => $user,
                'from' => $user->roles->pluck('name')->implode(', ') ?: '(none)',
            ];
        }

        // ---------- Safety: an active Super Admin must remain ----------
        $superAdminAfter = collect($plan)->contains(
            fn ($p) => in_array($p['action'], ['create', 'update', 'keep'], true) && $p['row']['role'] === 'Super Admin'
        ) || $others->contains(
            fn ($u) => ! $this->option('deactivate-others') && $u->roles->pluck('name')->contains('Super Admin')
        );

        if (! $superAdminAfter) {
            $this->error('Refused: this would leave no active Super Admin.');
            return self::FAILURE;
        }

        // ---------- Report ----------
        $counts = collect($plan)->countBy('action');

        $this->line('');
        $this->info('Archive access reconciliation');
        $this->line("  Approval reference: {$approvalRef}");
        $this->line('  Approved users:     ' . count($approved));
        foreach ($counts as $action => $n) {
            $this->line(sprintf('  %-19s %d', ucfirst($action) . ':', $n));
        }
        $this->line('');

        $this->table(
            ['Name', 'Email', 'Current role', 'Approved role', 'Last sign-in', 'Action'],
            collect($plan)->map(fn ($p) => [
                $p['row']['name'],
                $p['row']['email'],
                $p['from'],
                $p['row']['role'],
                $p['user']?->last_login_at ? (string) $p['user']->last_login_at : 'never',
                strtoupper($p['action']),
            ])->all()
        );

        if ($others->isNotEmpty() && ! $this->option('deactivate-others')) {
            $this->warn(sprintf(
                '  %d active user(s) are not on the approved memo. Re-run with --deactivate-others to remove their access.',
                $others->count()
            ));
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run finished: nothing was changed.');
            return self::SUCCESS;
        }

        // ---------- Apply ----------
        $done = [];
        $retired = [];

        try {
            DB::transaction(function () use ($plan, $approved, &$done, &$retired) {
                $this->ensurePermissions();
                $this->ensureRoles(collect($approved)->pluck('role')->unique()->all());

                $now = now();

                foreach ($plan as $p) {
                    $row = $p['row'];

                    switch ($p['action']) {
                        case 'create':
                            $id = DB::table('users')->insertGetId([
                                'name'                  => $row['name'],
                                'email'                 => $row['email'],
                                'phone'                 => $row['phone'],
                                'password'              => $row['password_hash'],
                                'email_verified_at'     => $now,
                                'is_active'             => 1,
                                'two_factor_enabled'    => 1,
                                'failed_login_attempts' => 0,
                                'created_at'            => $now,
                                'updated_at'            => $now,
                            ]);
                            User::findOrFail($id)->syncRoles([$row['role']]);
                            $done[] = ['Created', $id, $row, '-', $row['role']];
                            break;

                        case 'update':
                            $user = $p['user'];
                            if ($user->trashed()) {
                                $user->restore();
                            }
                            DB::table('users')->where('id', $user->id)->update(['is_active' => 1, 'updated_at' => $now]);
                            $user->syncRoles([$row['role']]);
                            $done[] = ['Role set / reactivated', $user->id, $row, $p['from'], $row['role']];
                            break;

                        case 'keep':
                            $done[] = ['No change', $p['user']->id, $row, $p['from'], $row['role']];
                            break;

                        case 'deactivate':
                            $user = $p['user'];
                            DB::table('users')->where('id', $user->id)->update([
                                'is_active'      => 0,
                                'remember_token' => null,
                                'updated_at'     => $now,
                            ]);
                            $user->syncRoles([]);
                            $this->endSessions($user->id);
                            $done[] = ['Deactivated - not on approved memo', $user->id, $row, $p['from'], '(none)'];
                            break;

                        default:
                            $done[] = ['Left active - not on memo', $p['user']->id, $row, $p['from'], $p['from']];
                    }
                }

                app(PermissionRegistrar::class)->forgetCachedPermissions();

                foreach (self::RETIRED_ROLES as $name) {
                    $role = Role::where('name', $name)->where('guard_name', self::GUARD)->first();
                    if ($role && $role->users()->count() === 0) {
                        $role->delete();
                        $retired[] = $name;
                    }
                }
            });
        } catch (Throwable $e) {
            $this->error('Reconciliation failed. Nothing was changed.');
            $this->line('  ' . $e->getMessage());
            return self::FAILURE;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $evidence = $this->writeEvidence($approvalRef, $done, $retired);

        $this->line('');
        $this->info('Done. Archive access now matches the approved list.');
        if ($retired !== []) {
            $this->line('  Roles removed (no longer used): ' . implode(', ', $retired));
        }
        $this->line("  Evidence file: {$evidence}");
        $this->line('  Now delete the input file: it contains password hashes.');

        return self::SUCCESS;
    }

    private function readFile(string $path): array
    {
        $handle = fopen($path, 'rb');
        $header = fgetcsv($handle, 0, "\t", "\x01", '');
        if ($header === false) {
            throw new RuntimeException('The file is empty.');
        }
        $header = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $header);

        $missing = array_diff(self::REQUIRED_COLUMNS, $header);
        if ($missing !== []) {
            throw new RuntimeException('Missing column(s): ' . implode(', ', $missing));
        }

        $rows = [];
        $errors = [];
        $seen = [];
        $line = 1;

        while (($fields = fgetcsv($handle, 0, "\t", "\x01", '')) !== false) {
            $line++;
            if ($fields === [null] || trim(implode('', $fields)) === '') {
                continue;
            }
            if (count($fields) !== count($header)) {
                $errors[] = "Line {$line}: expected " . count($header) . ' columns, found ' . count($fields);
                continue;
            }

            $r = array_map('trim', array_combine($header, $fields));
            $r['email'] = strtolower($r['email']);
            $r['phone'] = $r['phone'] === '' ? null : $r['phone'];

            if ($r['name'] === '') {
                $errors[] = "Line {$line}: name is empty";
            }
            if (! filter_var($r['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Line {$line}: '{$r['email']}' is not a valid email";
            }
            if ($r['role'] === '') {
                $errors[] = "Line {$line}: role is empty";
            }
            if (in_array($r['role'], self::RETIRED_ROLES, true)) {
                $errors[] = "Line {$line}: role '{$r['role']}' is retired and cannot be assigned";
            }
            if (! preg_match('/^\$2[aby]\$\d{2}\$[.\/A-Za-z0-9]{53}$/', $r['password_hash'])) {
                $errors[] = "Line {$line}: password_hash for {$r['email']} is not a bcrypt hash";
            }
            if (isset($seen[$r['email']])) {
                $errors[] = "Line {$line}: {$r['email']} also appears on line {$seen[$r['email']]}";
            }
            $seen[$r['email']] = $line;

            $rows[] = $r;
        }
        fclose($handle);

        if ($errors !== []) {
            throw new RuntimeException("The file has problems. Nothing was changed:\n  " . implode("\n  ", $errors));
        }
        if ($rows === []) {
            throw new RuntimeException('The file has no user rows.');
        }

        return $rows;
    }

    private function ensurePermissions(): void
    {
        $hasGroup = Schema::hasColumn('permissions', 'group_name');

        foreach (self::NEW_PERMISSIONS as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD],
                $hasGroup ? ['group_name' => 'archive'] : []
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['Audit and Control', 'Fincon'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', self::GUARD)->first()?->givePermissionTo('archive statements');
        }

        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', self::GUARD)->first();
        if (! $superAdmin) {
            throw new RuntimeException('The Super Admin role does not exist.');
        }
        $superAdmin->givePermissionTo(['archive statements', 'archive manage']);
    }

    private function ensureRoles(array $roleNames): void
    {
        foreach ($roleNames as $name) {
            if (in_array($name, self::EXISTING_ROLES, true)) {
                if (! Role::where('name', $name)->where('guard_name', self::GUARD)->exists()) {
                    throw new RuntimeException("The role '{$name}' does not exist.");
                }
                continue;
            }

            Role::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD])
                ->syncPermissions(self::VIEWER_PERMISSIONS);
        }
    }

    /** Signs the user out everywhere when sessions are stored in the database. */
    private function endSessions(int $userId): void
    {
        if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }
    }

    private function writeEvidence(string $approvalRef, array $done, array $retired): string
    {
        $lines = [];
        $lines[] = ['Archive Repository - access reconciliation record'];
        $lines[] = ['Approval reference', $approvalRef];
        $lines[] = ['Run at', now()->format('Y-m-d H:i:s')];
        $lines[] = ['Server user', get_current_user()];
        $lines[] = ['Roles removed', $retired === [] ? 'none' : implode('; ', $retired)];
        $lines[] = [];
        $lines[] = ['Result', 'Archive user ID', 'Name', 'Email', 'Unit', 'Role before', 'Role after'];

        foreach ($done as [$result, $id, $row, $before, $after]) {
            $lines[] = [$result, $id, $row['name'], $row['email'], $row['department'], $before, $after];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($lines as $l) {
            fputcsv($handle, $l, ',', '"', '');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $file = 'evidence/archive_access_reconciliation_' . now()->format('Ymd_His') . '.csv';
        Storage::disk('local')->put($file, $csv);

        return Storage::disk('local')->path($file);
    }
}
