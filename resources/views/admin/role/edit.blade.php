@extends('backend.layouts.master')

@section('content')
<div class="page-content">
    <div class="container-xxl">
        
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1">Edit Role</h4>
                        <p class="text-muted mb-0">Update role "{{ $role->name }}" and its permissions</p>
                    </div>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('role.index') }}">Roles and Permissions</a></li>
                            <li class="breadcrumb-item active">Edit Role</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar-sm rounded-circle bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}-subtle">
                                    <div class="avatar-title rounded-circle bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}">
                                        <i class="fas fa-{{ $role->name === 'Super Admin' ? 'crown' : ($role->name === 'Admin' ? 'user-shield' : 'user') }} text-white"></i>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h5 class="card-title mb-1">{{ $role->name }}</h5>
                                <p class="text-muted mb-0 small">Created {{ $role->created_at->diffForHumans() }} • {{ $role->users->count() }} users assigned</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <form action="{{ route('role.update', $role->id) }}" method="POST" enctype="multipart/form-data" id="roleEditForm">
                            @csrf
                            @method('PUT')
                            
                            <!-- Basic Information -->
                            <div class="mb-4">
                                <label for="name" class="form-label">{{ __('Role Name') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('role') is-invalid @enderror" 
                                       name="role" id="name" placeholder="Enter role name" 
                                       value="{{ old('role', $role->name) }}" required
                                       @if($role->name === 'Super Admin') readonly @endif>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @if($role->name === 'Super Admin')
                                    <div class="form-text text-warning">
                                        <i class="fas fa-lock me-1"></i>Super Admin role name cannot be changed
                                    </div>
                                @endif
                            </div>

                            <hr class="my-4">

                            <!-- Current Permissions Summary -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Permissions Assignment</h6>
                                    <div class="badge bg-info">{{ count($rolePermissions) }} permissions currently assigned</div>
                                </div>
                                
                                <!-- Permission Statistics -->
                                <div class="row g-3 mb-4">
                                    @foreach($permissions as $groupName => $permission)
                                        @php
                                            $groupPermissions = $permission->pluck('name')->toArray();
                                            $assignedInGroup = array_intersect($rolePermissions, $groupPermissions);
                                            $percentage = count($groupPermissions) > 0 ? (count($assignedInGroup) / count($groupPermissions)) * 100 : 0;
                                        @endphp
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card border">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h6 class="mb-0 small">{{ $groupName }}</h6>
                                                        <span class="badge bg-light text-dark">{{ count($assignedInGroup) }}/{{ count($groupPermissions) }}</span>
                                                    </div>
                                                    <div class="progress" style="height: 4px;">
                                                        <div class="progress-bar" role="progressbar" style="width: {{ $percentage }}%"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Permissions Section -->
                            @foreach($permissions as $groupName => $permission)
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">{{ $groupName }}</h6>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-success" onclick="selectGroupAll('{{ $groupName }}')">
                                            <i class="fas fa-check-double me-1"></i>All
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="selectGroupNone('{{ $groupName }}')">
                                            <i class="fas fa-times me-1"></i>None
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach ($permission as $index => $item)
                                    <div class="permission-item">
                                        <input type="checkbox" 
                                               value="{{ $item->name }}" 
                                               name="permissions[]" 
                                               class="btn-check" 
                                               id="permission-{{ $groupName }}-{{ $index }}"
                                               autocomplete="off"
                                               @if(in_array($item->name, $rolePermissions)) checked @endif
                                               data-group="{{ $groupName }}">
                                        <label class="btn btn-outline-primary btn-sm" 
                                               for="permission-{{ $groupName }}-{{ $index }}">
                                            <i class="fas fa-key me-1"></i>{{ $item->name }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach

                            <!-- Changes Summary -->
                            <div class="alert alert-info" id="changesSummary" style="display: none;">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-1"></i>Changes Summary
                                </h6>
                                <div id="changesContent"></div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="updateBtn">
                                    <i class="fas fa-save me-1"></i>Update Role
                                </button>
                                <button type="button" class="btn btn-warning" onclick="resetToOriginal()">
                                    <i class="fas fa-undo me-1"></i>Reset Changes
                                </button>
                                <a href="{{ route('role.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar with Information -->
            <div class="col-lg-4">
                <!-- Current Role Info -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Role Information</h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="fs-4 fw-bold text-primary">{{ $role->users->count() }}</div>
                                    <div class="text-muted small">Users Assigned</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center">
                                    <div class="fs-4 fw-bold text-success">{{ count($rolePermissions) }}</div>
                                    <div class="text-muted small">Permissions</div>
                                </div>
                            </div>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div class="small text-muted">
                            <div class="mb-2">
                                <strong>Created:</strong> {{ $role->created_at->format('M d, Y H:i') }}
                            </div>
                            <div class="mb-2">
                                <strong>Last Updated:</strong> {{ $role->updated_at->format('M d, Y H:i') }}
                            </div>
                            <div>
                                <strong>Guard:</strong> {{ $role->guard_name }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Users -->
                @if($role->users->count() > 0)
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Assigned Users</h6>
                        <div class="list-group list-group-flush">
                            @foreach($role->users->take(5) as $user)
                            <div class="list-group-item px-0 py-2 border-0">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-2">
                                        <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('backend/default-avatar.jpg') }}" 
                                             alt="{{ $user->name }}" class="avatar-xs rounded-circle">
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-medium small">{{ $user->name }}</div>
                                        <div class="text-muted small">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            
                            @if($role->users->count() > 5)
                            <div class="text-center pt-2">
                                <a href="{{ route('admin.users.index', ['role' => $role->id]) }}" class="btn btn-outline-primary btn-sm">
                                    View All {{ $role->users->count() }} Users
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                <!-- Permission Guide -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Permission Guide</h6>
                        <div class="text-muted small">
                            <p><strong>Index:</strong> View list of items</p>
                            <p><strong>Create:</strong> Add new items</p>
                            <p><strong>Edit:</strong> Modify existing items</p>
                            <p><strong>Delete:</strong> Remove items</p>
                            <p><strong>Approve:</strong> Approve pending items</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="selectAll()">
                                <i class="fas fa-check-double me-1"></i>Select All Permissions
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="selectNone()">
                                <i class="fas fa-times me-1"></i>Deselect All Permissions
                            </button>
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="showChanges()">
                                <i class="fas fa-eye me-1"></i>Preview Changes
                            </button>
                        </div>
                    </div>
                </div>

                @if($role->name !== 'Super Admin')
                <!-- Danger Zone -->
                <div class="card border-danger">
                    <div class="card-body">
                        <h6 class="card-title text-danger">Danger Zone</h6>
                        <p class="small text-muted">Once you delete a role, there is no going back. Please be certain.</p>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteRole()">
                            <i class="fas fa-trash me-1"></i>Delete Role
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
/* Custom CSS for Permission Grid Layout */
.permission-item {
    flex: 0 0 auto;
}

/* Ensure switches are properly aligned */
.form-check.form-switch {
    padding-left: 2.5em;
    min-height: 1.5rem;
}

.form-check.form-switch .form-check-input {
    width: 2em;
    margin-left: -2.5em;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .d-flex.flex-wrap.gap-2 {
        flex-direction: column;
        gap: 0.5rem !important;
    }
    
    .permission-item {
        flex: 1 1 100%;
    }
}

/* Button checkbox styling */
.btn-check:checked + .btn {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

/* Avatar sizing */
.avatar-xs {
    width: 24px;
    height: 24px;
}

/* Changes indicator */
.permission-changed {
    position: relative;
}

.permission-changed::after {
    content: '•';
    position: absolute;
    top: -5px;
    right: -5px;
    color: #ffc107;
    font-size: 1.2em;
}
</style>

<script>
// Store original permissions for comparison
const originalPermissions = @json($rolePermissions);
let hasChanges = false;

// JavaScript functions for quick selection
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change'));
    });
    checkForChanges();
}

