<?php
// app/Console/Commands/ArchiveSecuritySetup.php

namespace App\Console\Commands;

use App\Models\PasswordHistory;
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
 * One-off setup for the November 2026 security update. Safe to run again:
 * every step checks what is already in place.
 *
 *  1. Creates the new permissions and gives them to Super Admin only.
 *  2. Records each user's current password in their password history, so the
 *     required change cannot be "changed" back to the same (LMS) password.
 *  3. Requires every user to set a new password at their next sign-in.
 *  4. Clears failed-login counters (they were never reset before this fix).
 *  5. Revokes every API token issued by the old unprotected /api/login.
 *  6. Saves an evidence file for the audit pack.
 */
class ArchiveSecuritySetup extends Command
{
    protected $signature = 'archive:security-setup
                            {--change-ref= : Change request reference to record in the evidence file}
                            {--dry-run : Show what would change without changing anything}';

    protected $description = 'Set up permissions and password rules for the security update';

    private const GUARD = 'web';

    /** New permissions => description. All go to Super Admin only. */
    private const NEW_PERMISSIONS = [
        'system health' => 'View System Health and send test emails',
        'system audit' => 'View and export the audit log and login history',
    ];

    /** Created but given to nobody: API access must be granted deliberately. */
    private const UNASSIGNED_PERMISSIONS = [
        'api access' => 'Use the archive API with a personal access token',
    ];

    /** Existing permissions the new screens rely on. */
    private const REQUIRED_EXISTING = ['users index', 'users update', 'archive manage'];

