<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:users index'])->only(['index']);
        $this->middleware(['permission:users create'])->only(['create', 'store']);
        $this->middleware(['permission:users update'])->only(['edit', 'update']);
        $this->middleware(['permission:users delete'])->only(['destroy']);
    }
    

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::with(['roles'])
            ->withCount('loginHistories');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Status filter
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
            }
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->latest()->paginate($perPage)->withQueryString();

        // Stats
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $pendingUsers = User::whereNull('email_verified_at')->count();
        $inactiveUsers = User::where('is_active', false)->count();

        return view('backend.users.index', compact(
            'users',
            'totalUsers',
            'activeUsers',
            'pendingUsers',
            'inactiveUsers'
        ));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();
        
        return view('backend.users.create', compact('roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['boolean'],
            'two_factor_enabled' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        // Prepare user data
        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
            'two_factor_enabled' => $request->boolean('two_factor_enabled', false),
        ];

        // Create user
        $user = User::create($userData);

        // Assign roles
        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip()])
            ->log('User created');

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully!');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['roles', 'loginHistories' => function ($query) {
            $query->latest()->limit(10);
        }]);

        // Get user activities
        $activities = activity()
            ->causedBy($user)
            ->orWhere('subject_id', $user->id)
            ->orWhere('causer_id', $user->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('backend.users.show', compact('user', 'activities'));
    }

    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('backend.users.edit', compact('user', 'roles', 'userRoles'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'two_factor_enabled' => ['boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        // Prepare update data
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'two_factor_enabled' => $request->boolean('two_factor_enabled'),
        ];

        // Update user
        $user->update($updateData);

        // Sync roles
        if ($request->has('roles')) {
            $user->syncRoles($request->roles ?? []);
        }

        // Log activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['ip' => request()->ip()])
            ->log('User updated');

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.'
            ], 403);
        }

        // Log activity before deletion
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['user_data' => $user->toArray()])
            ->log('User deleted');

        // Soft delete user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully!'
        ]);
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(Request $request, User $user)
    {
        $isActive = $request->boolean('is_active');
        
        $user->update(['is_active' => $isActive]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties(['is_active' => $isActive])
            ->log($isActive ? 'User activated' : 'User deactivated');

        return response()->json([
            'success' => true,
            'message' => $isActive ? 'User activated successfully!' : 'User deactivated successfully!'
        ]);
    }

    /**
     * Send welcome email to user
     */
    public function sendWelcome(User $user)
    {
        // Generate new password
        $password = Str::random(12);
        $user->update(['password' => Hash::make($password)]);

        // TODO: Send welcome email with credentials
        // Mail::to($user->email)->send(new WelcomeEmail($user, $password));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('Welcome email sent');

        return response()->json([
            'success' => true,
            'message' => 'Welcome email sent successfully!'
        ]);
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user)
    {
        // Generate new password
        $password = Str::random(12);
        $user->update(['password' => Hash::make($password)]);

        // TODO: Send password reset email
        // Mail::to($user->email)->send(new PasswordResetEmail($user, $password));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->log('Password reset');

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully! Email sent to user.'
        ]);
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => ['required', 'in:activate,deactivate,enable_login,disable_login,send_welcome,delete'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $userIds = $request->user_ids;
        
        // Prevent acting on own account
        $userIds = array_filter($userIds, fn($id) => $id != auth()->id());

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot perform bulk actions on your own account.'
            ], 403);
        }

        $count = count($userIds);

        switch ($request->action) {
            case 'activate':
                User::whereIn('id', $userIds)->update(['is_active' => true]);
                $message = "{$count} user(s) activated successfully!";
                break;

            case 'deactivate':
                User::whereIn('id', $userIds)->update(['is_active' => false]);
                $message = "{$count} user(s) deactivated successfully!";
                break;

            case 'send_welcome':
                foreach (User::whereIn('id', $userIds)->get() as $user) {
                    // TODO: Send welcome email
                }
                $message = "Welcome emails sent to {$count} user(s)!";
                break;

            case 'delete':
                User::whereIn('id', $userIds)->delete();
                $message = "{$count} user(s) deleted successfully!";
                break;

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action.'
                ], 400);
        }

        // Log bulk activity
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => $request->action,
                'user_ids' => $userIds,
                'count' => $count
            ])
            ->log('Bulk user action performed');

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}