function selectNone() {
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
    });
    checkForChanges();
}

function selectGroupAll(groupName) {
    const checkboxes = document.querySelectorAll(`input[data-group="${groupName}"]`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change'));
    });
    checkForChanges();
}

function selectGroupNone(groupName) {
    const checkboxes = document.querySelectorAll(`input[data-group="${groupName}"]`);
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
    });
    checkForChanges();
}

function resetToOriginal() {
    const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = originalPermissions.includes(checkbox.value);
        checkbox.dispatchEvent(new Event('change'));
    });
    checkForChanges();
}

function checkForChanges() {
    const currentPermissions = Array.from(document.querySelectorAll('input[name="permissions[]"]:checked'))
        .map(cb => cb.value);
    
    const added = currentPermissions.filter(p => !originalPermissions.includes(p));
    const removed = originalPermissions.filter(p => !currentPermissions.includes(p));
    
    hasChanges = added.length > 0 || removed.length > 0;
    
    const updateBtn = document.getElementById('updateBtn');
    if (hasChanges) {
        updateBtn.classList.remove('btn-primary');
        updateBtn.classList.add('btn-warning');
        updateBtn.innerHTML = '<i class="fas fa-save me-1"></i>Save Changes (' + (added.length + removed.length) + ')';
    } else {
        updateBtn.classList.remove('btn-warning');
        updateBtn.classList.add('btn-primary');
        updateBtn.innerHTML = '<i class="fas fa-save me-1"></i>Update Role';
    }
    
    // Show/hide changes summary
    const changesSummary = document.getElementById('changesSummary');
    if (hasChanges) {
        const changesContent = document.getElementById('changesContent');
        let content = '';
        
        if (added.length > 0) {
            content += `<div class="mb-2"><strong class="text-success">Added (${added.length}):</strong><br>`;
            content += added.map(p => `<span class="badge bg-success-subtle text-success me-1">${p}</span>`).join('');
            content += '</div>';
        }
        
        if (removed.length > 0) {
            content += `<div><strong class="text-danger">Removed (${removed.length}):</strong><br>`;
            content += removed.map(p => `<span class="badge bg-danger-subtle text-danger me-1">${p}</span>`).join('');
            content += '</div>';
        }
        
        changesContent.innerHTML = content;
        changesSummary.style.display = 'block';
    } else {
        changesSummary.style.display = 'none';
    }
}

