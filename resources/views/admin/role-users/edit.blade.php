@extends('backend.layouts.master')

@section('content')
<div class="page-content">
    <div class="container-xxl">
        
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1">Edit User Role</h4>
                        <p class="text-muted mb-0">Change role assignment for {{ $user->name }}</p>
                    </div>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('role-users.index') }}">Role Assignment</a></li>
                            <li class="breadcrumb-item active">Edit Assignment</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- User Information Card -->
                <div class="card">
                    <div class="card-header bg-info-subtle">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('backend/default-avatar.jpg') }}" 
                                     alt="{{ $user->name }}" class="avatar-lg rounded-circle">
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1">{{ $user->name }}</h5>
                                <p class="text-muted mb-1">{{ $user->email }}</p>
                                <div class="d-flex gap-2">
                                    @if($user->employee_id)
                                        <span class="badge bg-light text-dark">ID: {{ $user->employee_id }}</span>
                                    @endif
                                    @if($user->department)
                                        <span class="badge bg-secondary">{{ $user->department->name }}</span>
                                    @endif
                                    <span class="badge bg-{{ $user->is_active ? 'success' : 'danger' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <div class="text-end">
                                    <div class="small text-muted">Joined</div>
                                    <div class="fw-medium">{{ $user->created_at->format('M d, Y') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Role Status -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Current Role Assignment
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($user->roles->count() > 0)
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">Current Role</h6>
                                    @foreach($user->roles as $role)
                                        <div class="d-flex align-items-center mb-3 p-3 border rounded bg-light">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar-sm rounded-circle bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}-subtle">
                                                    <div class="avatar-title rounded-circle bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}">
                                                        <i class="fas fa-{{ $role->name === 'Super Admin' ? 'crown' : ($role->name === 'Admin' ? 'user-shield' : 'user') }} text-white"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">{{ $role->name }}</h6>
                                                <p class="text-muted mb-0 small">{{ $role->permissions->count() }} permissions</p>
                                                <p class="text-muted mb-0 small">
                                                    Assigned: {{ $role->pivot->created_at ? $role->pivot->created_at->format('M d, Y H:i') : 'Unknown' }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-success mb-3">Current Permissions</h6>
                                    <div class="permissions-list" style="max-height: 300px; overflow-y: auto;">
                                        @php
                                            $userPermissions = $user->getAllPermissions()->groupBy('group_name');
                                        @endphp
                                        @foreach($userPermissions as $groupName => $permissions)
                                            <div class="mb-3">
                                                <div class="fw-medium text-muted small mb-1">{{ $groupName }}</div>
                                                <div class="d-flex flex-wrap gap-1">
                                                    @foreach($permissions as $permission)
                                                        <span class="badge bg-success-subtle text-success">{{ $permission->name }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-user-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Role Assigned</h5>
                                <p class="text-muted">This user currently has no role assigned.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Role Change Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exchange-alt me-2"></i>Change Role Assignment
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('role-users.update', $user->id) }}" method="POST" id="roleUpdateForm">
                            @csrf
                            @method('PUT')
                            
                            <!-- Role Selection -->
                            <div class="mb-4">
                                <label for="role_id" class="form-label">
                                    {{ __('Select New Role') }} <span class="text-danger">*</span>
                                </label>
                                <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" onchange="showRoleComparison()">
                                    <option value="">{{ __('Choose a role...') }}</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" 
                                                data-name="{{ $role->name }}"
                                                data-permissions="{{ $role->permissions->count() }}"
                                                data-users="{{ $role->users->count() }}"
                                                data-guard="{{ $role->guard_name }}"
                                                data-permissions-list="{{ $role->permissions->pluck('name')->implode('|') }}"
                                                data-permissions-groups="{{ $role->permissions->groupBy('group_name')->keys()->implode('|') }}"
                                                {{ old('role_id', $user->roles->first()?->id) == $role->id ? 'selected' : '' }}>
                                            {{ $role->name }} 
                                            ({{ $role->permissions->count() }} permissions, {{ $role->users->count() }} users)
                                        </option>
                                    @endforeach
                                </select>
                                @error('role_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Select a new role to assign to this user</div>
                            </div>

                            <!-- Role Comparison -->
                            <div id="role_comparison" class="mb-4" style="display: none;">
                                <div class="card border-warning">
                                    <div class="card-header bg-warning-subtle">
                                        <h6 class="card-title mb-0 text-warning">
                                            <i class="fas fa-balance-scale me-2"></i>Role Comparison
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-danger">Current Role</h6>
                                                <div id="current_role_details">
                                                    @if($user->roles->first())
                                                        <div class="mb-2">
                                                            <strong>Name:</strong> {{ $user->roles->first()->name }}
                                                        </div>
                                                        <div class="mb-2">
                                                            <strong>Permissions:</strong> {{ $user->getAllPermissions()->count() }}
                                                        </div>
                                                    @else
                                                        <div class="text-muted">No current role</div>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <h6 class="text-success">New Role</h6>
                                                <div id="new_role_details">
                                                    <!-- Will be populated by JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <hr>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <h6 class="text-info">Permissions Added</h6>
                                                <div id="permissions_added" class="permissions-diff">
                                                    <!-- Will be populated by JavaScript -->
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="text-warning">Permissions Removed</h6>
                                                <div id="permissions_removed" class="permissions-diff">
                                                    <!-- Will be populated by JavaScript -->
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="text-success">Permissions Kept</h6>
                                                <div id="permissions_kept" class="permissions-diff">
                                                    <!-- Will be populated by JavaScript -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Reason -->
                            <div class="mb-4">
                                <label for="change_reason" class="form-label">Reason for Change (Optional)</label>
                                <textarea name="change_reason" id="change_reason" class="form-control" rows="3" 
                                          placeholder="Enter reason for role change (for audit purposes)">{{ old('change_reason') }}</textarea>
                                <div class="form-text">This information will be logged for audit purposes</div>
                            </div>

                            <!-- Notification Options -->
                            <div class="mb-4">
                                <h6 class="mb-3">Notification Options</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="send_notification_email" 
                                                   name="send_notification_email" value="1" 
                                                   {{ old('send_notification_email', true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="send_notification_email">
                                                Send role change notification email
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="log_change" 
                                                   name="log_change" value="1" checked>
                                            <label class="form-check-label" for="log_change">
                                                Log this change for audit trail
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Confirmation -->
                            <div class="mb-4" id="confirmation_section" style="display: none;">
                                <div class="card border-danger">
                                    <div class="card-header bg-danger-subtle">
                                        <h6 class="card-title mb-0 text-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>Confirmation Required
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="confirmation_details"></div>
                                        <div class="mt-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="confirm_change" 
                                                       name="confirm_change" required>
                                                <label class="form-check-label" for="confirm_change">
                                                    I understand the implications and confirm this role change
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="update_btn" disabled>
                                    <i class="fas fa-save me-1"></i>Update Role Assignment
                                </button>
                                <button type="button" class="btn btn-info" onclick="previewChanges()" id="preview_btn" disabled>
                                    <i class="fas fa-eye me-1"></i>Preview Changes
                                </button>
                                <button type="reset" class="btn btn-warning" onclick="resetForm()">
                                    <i class="fas fa-undo me-1"></i>Reset Form
                                </button>
                                <a href="{{ route('role-users.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i>Back to List
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- User Statistics -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-chart-bar me-2"></i>User Statistics
                        </h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center p-2 border rounded">
                                    <div class="fs-5 fw-bold text-primary">{{ $user->getAllPermissions()->count() }}</div>
                                    <div class="text-muted small">Current Permissions</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 border rounded">
                                    <div class="fs-5 fw-bold text-success">{{ $user->roles->count() }}</div>
                                    <div class="text-muted small">Assigned Roles</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 border rounded">
                                    <div class="fs-5 fw-bold text-info">{{ $user->created_at->diffInDays(now()) }}</div>
                                    <div class="text-muted small">Days with Us</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 border rounded">
                                    <div class="fs-5 fw-bold text-warning">
                                        {{ $user->last_login_at ? $user->last_login_at->diffInDays(now()) : 'Never' }}
                                    </div>
                                    <div class="text-muted small">Days Since Login</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Change Summary -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-clipboard-check me-2"></i>Change Summary
                        </h6>
                        <div id="live_change_summary">
                            <div class="text-muted text-center py-3">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p class="mb-0">Select a new role to see change summary</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Roles -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-shield-alt me-2"></i>Available Roles
                        </h6>
                        <div class="list-group list-group-flush">
                            @foreach($roles as $role)
                                <div class="list-group-item px-0 py-2 border-0 {{ $user->hasRole($role->name) ? 'bg-light' : '' }}">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium small">
                                                {{ $role->name }}
                                                @if($user->hasRole($role->name))
                                                    <span class="badge bg-success ms-1">Current</span>
                                                @endif
                                            </div>
                                            <div class="text-muted small">{{ $role->permissions->count() }} permissions</div>
                                        </div>
                                        <span class="badge bg-{{ $role->users->count() > 0 ? 'primary' : 'secondary' }}">
                                            {{ $role->users->count() }} users
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </h6>
                        <div class="timeline">
                            @if($user->roles->first())
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <div class="fw-medium small">Role Assigned</div>
                                        <div class="text-muted small">{{ $user->roles->first()->name }}</div>
                                        <div class="text-muted small">
                                            {{ $user->roles->first()->pivot->created_at ? $user->roles->first()->pivot->created_at->diffForHumans() : 'Unknown' }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <div class="fw-medium small">Account Created</div>
                                    <div class="text-muted small">{{ $user->created_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            
                            @if($user->last_login_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <div class="fw-medium small">Last Login</div>
                                        <div class="text-muted small">{{ $user->last_login_at->diffForHumans() }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-bolt me-2"></i>Quick Actions
                        </h6>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-info btn-sm" onclick="viewUserDetails()">
                                <i class="fas fa-user me-1"></i>View Full Profile
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="removeAllRoles()">
                                <i class="fas fa-user-minus me-1"></i>Remove All Roles
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="exportUserData()">
                                <i class="fas fa-download me-1"></i>Export User Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles */
.avatar-lg {
    width: 4rem;
    height: 4rem;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.permissions-list {
    background-color: #f8f9fa !important;
    border-radius: 0.375rem;
    padding: 1rem;
}

.permissions-diff {
    min-height: 60px;
    padding: 0.5rem;
    background-color: #f8f9fa;
    border-radius: 0.25rem;
    border: 1px solid #dee2e6;
}

.permissions-diff:empty::before {
    content: 'None';
    color: #6c757d;
    font-style: italic;
}

.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline-item {
    position: relative;
    margin-bottom: 1rem;
}

.timeline-marker {
    position: absolute;
    left: -1.75rem;
    top: 0.25rem;
    width: 0.75rem;
    height: 0.75rem;
    border-radius: 50%;
}

.timeline-content {
    padding-left: 0.5rem;
}

/* Form states */
.form-check.form-switch {
    padding-left: 2.5em;
    min-height: 1.5rem;
}

.form-check.form-switch .form-check-input {
    width: 2em;
    margin-left: -2.5em;
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<script>
// Store current user permissions for comparison
const currentPermissions = @json($user->getAllPermissions()->pluck('name')->toArray());

// Show role comparison when new role is selected
function showRoleComparison() {
    const roleSelect = document.getElementById('role_id');
    const comparisonDiv = document.getElementById('role_comparison');
    const confirmationSection = document.getElementById('confirmation_section');
    const updateBtn = document.getElementById('update_btn');
    const previewBtn = document.getElementById('preview_btn');
    
    if (roleSelect.value) {
        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const newRoleName = selectedOption.getAttribute('data-name');
        const newPermissions = selectedOption.getAttribute('data-permissions-list').split('|').filter(p => p);
        const newPermissionsCount = selectedOption.getAttribute('data-permissions');
        const newUsersCount = selectedOption.getAttribute('data-users');
        
        // Update new role details
        document.getElementById('new_role_details').innerHTML = `
            <div class="mb-2"><strong>Name:</strong> ${newRoleName}</div>
            <div class="mb-2"><strong>Permissions:</strong> ${newPermissionsCount}</div>
            <div class="mb-2"><strong>Current Users:</strong> ${newUsersCount}</div>
        `;
        
        // Calculate permission differences
        const permissionsAdded = newPermissions.filter(p => !currentPermissions.includes(p));
        const permissionsRemoved = currentPermissions.filter(p => !newPermissions.includes(p));
        const permissionsKept = currentPermissions.filter(p => newPermissions.includes(p));
        
        // Update permission diff sections
        updatePermissionDiff('permissions_added', permissionsAdded, 'success');
        updatePermissionDiff('permissions_removed', permissionsRemoved, 'danger');
        updatePermissionDiff('permissions_kept', permissionsKept, 'info');
        
        // Show comparison
        comparisonDiv.style.display = 'block';
        
        // Update confirmation details
        const isCurrentRole = {{ $user->roles->first()?->id ?? 'null' }} == roleSelect.value;
        if (!isCurrentRole) {
            document.getElementById('confirmation_details').innerHTML = `
                <p class="mb-2">You are about to change the role for <strong>{{ $user->name }}</strong>:</p>
                <ul class="mb-2">
                    <li><strong>From:</strong> {{ $user->roles->first()?->name ?? 'No role' }}</li>
                    <li><strong>To:</strong> ${newRoleName}</li>
                    <li><strong>Permissions Added:</strong> ${permissionsAdded.length}</li>
                    <li><strong>Permissions Removed:</strong> ${permissionsRemoved.length}</li>
                </ul>
                <p class="text-warning small mb-0">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    This change will take effect immediately and may affect the user's access to system features.
                </p>
            `;
            confirmationSection.style.display = 'block';
            updateBtn.disabled = false;
            previewBtn.disabled = false;
        } else {
            confirmationSection.style.display = 'none';
            updateBtn.disabled = true;
            previewBtn.disabled = true;
        }
    } else {
        comparisonDiv.style.display = 'none';
        confirmationSection.style.display = 'none';
        updateBtn.disabled = true;
        previewBtn.disabled = true;
    }
    
    updateChangeSummary();
}

// Update permission difference display
function updatePermissionDiff(elementId, permissions, badgeClass) {
    const element = document.getElementById(elementId);
    if (permissions.length > 0) {
        element.innerHTML = permissions.map(p => 
            `<span class="badge bg-${badgeClass}-subtle text-${badgeClass} me-1 mb-1">${p}</span>`
        ).join('');
    } else {
        element.innerHTML = '';
    }
}

// Update change summary in sidebar
function updateChangeSummary() {
    const roleSelect = document.getElementById('role_id');
    const summaryDiv = document.getElementById('live_change_summary');
    
    if (roleSelect.value) {
        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const newRoleName = selectedOption.getAttribute('data-name');
        const newPermissions = selectedOption.getAttribute('data-permissions-list').split('|').filter(p => p);
        
        const permissionsAdded = newPermissions.filter(p => !currentPermissions.includes(p));
        const permissionsRemoved = currentPermissions.filter(p => !newPermissions.includes(p));
        
        const isCurrentRole = {{ $user->roles->first()?->id ?? 'null' }} == roleSelect.value;
        
        if (isCurrentRole) {
            summaryDiv.innerHTML = `
                <div class="alert alert-info">
                    <h6 class="alert-heading mb-2">No Changes</h6>
                    <p class="mb-0">This user already has the selected role assigned.</p>
                </div>
            `;
        } else {
            summaryDiv.innerHTML = `
                <div class="alert alert-warning">
                    <h6 class="alert-heading mb-2">Pending Changes</h6>
                    <div class="mb-2"><strong>New Role:</strong> ${newRoleName}</div>
                    <div class="mb-2"><strong>Permissions Added:</strong> ${permissionsAdded.length}</div>
                    <div class="mb-0"><strong>Permissions Removed:</strong> ${permissionsRemoved.length}</div>
                </div>
            `;
        }
    } else {
        summaryDiv.innerHTML = `
            <div class="text-muted text-center py-3">
                <i class="fas fa-info-circle fa-2x mb-2"></i>
                <p class="mb-0">Select a new role to see change summary</p>
            </div>
        `;
    }
}

// Preview changes
function previewChanges() {
    showRoleComparison();
    document.getElementById('role_comparison').scrollIntoView({ behavior: 'smooth' });
}

// Reset form
function resetForm() {
    document.getElementById('roleUpdateForm').reset();
    document.getElementById('role_comparison').style.display = 'none';
    document.getElementById('confirmation_section').style.display = 'none';
    document.getElementById('update_btn').disabled = true;
    document.getElementById('preview_btn').disabled = true;
    updateChangeSummary();
}

// View user details
function viewUserDetails() {
    window.open(`/users/{{ $user->id }}`, '_blank');
}

// Remove all roles
function removeAllRoles() {
    if (confirm('Are you sure you want to remove all roles from this user? This action cannot be undone.')) {
        fetch(`/assign-role/{{ $user->id }}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => window.location.href = '{{ route("role-users.index") }}', 1500);
            } else {
                showAlert(data.message || 'Error removing roles', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error removing roles', 'error');
        });
    }
}

// Export user data
function exportUserData() {
    const userData = {
        user: {
            id: {{ $user->id }},
            name: '{{ $user->name }}',
            email: '{{ $user->email }}',
            employee_id: '{{ $user->employee_id ?? '' }}',
            department: '{{ $user->department->name ?? '' }}',
            is_active: {{ $user->is_active ? 'true' : 'false' }},
            created_at: '{{ $user->created_at->toISOString() }}',
            last_login_at: '{{ $user->last_login_at?->toISOString() ?? '' }}'
        },
        current_roles: @json($user->roles->pluck('name')),
        current_permissions: @json($user->getAllPermissions()->pluck('name')),
        export_info: {
            exported_at: new Date().toISOString(),
            exported_by: '{{ auth()->user()->name }}'
        }
    };
    
    const dataStr = JSON.stringify(userData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `user-${userData.user.name.toLowerCase().replace(/\s+/g, '-')}-role-data.json`;
    link.click();
    
    URL.revokeObjectURL(url);
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const pageContent = document.querySelector('.page-content .container-xxl');
    pageContent.insertBefore(alertDiv, pageContent.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Form validation
document.getElementById('roleUpdateForm').addEventListener('submit', function(e) {
    const roleSelect = document.getElementById('role_id');
    const confirmCheckbox = document.getElementById('confirm_change');
    
    if (!roleSelect.value) {
        e.preventDefault();
        showAlert('Please select a role', 'warning');
        roleSelect.focus();
        return;
    }
    
    // Check if it's the same role
    const isCurrentRole = {{ $user->roles->first()?->id ?? 'null' }} == roleSelect.value;
    if (isCurrentRole) {
        e.preventDefault();
        showAlert('This user already has the selected role assigned', 'warning');
        return;
    }
    
    if (!confirmCheckbox.checked) {
        e.preventDefault();
        showAlert('Please confirm the role change by checking the confirmation box', 'warning');
        confirmCheckbox.focus();
        return;
    }
    
    // Show loading state
    const submitBtn = document.getElementById('update_btn');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Updating...';
    submitBtn.disabled = true;
    
    // Re-enable after 5 seconds in case of issues
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize form state
    showRoleComparison();
    
    // Auto-focus on role select if no current role
    @if(!$user->roles->first())
        document.getElementById('role_id').focus();
    @endif
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Listen for role selection changes
    document.getElementById('role_id').addEventListener('change', showRoleComparison);
    
    // Listen for confirmation checkbox changes
    const confirmCheckbox = document.getElementById('confirm_change');
    if (confirmCheckbox) {
        confirmCheckbox.addEventListener('change', function() {
            const updateBtn = document.getElementById('update_btn');
            if (this.checked && document.getElementById('role_id').value) {
                updateBtn.disabled = false;
                updateBtn.classList.remove('btn-secondary');
                updateBtn.classList.add('btn-primary');
            } else {
                updateBtn.disabled = true;
                updateBtn.classList.remove('btn-primary');
                updateBtn.classList.add('btn-secondary');
            }
        });
    }
});

// Warn before leaving with unsaved changes
let formChanged = false;
document.getElementById('roleUpdateForm').addEventListener('change', function() {
    formChanged = true;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Reset form changed flag on submit
document.getElementById('roleUpdateForm').addEventListener('submit', function() {
    formChanged = false;
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        const updateBtn = document.getElementById('update_btn');
        if (!updateBtn.disabled) {
            document.getElementById('roleUpdateForm').submit();
        }
    }
    
    // Ctrl/Cmd + R to reset
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        resetForm();
    }
});
</script>

@endsection