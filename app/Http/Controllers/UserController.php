<?php
// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use App\Models\PasswordSetupToken;
use App\Models\User;
use App\Services\PasswordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * User administration.
 *
 * Administrators never see or choose another person's password. New users and
 * password resets get a single-use link by email, and the person sets their own
 * password. Sign-in also needs the emailed code, so a link by email is the only
 * route that works anyway, and it keeps "who knew the password" out of question.
 */
class UserController extends Controller
{
    private const SUPER_ADMIN = 'Super Admin';

    public function __construct(private PasswordService $passwords)
    {
        // Before this update the status, reset, welcome and bulk actions (and the
        // details page) had no permission check, so any signed-in user could
        // deactivate or delete any account.
        $this->middleware(['permission:users index'])->only(['index', 'show']);
        $this->middleware(['permission:users create'])->only(['create', 'store']);
        $this->middleware(['permission:users update'])->only([
            'edit', 'update', 'toggleStatus', 'sendWelcome', 'resetPassword',
            'unlock', 'requirePasswordChange', 'bulkAction',
        ]);
        $this->middleware(['permission:users delete'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = User::with(['roles'])->withCount('loginHistories');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('is_active', true);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'pending':
                    $query->whereNull('email_verified_at');
                    break;
                case 'locked':
                    $query->where('locked_until', '>', now());
                    break;
                case 'password_change':
                    $query->where(function ($q) {
                        $q->where('must_change_password', true)->orWhereNull('password_changed_at');
                    });
                    break;
            }
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = in_array($perPage, [10, 15, 25, 50, 100], true) ? $perPage : 15;

        $users = $query->latest()->paginate($perPage)->withQueryString();

        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $pendingUsers = User::whereNull('email_verified_at')->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $lockedUsers = User::where('locked_until', '>', now())->count();
        $passwordChangeUsers = User::where(function ($q) {
            $q->where('must_change_password', true)->orWhereNull('password_changed_at');
        })->count();

        return view('backend.users.index', compact(
            'users',
            'totalUsers',
            'activeUsers',
            'pendingUsers',
            'inactiveUsers',
            'lockedUsers',
            'passwordChangeUsers'
        ));
    }

    public function create()
    {
        $roles = $this->assignableRoles();

        return view('backend.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'two_factor_enabled' => ['boolean'],
            'send_welcome' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => [Rule::in($this->assignableRoles()->pluck('id')->all())],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            // A random password nobody knows; the user sets their own from the link.
            'password' => Str::password(40),
            'is_active' => $request->boolean('is_active', true),
            'two_factor_enabled' => true,
        ]);

        $user->forceFill(['must_change_password' => true])->save();

        if ($request->filled('roles')) {
            $user->syncRoles(Role::whereIn('id', $request->roles)->get());
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
                'roles' => $user->getRoleNames()->all(),
            ])
            ->log('User created');

        $message = 'User created.';

        if ($request->boolean('send_welcome', true) && $user->is_active) {
            try {
                $this->passwords->issueLink($user, PasswordSetupToken::PURPOSE_WELCOME, auth()->user());
                $message .= ' An account setup link has been emailed to '.$user->email.'.';
            } catch (Throwable $e) {
                return redirect()
                    ->route('users.show', $user)
                    ->with('error', 'User created, but the setup email could not be sent. Check the mail settings on System Health, then use "Send account setup link".');
            }
        }

