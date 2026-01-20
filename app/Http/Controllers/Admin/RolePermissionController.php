<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:roles-permissions index'])->only(['index']);
        $this->middleware(['permission:roles-permissions create'])->only(['create', 'store']);
        $this->middleware(['permission:roles-permissions update'])->only(['edit', 'update']);
        $this->middleware(['permission:roles-permissions destroy'])->only(['destroy']);
        $this->middleware(['permission:roles-permissions show'])->only(['show']);
    }
    function index()
    {
        $role = Role::all();
        return view('admin.role.index', compact('role'));
    }

    function create()
    {
        $permissions = Permission::all()->groupBy('group_name');
        return view('admin.role.create', compact('permissions'));
    }

    function store(Request $request)
    {
        $request->validate([
            'role' => 'required|string|max:50|unique:roles,name',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create(['guard_name' => 'web', 'name' => $request->role]);
        $role->syncPermissions($request->permissions);

        flash()
            ->option('position', 'bottom-right')
            ->success('Role Created successfully and Permission appropriately assigned!');
        return redirect()->route('role.index');
    }

    function edit(Request $request, String $id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all()->groupBy('group_name');
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('admin.role.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    function update(Request $request, String $id)
    {
        $request->validate([
            'role' => 'required|string|max:50|unique:roles,name,' . $id,
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::findOrFail($id);
        
        // Prevent editing Super Admin role name
        if ($role->name !== 'Super Admin') {
            $role->name = $request->role;
            $role->save();
        }

        $role->syncPermissions($request->permissions);

        flash()
            ->option('position', 'bottom-right')
            ->success('Role Updated successfully and Permission appropriately assigned!');
        return redirect()->route('role.index');
    }

    function show(String $id)
    {
        $role = Role::with(['permissions', 'users'])->findOrFail($id);
        return view('admin.role.show', compact('role'));
    }

    // NEW MISSING FUNCTIONS BELOW:

    /**
     * Delete a role
     */
    function destroy(String $id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Prevent deleting Super Admin role
            if ($role->name === 'Super Admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Super Admin role cannot be deleted for security reasons.'
                ], 403);
            }
            
            // Check if role has users assigned
            if ($role->users()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role that has ' . $role->users()->count() . ' user(s) assigned. Please reassign users first.'
                ], 400);
            }
            
            $roleName = $role->name;
            $role->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Role '{$roleName}' deleted successfully."
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get role details for AJAX modal
     */
    function details(String $id)
    {
        try {
            $role = Role::with(['permissions', 'users'])->findOrFail($id);
            
            $html = view('admin.role.partials.details', compact('role'))->render();
            
            return response()->json([
                'success' => true,
                'html' => $html,
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions_count' => $role->permissions->count(),
                    'users_count' => $role->users->count(),
                    'created_at' => $role->created_at->format('M d, Y H:i'),
                    'updated_at' => $role->updated_at->format('M d, Y H:i')
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error loading role details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete roles
     */
    function bulkDelete(Request $request)
    {
        $request->validate([
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id'
        ]);

        try {
            $roles = Role::whereIn('id', $request->role_ids)->get();
            $deleted = 0;
            $errors = [];
            $skipped = [];

            foreach ($roles as $role) {
                // Skip Super Admin role
                if ($role->name === 'Super Admin') {
                    $skipped[] = "Cannot delete Super Admin role";
                    continue;
                }
                
                // Skip roles with assigned users
                if ($role->users()->count() > 0) {
                    $skipped[] = "Role '{$role->name}' has {$role->users()->count()} assigned user(s)";
                    continue;
                }
                
                try {
                    $role->delete();
                    $deleted++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to delete role '{$role->name}': " . $e->getMessage();
                }
            }

            $message = "Successfully deleted {$deleted} role(s)";
            
            if (!empty($skipped)) {
                $message .= ". Skipped: " . implode(', ', $skipped);
            }
            
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'deleted_count' => $deleted,
                'skipped_count' => count($skipped),
                'error_count' => count($errors)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during bulk delete: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show manage permissions page for a specific role
     */
    function managePermissions(String $id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        $permissions = Permission::all()->groupBy('group_name');
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        
        return view('admin.role.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update permissions only (separate from role update)
     */
    function updatePermissions(Request $request, String $id)
    {
        $request->validate([
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name'
        ]);

        try {
            $role = Role::findOrFail($id);
            $oldPermissions = $role->permissions->pluck('name')->toArray();
            
            $role->syncPermissions($request->permissions);
            
            $newPermissions = $request->permissions;
            $added = array_diff($newPermissions, $oldPermissions);
            $removed = array_diff($oldPermissions, $newPermissions);
            
            $message = "Permissions updated successfully for role '{$role->name}'";
            if (!empty($added)) {
                $message .= ". Added: " . count($added) . " permission(s)";
            }
            if (!empty($removed)) {
                $message .= ". Removed: " . count($removed) . " permission(s)";
            }
            
            flash()
                ->option('position', 'bottom-right')
                ->success($message);
                
            return redirect()->route('role.show', $id);
            
        } catch (\Exception $e) {
            flash()
                ->option('position', 'bottom-right')
                ->error('Error updating permissions: ' . $e->getMessage());
                
            return redirect()->back();
        }
    }

    /**
     * Clone/Duplicate a role with all its permissions
     */
    function duplicate(Request $request, String $id)
    {
        $request->validate([
            'new_role_name' => 'required|string|max:50|unique:roles,name'
        ]);

        try {
            $originalRole = Role::with('permissions')->findOrFail($id);
            
            $newRole = Role::create([
                'guard_name' => $originalRole->guard_name,
                'name' => $request->new_role_name
            ]);
            
            $newRole->syncPermissions($originalRole->permissions->pluck('name')->toArray());
            
            flash()
                ->option('position', 'bottom-right')
                ->success("Role '{$request->new_role_name}' created successfully as a copy of '{$originalRole->name}'!");
                
            return redirect()->route('role.show', $newRole->id);
            
        } catch (\Exception $e) {
            flash()
                ->option('position', 'bottom-right')
                ->error('Error duplicating role: ' . $e->getMessage());
                
            return redirect()->back();
        }
    }

    /**
     * Export role data as JSON
     */
    function export(String $id)
    {
        try {
            $role = Role::with(['permissions', 'users'])->findOrFail($id);
            
            $exportData = [
                'role' => [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'created_at' => $role->created_at->toISOString(),
                    'updated_at' => $role->updated_at->toISOString()
                ],
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'name' => $permission->name,
                        'group_name' => $permission->group_name ?? 'default'
                    ];
                }),
                'statistics' => [
                    'total_permissions' => $role->permissions->count(),
                    'assigned_users' => $role->users->count(),
                    'permission_groups' => $role->permissions->groupBy('group_name')->count()
                ],
                'export_info' => [
                    'exported_at' => now()->toISOString(),
                    'exported_by' => Auth::user()->name ?? 'System'
                ]
            ];
            
            $fileName = 'role-' . strtolower(str_replace(' ', '-', $role->name)) . '-export-' . date('Y-m-d-H-i-s') . '.json';
            
            return response()->json($exportData)
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->header('Content-Type', 'application/json');
                
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error exporting role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get roles statistics for dashboard
     */
    function getStatistics()
    {
        try {
            $stats = [
                'total_roles' => Role::count(),
                'total_permissions' => Permission::count(),
                'roles_with_users' => Role::has('users')->count(),
                'empty_roles' => Role::doesntHave('users')->count(),
                'recent_roles' => Role::where('created_at', '>=', now()->subDays(30))->count(),
                'permission_groups' => Permission::select('group_name')->distinct()->count(),
                'most_used_role' => Role::withCount('users')->orderByDesc('users_count')->first(),
                'least_used_role' => Role::withCount('users')->orderBy('users_count')->first()
            ];
            
            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error getting statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search roles by name or permissions
     */
    function search(Request $request)
    {
        $query = $request->get('q', '');
        $perPage = $request->get('per_page', 15);
        
        try {
            $roles = Role::with(['permissions', 'users'])
                ->where('name', 'LIKE', "%{$query}%")
                ->orWhereHas('permissions', function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%");
                })
                ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $roles,
                'query' => $query
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching roles: ' . $e->getMessage()
            ], 500);
        }
    }
}