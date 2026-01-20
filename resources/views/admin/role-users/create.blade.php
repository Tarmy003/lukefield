@extends('backend.layouts.master')

@section('content')
<div class="page-content">
    <div class="container-xxl">
        
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1">Assign Role to User</h4>
                        <p class="text-muted mb-0">Assign roles to existing users who don't have roles yet</p>
                    </div>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('role-users.index') }}">Role Assignment</a></li>
                            <li class="breadcrumb-item active">Assign Role</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-shield me-2"></i>Role Assignment
                        </h5>
                    </div>
                    
                    <div class="card-body">
                        <form action="{{ route('role-users.store') }}" method="POST" id="assignRoleForm">
                            @csrf
                            
                            <!-- User Selection Section -->
                            <div class="mb-4">
                                <h6 class="mb-3 text-primary">
                                    <i class="fas fa-user me-2"></i>Select User
                                </h6>
                                
                                <!-- Search Users -->
                                <div class="mb-3">
                                    <label for="user_search" class="form-label">Search Users</label>
                                    <input type="text" class="form-control" id="user_search" 
                                           placeholder="Search by name, email, or employee ID..." 
                                           onkeyup="filterUsers()">
                                    <div class="form-text">Only users without assigned roles are shown</div>
                                </div>

                                <!-- Users Selection -->
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">{{ __('Select User') }} <span class="text-danger">*</span></label>
                                    <select name="user_id" id="user_id" class="form-select @error('user_id') is-invalid @enderror" onchange="showUserDetails()">
                                        <option value="">{{ __('Choose a user...') }}</option>
                                        @if(isset($usersWithoutRoles) && $usersWithoutRoles->count() > 0)
                                            @foreach($usersWithoutRoles as $user)
                                                <option value="{{ $user->id }}" 
                                                        data-name="{{ $user->name }}"
                                                        data-email="{{ $user->email }}"
                                                        data-phone="{{ $user->phone ?? 'Not provided' }}"
                                                        data-employee-id="{{ $user->employee_id ?? 'Not set' }}"
                                                        data-department="{{ $user->department->name ?? 'No department' }}"
                                                        data-created="{{ $user->created_at->format('M d, Y') }}"
                                                        data-last-login="{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never logged in' }}"
                                                        {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }} ({{ $user->email }})
                                                    @if($user->employee_id) - ID: {{ $user->employee_id }} @endif
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Selected User Preview -->
                                <div id="user_preview" class="card border-info" style="display: none;">
                                    <div class="card-header bg-info-subtle">
                                        <h6 class="card-title mb-0 text-info">
                                            <i class="fas fa-user-circle me-2"></i>Selected User Details
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div id="user_basic_details"></div>
                                            </div>
                                            <div class="col-md-6">
                                                <div id="user_additional_details"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Role Selection Section -->
                            <div class="mb-4">
                                <h6 class="mb-3 text-primary">
                                    <i class="fas fa-shield-alt me-2"></i>Select Role
                                </h6>
                                
                                <!-- Role Filter -->
                                <div class="mb-3">
                                    <label for="role_filter" class="form-label">Filter Roles</label>
                                    <input type="text" class="form-control" id="role_filter" 
                                           placeholder="Search roles..." 
                                           onkeyup="filterRoles()">
                                </div>

                                <div class="mb-3">
                                    <label for="role_id" class="form-label">{{ __('Select Role') }} <span class="text-danger">*</span></label>
                                    <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" onchange="showRoleDetails()">
                                        <option value="">{{ __('Choose a role...') }}</option>
                                        @if(isset($roles) && $roles->count() > 0)
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" 
                                                        data-name="{{ $role->name }}"
                                                        data-permissions="{{ $role->permissions->count() }}"
                                                        data-users="{{ $role->users->count() }}"
                                                        data-guard="{{ $role->guard_name }}"
                                                        data-created="{{ $role->created_at->format('M d, Y') }}"
                                                        data-permissions-list="{{ $role->permissions->pluck('name')->implode(', ') }}"
                                                        {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                                    {{ $role->name }} 
                                                    ({{ $role->permissions->count() }} permissions, {{ $role->users->count() }} users)
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('role_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Role Details Preview -->
                                <div id="role_preview" class="card border-success" style="display: none;">
                                    <div class="card-header bg-success-subtle">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="card-title mb-0 text-success">
                                                <i class="fas fa-shield-alt me-2"></i>Role Details
                                            </h6>
                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="togglePermissions()">
                                                <span id="permissions_toggle_text">Show Permissions</span>
                                                <i class="fas fa-chevron-down ms-1" id="permissions_toggle_icon"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="role_details"></div>
                                        <div id="role_permissions" class="mt-3" style="display: none;">
                                            <!-- Permissions will be loaded here -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Assignment Confirmation -->
                            <div id="assignment_confirmation" class="card border-warning" style="display: none;">
                                <div class="card-header bg-warning-subtle">
                                    <h6 class="card-title mb-0 text-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>Assignment Confirmation
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="assignment_summary_details"></div>
                                    <div class="mt-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="confirm_assignment" name="confirm_assignment" required>
                                            <label class="form-check-label" for="confirm_assignment">
                                                I confirm that I want to assign this role to the selected user
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Options -->
                            <div class="mb-4">
                                <h6 class="mb-3">Notification Options</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="send_notification_email" name="send_notification_email" value="1" checked>
                                            <label class="form-check-label" for="send_notification_email">
                                                Send role assignment notification email
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="log_assignment" name="log_assignment" value="1" checked>
                                            <label class="form-check-label" for="log_assignment">
                                                Log this assignment for audit trail
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="submit_btn" disabled>
                                    <i class="fas fa-user-shield me-1"></i>Assign Role
                                </button>
                                <button type="button" class="btn btn-info" onclick="previewAssignment()" id="preview_btn" disabled>
                                    <i class="fas fa-eye me-1"></i>Preview Assignment
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

            <!-- Sidebar with Information -->
            <div class="col-lg-4">
                <!-- Assignment Summary -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-clipboard-check me-2"></i>Assignment Summary
                        </h6>
                        <div id="live_assignment_summary">
                            <div class="text-muted text-center py-3">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <p class="mb-0">Select a user and role to see assignment summary</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Users Without Roles Stats -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-users me-2"></i>Users Without Roles
                        </h6>
                        @if(isset($usersWithoutRoles) && $usersWithoutRoles->count() > 0)
                            <div class="text-center mb-3">
                                <div class="fs-3 fw-bold text-warning">{{ $usersWithoutRoles->count() }}</div>
                                <div class="text-muted">Users need role assignment</div>
                            </div>
                            
                            <div class="list-group list-group-flush">
                                @foreach($usersWithoutRoles->take(5) as $user)
                                    <div class="list-group-item px-0 py-2 border-0">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-2">
                                                <div class="avatar-sm rounded-circle bg-warning-subtle">
                                                    <div class="avatar-title rounded-circle bg-warning">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-medium small">{{ $user->name }}</div>
                                                <div class="text-muted small">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                
                                @if($usersWithoutRoles->count() > 5)
                                    <div class="text-center mt-2">
                                        <small class="text-muted">and {{ $usersWithoutRoles->count() - 5 }} more...</small>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                <p class="text-success mb-0">All users have roles assigned!</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Available Roles -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-shield-alt me-2"></i>Available Roles
                        </h6>
                        @if(isset($roles) && $roles->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($roles as $role)
                                    <div class="list-group-item px-0 py-2 border-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-medium small">{{ $role->name }}</div>
                                                <div class="text-muted small">{{ $role->permissions->count() }} permissions</div>
                                            </div>
                                            <span class="badge bg-{{ $role->users->count() > 0 ? 'primary' : 'secondary' }}">
                                                {{ $role->users->count() }} users
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted small">No roles available</div>
                        @endif
                    </div>
                </div>

                <!-- Tips -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-lightbulb me-2"></i>Best Practices
                        </h6>
                        <ul class="text-muted small mb-0">
                            <li>Review user's responsibilities before assigning roles</li>
                            <li>Choose the role with minimum required permissions</li>
                            <li>Notify users about their new role assignments</li>
                            <li>Document role assignments for compliance</li>
                            <li>Regularly audit user role assignments</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles */
.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.form-check.form-switch {
    padding-left: 2.5em;
    min-height: 1.5rem;
}

.form-check.form-switch .form-check-input {
    width: 2em;
    margin-left: -2.5em;
}

#role_permissions {
    max-height: 200px;
    overflow-y: auto;
}

.permission-badge {
    font-size: 0.75rem;
    margin: 0.1rem;
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Highlight selected options */
.form-select option:checked {
    background-color: var(--bs-primary);
    color: white;
}
</style>

<script>
// Filter users based on search input
function filterUsers() {
    const searchTerm = document.getElementById('user_search').value.toLowerCase();
    const userSelect = document.getElementById('user_id');
    const options = userSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') return; // Skip the default option
        
        const text = option.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

// Filter roles based on search input
function filterRoles() {
    const searchTerm = document.getElementById('role_filter').value.toLowerCase();
    const roleSelect = document.getElementById('role_id');
    const options = roleSelect.querySelectorAll('option');
    
    options.forEach(option => {
        if (option.value === '') return; // Skip the default option
        
        const text = option.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

// Show selected user details
function showUserDetails() {
    const userSelect = document.getElementById('user_id');
    const userPreview = document.getElementById('user_preview');
    const userBasicDetails = document.getElementById('user_basic_details');
    const userAdditionalDetails = document.getElementById('user_additional_details');
    
    if (userSelect.value) {
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        
        userBasicDetails.innerHTML = `
            <div class="mb-2">
                <strong>Name:</strong> ${selectedOption.getAttribute('data-name')}
            </div>
            <div class="mb-2">
                <strong>Email:</strong> ${selectedOption.getAttribute('data-email')}
            </div>
            <div class="mb-2">
                <strong>Phone:</strong> ${selectedOption.getAttribute('data-phone')}
            </div>
        `;
        
        userAdditionalDetails.innerHTML = `
            <div class="mb-2">
                <strong>Employee ID:</strong> ${selectedOption.getAttribute('data-employee-id')}
            </div>
            <div class="mb-2">
                <strong>Department:</strong> ${selectedOption.getAttribute('data-department')}
            </div>
            <div class="mb-2">
                <strong>Joined:</strong> ${selectedOption.getAttribute('data-created')}
            </div>
            <div class="mb-2">
                <strong>Last Login:</strong> ${selectedOption.getAttribute('data-last-login')}
            </div>
        `;
        
        userPreview.style.display = 'block';
    } else {
        userPreview.style.display = 'none';
    }
    
    updateAssignmentSummary();
    checkFormCompletion();
}

// Show selected role details
function showRoleDetails() {
    const roleSelect = document.getElementById('role_id');
    const rolePreview = document.getElementById('role_preview');
    const roleDetails = document.getElementById('role_details');
    const rolePermissions = document.getElementById('role_permissions');
    
    if (roleSelect.value) {
        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const permissions = selectedOption.getAttribute('data-permissions');
        const users = selectedOption.getAttribute('data-users');
        const guard = selectedOption.getAttribute('data-guard');
        const created = selectedOption.getAttribute('data-created');
        const permissionsList = selectedOption.getAttribute('data-permissions-list');
        
        roleDetails.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="text-center p-2 border rounded">
                        <div class="fs-5 fw-bold text-primary">${permissions}</div>
                        <div class="text-muted small">Permissions</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="text-center p-2 border rounded">
                        <div class="fs-5 fw-bold text-success">${users}</div>
                        <div class="text-muted small">Current Users</div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Guard:</strong> ${guard}
                    </div>
                    <div class="col-md-6">
                        <strong>Created:</strong> ${created}
                    </div>
                </div>
            </div>
        `;
        
        // Load permissions
        if (permissionsList) {
            const permissions = permissionsList.split(', ');
            let permissionsHtml = '<div class="d-flex flex-wrap gap-1">';
            permissions.forEach(permission => {
                permissionsHtml += `<span class="badge bg-primary permission-badge">${permission}</span>`;
            });
            permissionsHtml += '</div>';
            rolePermissions.innerHTML = permissionsHtml;
        } else {
            rolePermissions.innerHTML = '<div class="text-muted">No permissions assigned</div>';
        }
        
        rolePreview.style.display = 'block';
    } else {
        rolePreview.style.display = 'none';
    }
    
    updateAssignmentSummary();
    checkFormCompletion();
}

// Toggle permissions visibility
function togglePermissions() {
    const permissionsDiv = document.getElementById('role_permissions');
    const toggleText = document.getElementById('permissions_toggle_text');
    const toggleIcon = document.getElementById('permissions_toggle_icon');
    
    if (permissionsDiv.style.display === 'none') {
        permissionsDiv.style.display = 'block';
        toggleText.textContent = 'Hide Permissions';
        toggleIcon.classList.remove('fa-chevron-down');
        toggleIcon.classList.add('fa-chevron-up');
    } else {
        permissionsDiv.style.display = 'none';
        toggleText.textContent = 'Show Permissions';
        toggleIcon.classList.remove('fa-chevron-up');
        toggleIcon.classList.add('fa-chevron-down');
    }
}

// Update assignment summary
function updateAssignmentSummary() {
    const userSelect = document.getElementById('user_id');
    const roleSelect = document.getElementById('role_id');
    const summaryDiv = document.getElementById('live_assignment_summary');
    const confirmationDiv = document.getElementById('assignment_confirmation');
    const confirmationDetails = document.getElementById('assignment_summary_details');
    
    if (userSelect.value && roleSelect.value) {
        const userName = userSelect.options[userSelect.selectedIndex].getAttribute('data-name');
        const userEmail = userSelect.options[userSelect.selectedIndex].getAttribute('data-email');
        const roleName = roleSelect.options[roleSelect.selectedIndex].getAttribute('data-name');
        const rolePermissions = roleSelect.options[roleSelect.selectedIndex].getAttribute('data-permissions');
        
        const summary = `
            <div class="alert alert-info">
                <h6 class="alert-heading mb-2">Ready to Assign</h6>
                <div class="mb-2"><strong>User:</strong> ${userName}</div>
                <div class="mb-2"><strong>Email:</strong> ${userEmail}</div>
                <div class="mb-2"><strong>Role:</strong> ${roleName}</div>
                <div class="mb-0"><strong>Permissions:</strong> ${rolePermissions}</div>
            </div>
        `;
        
        summaryDiv.innerHTML = summary;
        
        // Show confirmation section
        confirmationDetails.innerHTML = `
            <p class="mb-2">You are about to assign the role <strong>"${roleName}"</strong> to user <strong>"${userName}"</strong>.</p>
            <p class="mb-2">This will grant the user <strong>${rolePermissions} permissions</strong>.</p>
            <p class="mb-0 text-warning"><small><i class="fas fa-exclamation-triangle me-1"></i>This action can be reversed later by removing the role assignment.</small></p>
        `;
        
        confirmationDiv.style.display = 'block';
    } else {
        summaryDiv.innerHTML = `
            <div class="text-muted text-center py-3">
                <i class="fas fa-info-circle fa-2x mb-2"></i>
                <p class="mb-0">Select a user and role to see assignment summary</p>
            </div>
        `;
        confirmationDiv.style.display = 'none';
    }
}

// Check if form is complete and enable/disable buttons
function checkFormCompletion() {
    const userSelect = document.getElementById('user_id');
    const roleSelect = document.getElementById('role_id');
    const submitBtn = document.getElementById('submit_btn');
    const previewBtn = document.getElementById('preview_btn');
    
    const isComplete = userSelect.value && roleSelect.value;
    
    submitBtn.disabled = !isComplete;
    previewBtn.disabled = !isComplete;
    
    if (isComplete) {
        submitBtn.classList.remove('btn-secondary');
        submitBtn.classList.add('btn-primary');
        previewBtn.classList.remove('btn-secondary');
        previewBtn.classList.add('btn-info');
    } else {
        submitBtn.classList.remove('btn-primary');
        submitBtn.classList.add('btn-secondary');
        previewBtn.classList.remove('btn-info');
        previewBtn.classList.add('btn-secondary');
    }
}

// Preview assignment
function previewAssignment() {
    updateAssignmentSummary();
    document.getElementById('assignment_confirmation').scrollIntoView({ behavior: 'smooth' });
}

// Reset form
function resetForm() {
    document.getElementById('assignRoleForm').reset();
    document.getElementById('user_preview').style.display = 'none';
    document.getElementById('role_preview').style.display = 'none';
    document.getElementById('assignment_confirmation').style.display = 'none';
    updateAssignmentSummary();
    checkFormCompletion();
}

// Form validation
document.getElementById('assignRoleForm').addEventListener('submit', function(e) {
    const userSelect = document.getElementById('user_id');
    const roleSelect = document.getElementById('role_id');
    const confirmCheckbox = document.getElementById('confirm_assignment');
    
    if (!userSelect.value) {
        e.preventDefault();
        alert('Please select a user');
        userSelect.focus();
        return;
    }
    
    if (!roleSelect.value) {
        e.preventDefault();
        alert('Please select a role');
        roleSelect.focus();
        return;
    }
    
    if (!confirmCheckbox.checked) {
        e.preventDefault();
        alert('Please confirm the assignment by checking the confirmation box');
        confirmCheckbox.focus();
        return;
    }
});

// Initialize form state on page load
document.addEventListener('DOMContentLoaded', function() {
    checkFormCompletion();
    updateAssignmentSummary();
});
</script>

@endsection