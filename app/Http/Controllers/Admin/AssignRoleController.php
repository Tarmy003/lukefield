<?php

namespace App\Http\Controllers\Admin;

use App\Notifications\RoleAssignedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Routing\Controller;

class AssignRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles-users index')->only('index');
        $this->middleware('permission:roles-users create')->only(['create', 'store']);
        $this->middleware('permission:roles-users update')->only(['edit', 'update']);
        $this->middleware('permission:roles-users destroy')->only('destroy');
        $this->middleware('permission:roles-users show')->only('show');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Get users with roles (paginated)
        $usersWithRoles = \App\Models\User::with(['roles'])
            ->has('roles')
            ->paginate(15);
        
        // Get users without roles
        $usersWithoutRoles = \App\Models\User::doesntHave('roles')
            ->get();
        
        // Get all roles with user counts
        $roles = \Spatie\Permission\Models\Role::withCount(['users', 'permissions'])
            ->with(['users', 'permissions'])
            ->get();
        
        // Calculate statistics
        $totalRoles = $roles->count();
        
        return view('admin.role-users.index', compact(
            'usersWithRoles',
            'usersWithoutRoles',
            'roles',
            'totalRoles'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get users without any roles
        $usersWithoutRoles = \App\Models\User::doesntHave('roles')
            ->select('id', 'name', 'email','created_at', 'last_login_at')
            ->orderBy('name')
            ->get();
        
        // Get all available roles
        $roles = \Spatie\Permission\Models\Role::with(['permissions', 'users'])
            ->orderBy('name')
            ->get();
        
        return view('admin.role-users.create', compact('usersWithoutRoles', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'confirm_assignment' => 'required|accepted',
            'send_notification_email' => 'boolean',
            'log_assignment' => 'boolean'
        ]);

        $user = \App\Models\User::findOrFail($validated['user_id']);
        $role = \Spatie\Permission\Models\Role::findOrFail($validated['role_id']);
        
        // Check if user already has a role
        if ($user->roles()->count() > 0) {
            // Log failed attempt
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'role_assignment_failed',
                    'attempted_role' => $role->name,
                    'failure_reason' => 'User already has role assigned',
                    'existing_roles' => $user->roles->pluck('name')->toArray()
                ])
                ->log('Failed role assignment attempt - user already has role');

            return redirect()->back()
                ->withErrors(['user_id' => 'This user already has a role assigned.'])
                ->withInput();
        }
        
        try {
            // Store previous state for audit
            $previousRoles = $user->roles->pluck('name')->toArray();
            
            // Assign role to user
            $user->assignRole($role);
            
            // Log successful role assignment
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'role_assigned',
                    'assigned_role' => $role->name,
                    'role_id' => $role->id,
                    'previous_roles' => $previousRoles,
                    'new_roles' => [$role->name],
                    'permissions_granted' => $role->permissions->pluck('name')->toArray(),
                    'permissions_count' => $role->permissions->count(),
                    'notification_sent' => $validated['send_notification_email'] ?? false,
                    'assignment_method' => 'manual_assignment'
                ])
                ->log("Role '{$role->name}' assigned to user");
            
            // Send notification email if requested
            if ($validated['send_notification_email'] ?? false) {
                try {
                    Notification::send($user, new RoleAssignedNotification($role));
                    
                    // Log notification activity
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($user)
                        ->withProperties([
                            'action' => 'role_assignment_notification_sent',
                            'notification_type' => 'email',
                            'role_name' => $role->name,
                            'recipient_email' => $user->email
                        ])
                        ->log('Role assignment notification email sent');
                        
                } catch (\Exception $e) {
                    // Log notification failure
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($user)
                        ->withProperties([
                            'action' => 'role_assignment_notification_failed',
                            'notification_type' => 'email',
                            'role_name' => $role->name,
                            'recipient_email' => $user->email,
                            'error_message' => $e->getMessage()
                        ])
                        ->log('Failed to send role assignment notification email');
                }
            }
            
            // Additional logging if requested
            if ($validated['log_assignment'] ?? false) {
                Log::info('Role assigned', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'assigned_by' => auth()->user()->name,
                    'assigned_by_id' => auth()->user()->id,
                    'assigned_at' => now(),
                    'permissions_granted' => $role->permissions->pluck('name')->toArray()
                ]);
            }
            
            flash()
                ->option('position', 'bottom-right')
                ->success("Role '{$role->name}' successfully assigned to {$user->name}!");
                
            return redirect()->route('assign-role.index');
            
        } catch (\Exception $e) {
            // Log assignment failure
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'role_assignment_error',
                    'attempted_role' => $role->name,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString()
                ])
                ->log('Role assignment failed due to system error');
                
            Log::error('Role assignment failed', [
                'user_id' => $validated['user_id'],
                'role_id' => $validated['role_id'],
                'error' => $e->getMessage(),
                'assigned_by' => auth()->user()->id
            ]);
            
            flash()
                ->option('position', 'bottom-right')
                ->error('Failed to assign role. Please try again.');
                
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = \App\Models\User::with(['roles.permissions'])->findOrFail($id);
        
        // Log view activity
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'action' => 'viewed_user_role_details',
                'current_roles' => $user->roles->pluck('name')->toArray()
            ])
            ->log('Viewed user role assignment details');
            
        return view('admin.role-users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = \App\Models\User::with('roles')->findOrFail($id);
        $roles = \Spatie\Permission\Models\Role::with(['permissions', 'users'])->get();
        
        // Log edit form access
        activity()
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->withProperties([
                'action' => 'accessed_role_edit_form',
                'current_roles' => $user->roles->pluck('name')->toArray()
            ])
            ->log('Accessed role assignment edit form');
            
        return view('admin.role-users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'send_notification_email' => 'boolean'
        ]);

        $user = \App\Models\User::with('roles')->findOrFail($id);
        $newRole = \Spatie\Permission\Models\Role::findOrFail($validated['role_id']);
        
        // Store previous state for audit
        $previousRoles = $user->roles->pluck('name')->toArray();
        $previousPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        
        try {
            // Update user role
            $user->syncRoles([$newRole]);
            
            // Log role change
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'role_changed',
                    'previous_roles' => $previousRoles,
                    'new_role' => $newRole->name,
                    'previous_permissions' => $previousPermissions,
                    'new_permissions' => $newRole->permissions->pluck('name')->toArray(),
                    'permissions_added' => array_diff($newRole->permissions->pluck('name')->toArray(), $previousPermissions),
                    'permissions_removed' => array_diff($previousPermissions, $newRole->permissions->pluck('name')->toArray()),
                    'notification_sent' => $validated['send_notification_email'] ?? false
                ])
                ->log("User role changed from '" . implode(', ', $previousRoles) . "' to '{$newRole->name}'");
            
            // Send notification if requested
            if ($validated['send_notification_email'] ?? false) {
                try {
                    Notification::send($user, new RoleAssignedNotification($newRole));
                    
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($user)
                        ->withProperties([
                            'action' => 'role_change_notification_sent',
                            'notification_type' => 'email',
                            'new_role' => $newRole->name
                        ])
                        ->log('Role change notification email sent');
                        
                } catch (\Exception $e) {
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($user)
                        ->withProperties([
                            'action' => 'role_change_notification_failed',
                            'error_message' => $e->getMessage()
                        ])
                        ->log('Failed to send role change notification');
                }
            }
            
            flash()
                ->option('position', 'bottom-right')
                ->success("User role updated successfully!");
                
            return redirect()->route('assign-role.index');
            
        } catch (\Exception $e) {
            // Log update failure
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'role_update_error',
                    'attempted_role' => $newRole->name,
                    'error_message' => $e->getMessage()
                ])
                ->log('Failed to update user role');
                
            flash()
                ->option('position', 'bottom-right')
                ->error('Failed to update role. Please try again.');
                
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = \App\Models\User::with('roles')->findOrFail($id);
            $removedRoles = $user->roles->pluck('name')->toArray();
            $removedPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            
            if (empty($removedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no roles to remove.'
                ]);
            }
            
            // Remove all roles
            $user->syncRoles([]);
            
            // Log role removal
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'roles_removed',
                    'removed_roles' => $removedRoles,
                    'removed_permissions' => $removedPermissions,
                    'permissions_count' => count($removedPermissions)
                ])
                ->log("All roles removed from user (removed: " . implode(', ', $removedRoles) . ")");
            
            return response()->json([
                'success' => true,
                'message' => "All roles removed from {$user->name} successfully."
            ]);
            
        } catch (\Exception $e) {
            // Log removal failure
            if (isset($user)) {
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($user)
                    ->withProperties([
                        'action' => 'role_removal_error',
                        'error_message' => $e->getMessage()
                    ])
                    ->log('Failed to remove user roles');
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error removing roles: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get users without roles via AJAX (for dynamic loading)
     */
    public function getUsersWithoutRoles()
    {
        $users = \App\Models\User::doesntHave('roles')
            ->select('id', 'name', 'email', 'employee')
            ->orderBy('name')
            ->get();
        
        // Log API access
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'action' => 'fetched_users_without_roles',
                'users_count' => $users->count(),
                'access_method' => 'ajax'
            ])
            ->log('Fetched users without roles via AJAX');
            
        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Get role permissions via AJAX
     */
    public function getRolePermissions($roleId)
    {
        try {
            $role = \Spatie\Permission\Models\Role::with('permissions')->findOrFail($roleId);
            
            // Log permission lookup
            activity()
                ->causedBy(auth()->user())
                ->performedOn($role)
                ->withProperties([
                    'action' => 'fetched_role_permissions',
                    'role_name' => $role->name,
                    'permissions_count' => $role->permissions->count(),
                    'access_method' => 'ajax'
                ])
                ->log("Fetched permissions for role '{$role->name}'");
            
            return response()->json([
                'success' => true,
                'permissions' => $role->permissions->groupBy('group_name')
            ]);
            
        } catch (\Exception $e) {
            // Log lookup failure
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'role_permissions_fetch_error',
                    'role_id' => $roleId,
                    'error_message' => $e->getMessage()
                ])
                ->log('Failed to fetch role permissions');
                
            return response()->json([
                'success' => false,
                'message' => 'Error loading permissions: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get user roles via AJAX
     */
    public function getUserRoles($userId)
    {
        try {
            $user = \App\Models\User::with(['roles.permissions'])->findOrFail($userId);
            
            // Log user role lookup
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'fetched_user_roles',
                    'current_roles' => $user->roles->pluck('name')->toArray(),
                    'access_method' => 'ajax'
                ])
                ->log('Fetched user role details');
            
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee,
                    'roles' => $user->roles,
                    'permissions' => $user->getAllPermissions(),
                    'role_assigned_date' => $user->roles->first()?->pivot?->created_at
                ]
            ]);
        } catch (\Exception $e) {
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'user_roles_fetch_error',
                    'user_id' => $userId,
                    'error_message' => $e->getMessage()
                ])
                ->log('Failed to fetch user role details');
                
            return response()->json([
                'success' => false,
                'message' => 'Error loading user details'
            ]);
        }
    }

    /**
     * Bulk remove roles
     */
    public function bulkRemoveRoles(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        try {
            $users = \App\Models\User::with('roles')->whereIn('id', $validated['user_ids'])->get();
            $processedUsers = [];
            $count = 0;

            foreach ($users as $user) {
                if ($user->roles()->count() > 0) {
                    $removedRoles = $user->roles->pluck('name')->toArray();
                    $user->syncRoles([]);
                    $count++;
                    
                    $processedUsers[] = [
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'removed_roles' => $removedRoles
                    ];
                    
                    // Log individual role removal
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($user)
                        ->withProperties([
                            'action' => 'bulk_roles_removed',
                            'removed_roles' => $removedRoles,
                            'operation_type' => 'bulk_operation'
                        ])
                        ->log("Roles removed via bulk operation (removed: " . implode(', ', $removedRoles) . ")");
                }
            }

            // Log bulk operation summary
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'bulk_role_removal_completed',
                    'total_users_processed' => $count,
                    'total_users_requested' => count($validated['user_ids']),
                    'processed_users' => $processedUsers
                ])
                ->log("Bulk role removal completed - {$count} users processed");

            return response()->json([
                'success' => true,
                'message' => "Successfully removed roles from {$count} user(s)"
            ]);

        } catch (\Exception $e) {
            // Log bulk operation failure
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'bulk_role_removal_error',
                    'requested_user_ids' => $validated['user_ids'],
                    'error_message' => $e->getMessage()
                ])
                ->log('Bulk role removal operation failed');
                
            return response()->json([
                'success' => false,
                'message' => 'Error removing roles: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Quick assign role via AJAX
     */
    public function quickAssign(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        try {
            $user = \App\Models\User::with('roles')->findOrFail($validated['user_id']);
            $role = \Spatie\Permission\Models\Role::findOrFail($validated['role_id']);

            if ($user->roles()->count() > 0) {
                // Log failed quick assign attempt
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($user)
                    ->withProperties([
                        'action' => 'quick_assign_failed',
                        'attempted_role' => $role->name,
                        'failure_reason' => 'User already has role',
                        'existing_roles' => $user->roles->pluck('name')->toArray(),
                        'operation_type' => 'quick_assign'
                    ])
                    ->log('Quick role assignment failed - user already has role');
                    
                return response()->json([
                    'success' => false,
                    'message' => 'User already has a role assigned'
                ]);
            }

            $user->assignRole($role);

            // Log successful quick assign
            activity()
                ->causedBy(auth()->user())
                ->performedOn($user)
                ->withProperties([
                    'action' => 'quick_role_assigned',
                    'assigned_role' => $role->name,
                    'role_id' => $role->id,
                    'permissions_granted' => $role->permissions->pluck('name')->toArray(),
                    'operation_type' => 'quick_assign',
                    'assignment_method' => 'ajax_quick_assign'
                ])
                ->log("Role '{$role->name}' quickly assigned to user");

            return response()->json([
                'success' => true,
                'message' => "Role '{$role->name}' assigned to {$user->name} successfully"
            ]);

        } catch (\Exception $e) {
            // Log quick assign error
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'action' => 'quick_assign_error',
                    'user_id' => $validated['user_id'],
                    'role_id' => $validated['role_id'],
                    'error_message' => $e->getMessage(),
                    'operation_type' => 'quick_assign'
                ])
                ->log('Quick role assignment failed due to system error');
                
            return response()->json([
                'success' => false,
                'message' => 'Error assigning role: ' . $e->getMessage()
            ]);
        }
    }
}