        return redirect()->route('users.show', $user)->with('success', $message);
    }

    public function show(User $user)
    {
        $user->load(['roles', 'loginHistories' => function ($query) {
            $query->latest('logged_in_at')->limit(10);
        }]);

        // Actions by this user and actions on this user.
        $activities = Activity::query()
            ->where(function ($q) use ($user) {
                $q->where(function ($q) use ($user) {
                    $q->where('causer_type', $user->getMorphClass())->where('causer_id', $user->id);
                })->orWhere(function ($q) use ($user) {
                    $q->where('subject_type', $user->getMorphClass())->where('subject_id', $user->id);
                });
            })
            ->with('causer')
            ->latest()
            ->limit(20)
            ->get();

        $passwordExpiresAt = $this->passwords->expiresAt($user);
        $passwordChangeReason = $this->passwords->changeRequiredReason($user);
        $pendingLink = $user->passwordSetupTokens()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        return view('backend.users.show', compact(
            'user',
            'activities',
            'passwordExpiresAt',
            'passwordChangeReason',
            'pendingLink'
        ));
    }

    public function edit(User $user)
    {
        $roles = $this->assignableRoles();
        $userRoles = $user->roles->pluck('id')->toArray();
        $passwordExpiresAt = $this->passwords->expiresAt($user);

        return view('backend.users.edit', compact('user', 'roles', 'userRoles', 'passwordExpiresAt'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $isSelf = $user->id === auth()->id();
        $newRoles = Role::whereIn('id', $request->input('roles', []))->get();
        $oldRoleNames = $user->getRoleNames()->all();

        // Only a Super Admin may give or take away the Super Admin role.
        $superAdminChanging = in_array(self::SUPER_ADMIN, $oldRoleNames, true)
            !== $newRoles->contains('name', self::SUPER_ADMIN);
        if ($superAdminChanging && ! auth()->user()->isSuperAdmin()) {
            return back()->withInput()->with('error', 'Only a Super Admin can give or remove the Super Admin role.');
        }

        if ($isSelf && ! $request->boolean('is_active')) {
            return back()->withInput()->with('error', 'You cannot deactivate your own account.');
        }

        if ($isSelf && in_array(self::SUPER_ADMIN, $oldRoleNames, true) && ! $newRoles->contains('name', self::SUPER_ADMIN)) {
            return back()->withInput()->with('error', 'You cannot remove the Super Admin role from yourself.');
        }

        $emailChanged = Str::lower($validated['email']) !== Str::lower($user->email);

        $user->update([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
            // The emailed sign-in code is always required; this flag is kept on.
            'two_factor_enabled' => true,
        ]);

        if ($emailChanged) {
            // The new address has not been proven yet, and any link sent to
            // the old address must stop working.
            $user->forceFill(['email_verified_at' => null])->save();
            $user->passwordSetupTokens()->whereNull('used_at')->delete();
        }

        $user->syncRoles($newRoles);

        if (! $user->is_active) {
            $this->passwords->endOtherSessions($user);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
                'roles_before' => $oldRoleNames,
                'roles_after' => $user->getRoleNames()->all(),
                'email_changed' => $emailChanged,
            ])
            ->log('User updated');

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User updated.');
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return $this->jsonError('You cannot delete your own account.', 403);
        }

        if ($error = $this->superAdminGuard(collect([$user]))) {
            return $this->jsonError($error, 403);
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'ip' => request()->ip(),
                'user' => $user->only(['id', 'name', 'email']),
                'roles' => $user->getRoleNames()->all(),
            ])
            ->log('User deleted');

        $this->passwords->endOtherSessions($user);
        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted.']);
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $isActive = $request->boolean('is_active');

        if (! $isActive && $user->id === auth()->id()) {
            return $this->jsonError('You cannot deactivate your own account.', 403);
        }

        if (! $isActive && ($error = $this->superAdminGuard(collect([$user])))) {
            return $this->jsonError($error, 403);
        }

        $user->update(['is_active' => $isActive]);

        if (! $isActive) {
            $this->passwords->endOtherSessions($user);
            $user->passwordSetupTokens()->whereNull('used_at')->delete();
        }

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['is_active' => $isActive, 'ip' => request()->ip()])
            ->log($isActive ? 'User activated' : 'User deactivated');

        return response()->json([
            'success' => true,
            'message' => $isActive ? 'User activated.' : 'User deactivated and signed out.',
        ]);
    }

    /**
     * Email an account setup link (24 hours) to a user who has not set a password yet.
     */
    public function sendWelcome(User $user): JsonResponse
    {
        return $this->sendLink($user, PasswordSetupToken::PURPOSE_WELCOME);
    }

    /**
     * Email a password reset link (60 minutes). The current password keeps
     * working until the user sets a new one.
     */
    public function resetPassword(User $user): JsonResponse
    {
        return $this->sendLink($user, PasswordSetupToken::PURPOSE_RESET);
    }

    public function unlock(User $user): JsonResponse
    {
        $user->unlock();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip()])
            ->log('Account unlocked');

        return response()->json(['success' => true, 'message' => $user->name.' has been unlocked.']);
    }

    public function requirePasswordChange(User $user): JsonResponse
    {
        $user->forceFill(['must_change_password' => true])->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip()])
            ->log('Password change required at next sign-in');

        return response()->json([
            'success' => true,
            'message' => $user->name.' must set a new password before doing anything else.',
        ]);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'action' => ['required', 'in:activate,deactivate,send_welcome,require_password_change,unlock,delete'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        if ($request->action === 'delete' && ! auth()->user()->can('users delete')) {
            return $this->jsonError('You do not have permission to delete users.', 403);
        }

        $userIds = collect($request->user_ids)->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === auth()->id())->values();

        if ($userIds->isEmpty()) {
            return $this->jsonError('Select at least one user other than yourself.', 403);
        }

        $users = User::whereIn('id', $userIds)->get();
        $count = $users->count();

        if (in_array($request->action, ['deactivate', 'delete'], true) && ($error = $this->superAdminGuard($users))) {
            return $this->jsonError($error, 403);
        }

        $failed = [];

        switch ($request->action) {
            case 'activate':
                User::whereIn('id', $userIds)->update(['is_active' => true]);
                $message = "{$count} user(s) activated.";
                break;

            case 'deactivate':
                User::whereIn('id', $userIds)->update(['is_active' => false]);
                $users->each(fn (User $u) => $this->passwords->endOtherSessions($u));
                $message = "{$count} user(s) deactivated and signed out.";
                break;

            case 'send_welcome':
                foreach ($users as $user) {
                    if (! $user->is_active) {
                        $failed[] = $user->name.' (inactive)';
                        continue;
                    }
                    $purpose = $user->password_changed_at === null
                        ? PasswordSetupToken::PURPOSE_WELCOME
                        : PasswordSetupToken::PURPOSE_RESET;
                    try {
                        $this->passwords->issueLink($user, $purpose, auth()->user());
                    } catch (Throwable $e) {
                        $failed[] = $user->name;
                    }
                }
                $sent = $count - count($failed);
                $message = "Password links emailed to {$sent} user(s).";
                break;

            case 'require_password_change':
                User::whereIn('id', $userIds)->update(['must_change_password' => true]);
                $message = "{$count} user(s) must set a new password at their next action.";
                break;

            case 'unlock':
                User::whereIn('id', $userIds)->update(['failed_login_attempts' => 0, 'locked_until' => null]);
                $message = "{$count} user(s) unlocked.";
                break;

            case 'delete':
                $users->each(fn (User $u) => $this->passwords->endOtherSessions($u));
                User::whereIn('id', $userIds)->delete();
                $message = "{$count} user(s) deleted.";
                break;

            default:
                return $this->jsonError('Invalid action.', 400);
        }

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => $request->action,
                'user_ids' => $userIds->all(),
                'users' => $users->pluck('email')->all(),
                'failed' => $failed,
                'ip' => request()->ip(),
            ])
            ->log('Bulk user action: '.$request->action);

        if ($failed !== []) {
            $message .= ' Not sent: '.implode(', ', $failed).'.';
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    private function sendLink(User $user, string $purpose): JsonResponse
    {
        if (! $user->is_active) {
            return $this->jsonError('Activate this user before sending a link.', 422);
        }

        try {
            $token = $this->passwords->issueLink($user, $purpose, auth()->user());
        } catch (Throwable $e) {
            return $this->jsonError('The email could not be sent. Check the mail settings on System Health.', 500);
        }

        $validity = $purpose === PasswordSetupToken::PURPOSE_WELCOME
            ? config('security.links.welcome_hours', 24).' hours'
            : config('security.links.reset_minutes', 60).' minutes';

        return response()->json([
            'success' => true,
            'message' => ($purpose === PasswordSetupToken::PURPOSE_WELCOME ? 'Account setup link' : 'Password reset link')
                ." emailed to {$user->email}. It works once and expires in {$validity}.",
            'expires_at' => $token->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Stops anyone deactivating or deleting every active Super Admin, which
     * would leave nobody able to manage the system.
     */
    private function superAdminGuard($users): ?string
    {
        $targets = $users->filter(fn (User $u) => $u->hasRole(self::SUPER_ADMIN));

        if ($targets->isEmpty()) {
            return null;
        }

        if (! auth()->user()->isSuperAdmin()) {
            return 'Only a Super Admin can deactivate or delete a Super Admin.';
        }

        $remaining = User::role(self::SUPER_ADMIN)
            ->where('is_active', true)
            ->whereNotIn('id', $targets->pluck('id'))
            ->count();

        return $remaining === 0 ? 'At least one active Super Admin must remain.' : null;
    }

    /**
     * Only a Super Admin sees the Super Admin role as an option.
     */
    private function assignableRoles()
    {
        return Role::orderBy('name')
            ->when(! auth()->user()->isSuperAdmin(), fn ($q) => $q->where('name', '!=', self::SUPER_ADMIN))
            ->get();
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