    public function handle(): int
    {
        $changeRef = trim((string) $this->option('change-ref'));
        if ($changeRef === '') {
            $this->error('Give the change request reference, e.g. --change-ref="LFCL/IT/ARC/2026/02".');
            return self::FAILURE;
        }

        foreach (['must_change_password', 'password_changed_at'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                $this->error("The users.{$column} column is missing. Run the 2026_11_20_090000 migration first.");
                return self::FAILURE;
            }
        }

        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', self::GUARD)->first();
        if (! $superAdmin) {
            $this->error('The Super Admin role does not exist.');
            return self::FAILURE;
        }

        $missing = collect(self::REQUIRED_EXISTING)
            ->reject(fn ($name) => Permission::where('name', $name)->where('guard_name', self::GUARD)->exists())
            ->values();
        if ($missing->isNotEmpty()) {
            $this->warn('These existing permissions were not found and will be created for Super Admin: '.$missing->implode(', '));
        }

        $users = User::orderBy('id')->get();
        $toFlag = $users->filter(fn (User $u) => $u->password_changed_at === null && ! $u->must_change_password);
        $lockedCount = $users->filter(fn (User $u) => $u->failed_login_attempts > 0 || $u->locked_until !== null)->count();
        $tokenCount = Schema::hasTable('personal_access_tokens') ? DB::table('personal_access_tokens')->count() : 0;

        $this->info('Security setup'.($this->option('dry-run') ? ' (DRY RUN: nothing will change)' : ''));
        $this->line("  Change reference:              {$changeRef}");
        $this->line('  New permissions for Super Admin: '.implode(', ', array_keys(self::NEW_PERMISSIONS)));
        $this->line('  Permission created, given to nobody: '.implode(', ', array_keys(self::UNASSIGNED_PERMISSIONS)));
        $this->line('  Users who must change password: '.$toFlag->count().' of '.$users->count());
        $this->line("  Failed-login counters to clear: {$lockedCount}");
        $this->line("  API tokens to revoke:           {$tokenCount}");
        $this->line('');

        $this->table(
            ['ID', 'Name', 'Email', 'Active', 'Action'],
            $users->map(fn (User $u) => [
                $u->id,
                $u->name,
                $u->email,
                $u->is_active ? 'Yes' : 'No',
                $toFlag->contains('id', $u->id) ? 'Must change password' : 'No change (already set)',
            ])->all()
        );

        if ($this->option('dry-run')) {
            $this->info('Dry run finished. Run again without --dry-run to apply.');
            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($superAdmin, $missing, $toFlag, $users) {
                $hasGroup = Schema::hasColumn('permissions', 'group_name');

                foreach (array_merge(self::NEW_PERMISSIONS, self::UNASSIGNED_PERMISSIONS) as $name => $description) {
                    Permission::firstOrCreate(
                        ['name' => $name, 'guard_name' => self::GUARD],
                        $hasGroup ? ['group_name' => explode(' ', $name)[0]] : []
                    );
                }

                foreach ($missing as $name) {
                    Permission::firstOrCreate(
                        ['name' => $name, 'guard_name' => self::GUARD],
                        $hasGroup ? ['group_name' => explode(' ', $name)[0]] : []
                    );
                }

                app(PermissionRegistrar::class)->forgetCachedPermissions();

                $superAdmin->givePermissionTo(array_merge(array_keys(self::NEW_PERMISSIONS), $missing->all()));

                // API access is never automatic: remove it from any role that got it.
                foreach (array_keys(self::UNASSIGNED_PERMISSIONS) as $name) {
                    Role::where('guard_name', self::GUARD)->get()
                        ->each(fn (Role $role) => $role->hasPermissionTo($name) ? $role->revokePermissionTo($name) : null);
                }

                $now = now();

                foreach ($users as $user) {
                    $alreadyRecorded = PasswordHistory::where('user_id', $user->id)
                        ->where('password', $user->getAuthPassword())
                        ->exists();

                    if (! $alreadyRecorded && $user->getAuthPassword()) {
                        PasswordHistory::create([
                            'user_id' => $user->id,
                            'password' => $user->getAuthPassword(),
                            'created_at' => $now,
                        ]);
                    }
                }

                User::whereIn('id', $toFlag->pluck('id'))->update(['must_change_password' => true]);

                User::query()->update(['failed_login_attempts' => 0, 'locked_until' => null]);

                if (Schema::hasTable('personal_access_tokens')) {
                    DB::table('personal_access_tokens')->delete();
                }
            });
        } catch (Throwable $e) {
            $this->error('Setup failed. Nothing was changed.');
            $this->line('  '.$e->getMessage());
            return self::FAILURE;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        activity()
            ->withProperties([
                'change_ref' => $changeRef,
                'users_required_to_change' => $toFlag->count(),
                'api_tokens_revoked' => $tokenCount,
                'permissions_added' => array_keys(self::NEW_PERMISSIONS),
            ])
            ->log('Security update applied (archive:security-setup)');

        $evidence = $this->writeEvidence($changeRef, $users, $toFlag->pluck('id')->all(), $tokenCount);

        $this->info('Done.');
        $this->line('  '.$toFlag->count().' user(s) must set a new password at their next sign-in.');
        $this->line("  {$tokenCount} API token(s) revoked.");
        $this->line("  Evidence file: {$evidence}");

        return self::SUCCESS;
    }

    private function writeEvidence(string $changeRef, $users, array $flaggedIds, int $tokenCount): string
    {
        $rows = [];
        $rows[] = ['Archive Repository - security update record'];
        $rows[] = ['Change reference', $changeRef];
        $rows[] = ['Run at', now()->format('Y-m-d H:i:s T')];
        $rows[] = ['Permissions given to Super Admin', implode('; ', array_keys(self::NEW_PERMISSIONS))];
        $rows[] = ['Permissions created and given to nobody', implode('; ', array_keys(self::UNASSIGNED_PERMISSIONS))];
        $rows[] = ['API tokens revoked', (string) $tokenCount];
        $rows[] = ['Failed-login counters cleared', 'All users'];
        $rows[] = [];
        $rows[] = ['User ID', 'Name', 'Email', 'Active', 'Roles', 'Must change password at next sign-in'];

        foreach ($users as $user) {
            $rows[] = [
                (string) $user->id,
                $user->name,
                $user->email,
                $user->is_active ? 'Yes' : 'No',
                $user->getRoleNames()->implode('; '),
                in_array($user->id, $flaggedIds, true) ? 'Yes' : 'No (already set)',
            ];
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $path = 'evidence/security-update-'.now()->format('Ymd-His').'.csv';
        if (! Storage::disk('local')->put($path, $csv)) {
            throw new RuntimeException("Could not write the evidence file {$path}.");
        }

        return Storage::disk('local')->path($path);
    }
}