function showChanges() {
    checkForChanges();
    if (hasChanges) {
        document.getElementById('changesSummary').scrollIntoView({ behavior: 'smooth' });
    } else {
        alert('No changes detected');
    }
}

function deleteRole() {
    if (confirm('Are you sure you want to delete this role? This action cannot be undone and will affect {{ $role->users->count() }} users.')) {
        fetch(`{{ route('role.destroy', $role->id) }}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("role.index") }}';
            } else {
                alert(data.message || 'Error deleting role');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting role');
        });
    }
}

// Listen for permission changes
document.addEventListener('change', function(e) {
    if (e.target.name === 'permissions[]') {
        checkForChanges();
    }
});

// Form validation
document.getElementById('roleEditForm').addEventListener('submit', function(e) {
    const nameInput = document.getElementById('name');
    const checkedPermissions = document.querySelectorAll('input[name="permissions[]"]:checked');
    
    if (nameInput.value.trim() === '') {
        e.preventDefault();
        alert('Please enter a role name');
        nameInput.focus();
        return;
    }
    
    if (checkedPermissions.length === 0) {
        e.preventDefault();
        alert('Please select at least one permission');
        return;
    }
    
    // Confirm if major changes
    const currentPermissions = Array.from(checkedPermissions).map(cb => cb.value);
    const changeCount = Math.abs(currentPermissions.length - originalPermissions.length);
    
    if (changeCount > 5) {
        if (!confirm(`You are making ${changeCount} permission changes. Are you sure you want to continue?`)) {
            e.preventDefault();
            return;
        }
    }
});

// Initial check on page load
document.addEventListener('DOMContentLoaded', function() {
    checkForChanges();
});

// Warn before leaving with unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (hasChanges) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

@endsection