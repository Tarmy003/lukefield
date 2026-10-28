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
 * Creates archive users from an approved list, keeping each person's existing
 * LMS password (copied as its bcrypt hash; the plain password is never seen).
 *
 * Design decisions:
 *  - --approval-ref is mandatory and is written into the evidence file, so
 *    every account created can be traced to the approval that authorised it.
 *  - Department roles are created here, each with view-and-statement rights
 *    only. Importing data, clearing data and import logs move behind a new
 *    "archive manage" permission held by Super Admin alone.
 *  - Users already in the archive (matched by email) are skipped, never
 *    changed, and listed.
 *  - Two-factor login is switched on for every new user, so a password alone
 *    is not enough to get in.
 *  - Everything runs in one database transaction: roles, permissions and users
 *    are created together or not at all.
 *  - An evidence CSV (no password hashes) is saved to
 *    storage/app/evidence/ for the audit pack.
 *
 * Usage:
 *   php artisan archive:import-users storage/imports/archive_users_approved.tsv --approval-ref="LFCL/IT/2026/xxx" --dry-run
 *   php artisan archive:import-users storage/imports/archive_users_approved.tsv --approval-ref="LFCL/IT/2026/xxx"
 */
class ArchiveImportUsers extends Command
{
    protected $signature = 'archive:import-users
        {file : Tab-delimited list: name, email, phone, department, role, password_hash}
        {--approval-ref= : Reference of the signed approval memo (required)}
        {--dry-run : Check the file and show what would happen, without changing anything}';

    protected $description = 'Create approved archive users with their existing LMS passwords and department roles';

    private const GUARD = 'web';

    /** Read-only rights every department role gets. */
    private const VIEWER_PERMISSIONS = ['archive index', 'archive statements', 'customers search', 'customers show'];

    /** New permissions introduced by this change. */
    private const NEW_PERMISSIONS = [
        'archive statements' => 'Generate, preview and export statements of account',
        'archive manage'     => 'Import data, clear data and view import logs (Super Admin only)',
    ];

    /** Roles that already exist and keep their current rights; they gain statement access. */
    private const EXISTING_ROLES = ['Audit and Control', 'Fincon', 'Super Admin'];

    private const REQUIRED_COLUMNS = ['name', 'email', 'phone', 'department', 'role', 'password_hash'];

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        $approvalRef = trim((string) $this->option('approval-ref'));

        if ($approvalRef === '') {
            $this->error('--approval-ref is required: give the reference of the signed approval memo.');
            return self::FAILURE;
        }

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("Cannot read the file: {$path}");
            return self::FAILURE;
        }

        try {
            $rows = $this->readFile($path);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $existingEmails = User::withTrashed()
            ->pluck('email')
            ->map(fn ($e) => strtolower($e))
            ->flip();

        $toCreate = [];
        $skipped = [];
        foreach ($rows as $row) {
            if (isset($existingEmails[$row['email']])) {
                $skipped[] = $row;
            } else {
                $toCreate[] = $row;
            }
        }

        $newRoles = collect($rows)->pluck('role')->unique()
            ->reject(fn ($r) => in_array($r, self::EXISTING_ROLES, true))
            ->values()->all();

        $this->line('');
        $this->info('Archive user onboarding');
        $this->line("  Approval reference: {$approvalRef}");
        $this->line('  Users in file:      ' . count($rows));
        $this->line('  To create:          ' . count($toCreate));
        $this->line('  Already exist:      ' . count($skipped) . ' (will be skipped, not changed)');
        $this->line('  New roles:          ' . ($newRoles === [] ? 'none' : implode(', ', $newRoles)));
        $this->line('');

        $this->table(
            ['Name', 'Email', 'Department', 'Archive role', 'Action'],
            collect($rows)->map(fn ($r) => [
                $r['name'], $r['email'], $r['department'], $r['role'],
                isset($existingEmails[$r['email']]) ? 'SKIP (exists)' : 'create',
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run finished: nothing was changed. Run again without --dry-run to create the users.');
            return self::SUCCESS;
        }

        $created = [];

        try {
            DB::transaction(function () use ($rows, $toCreate, &$created) {
                $this->ensurePermissions();
                $this->ensureRoles(collect($rows)->pluck('role')->unique()->all());

                $now = now();

                foreach ($toCreate as $row) {
                    // Inserted directly so the existing bcrypt hash is stored as-is
                    // and not passed through the model's password hashing.
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

                    User::findOrFail($id)->assignRole($row['role']);

                    $created[] = $row + ['id' => $id, 'created_at' => $now->format('Y-m-d H:i:s')];
                }
            });
        } catch (Throwable $e) {
            $this->error('Onboarding failed. Nothing was changed.');
            $this->line('  ' . $e->getMessage());
            return self::FAILURE;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $evidence = $this->writeEvidence($approvalRef, $created, $skipped);

        $this->line('');
        $this->info(sprintf('Done: %d user(s) created, %d skipped.', count($created), count($skipped)));
        $this->line("  Evidence file: {$evidence}");
        $this->line('  Now delete the input file: it contains password hashes.');

        return self::SUCCESS;
    }

    /**
     * Reads and validates the whole file before anything is written.
     */
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

        foreach (array_keys(self::NEW_PERMISSIONS) as $name) {
            $attributes = ['name' => $name, 'guard_name' => self::GUARD];
            $values = $hasGroup ? ['group_name' => 'archive'] : [];
            Permission::firstOrCreate($attributes, $values);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Existing roles keep what they have and gain statement access.
        foreach (['Audit and Control', 'Fincon'] as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', self::GUARD)->first();
            $role?->givePermissionTo('archive statements');
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

            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
            $role->syncPermissions(self::VIEWER_PERMISSIONS);
        }
    }

    private function writeEvidence(string $approvalRef, array $created, array $skipped): string
    {
        $lines = [];
        $lines[] = ['Archive Repository - user onboarding record'];
        $lines[] = ['Approval reference', $approvalRef];
        $lines[] = ['Run at', now()->format('Y-m-d H:i:s')];
        $lines[] = ['Server user', get_current_user()];
        $lines[] = ['Password handling', 'Existing LMS bcrypt hashes copied; plain passwords never seen. Two-factor login enabled for every new user.'];
        $lines[] = [];
        $lines[] = ['Result', 'Archive user ID', 'Name', 'Email', 'Department', 'Archive role', 'Created at'];

        foreach ($created as $r) {
            $lines[] = ['Created', $r['id'], $r['name'], $r['email'], $r['department'], $r['role'], $r['created_at']];
        }
        foreach ($skipped as $r) {
            $lines[] = ['Skipped - already exists', '', $r['name'], $r['email'], $r['department'], $r['role'], ''];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($lines as $l) {
            fputcsv($handle, $l, ',', '"', '');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $file = 'evidence/archive_user_onboarding_' . now()->format('Ymd_His') . '.csv';
        Storage::disk('local')->put($file, $csv);

        return Storage::disk('local')->path($file);
    }
}
