@extends('backend.layouts.master')

@section('content')
<div class="page-content">
    <div class="container-xxl">
        
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1">Role Details</h4>
                        <p class="text-muted mb-0">Viewing role "{{ $role->name }}" information and permissions</p>
                    </div>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('role.index') }}">Roles and Permissions</a></li>
                            <li class="breadcrumb-item active">{{ $role->name }}</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Overview -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Role Information Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar-lg rounded-circle bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}-subtle">
                                        <div class="avatar-title rounded-circle bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}">
                                            <i class="fas fa-{{ $role->name === 'Super Admin' ? 'crown' : ($role->name === 'Admin' ? 'user-shield' : 'user') }} text-white fs-24"></i>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="card-title mb-1">{{ $role->name }}</h5>
                                    <p class="text-muted mb-0">
                                        <span class="badge bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}-subtle text-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}">
                                            {{ $role->guard_name }} Guard
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-cog me-1"></i>Actions
                                </button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('role.edit', $role->id) }}">
                                            <i class="fas fa-edit me-2"></i>Edit Role
                                        </a>
                                    </li>
                                    {{-- <li>
                                        <a class="dropdown-item" href="{{ route('role.permissions', $role->id) }}">
                                            <i class="fas fa-key me-2"></i>Manage Permissions
                                        </a>
                                    </li> --}}
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="#" onclick="exportRoleData()">
                                            <i class="fas fa-download me-2"></i>Export Role Data
                                        </a>
                                    </li>
                                    @if($role->name !== 'Super Admin')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="deleteRole()">
                                                <i class="fas fa-trash me-2"></i>Delete Role
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Role Statistics -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="fs-3 fw-bold text-primary">{{ $role->permissions->count() }}</div>
                                    <div class="text-muted small">Total Permissions</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="fs-3 fw-bold text-success">{{ $role->users->count() }}</div>
                                    <div class="text-muted small">Assigned Users</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="fs-3 fw-bold text-info">{{ $role->permissions->groupBy('group_name')->count() }}</div>
                                    <div class="text-muted small">Permission Groups</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 border rounded">
                                    <div class="fs-3 fw-bold text-warning">{{ $role->created_at->diffInDays(now()) }}</div>
                                    <div class="text-muted small">Days Old</div>
                                </div>
                            </div>
                        </div>

                        <!-- Role Metadata -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Role Name</label>
                                    <div class="form-control-plaintext">{{ $role->name }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Guard Name</label>
                                    <div class="form-control-plaintext">{{ $role->guard_name }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Created Date</label>
                                    <div class="form-control-plaintext">
                                        {{ $role->created_at->format('M d, Y H:i') }}
                                        <small class="text-muted">({{ $role->created_at->diffForHumans() }})</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Last Updated</label>
                                    <div class="form-control-plaintext">
                                        {{ $role->updated_at->format('M d, Y H:i') }}
                                        <small class="text-muted">({{ $role->updated_at->diffForHumans() }})</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions Card -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-key me-2"></i>Permissions
                                <span class="badge bg-primary ms-2">{{ $role->permissions->count() }}</span>
                            </h5>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-secondary" onclick="togglePermissionView('grid')" id="gridViewBtn">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button class="btn btn-outline-secondary active" onclick="togglePermissionView('list')" id="listViewBtn">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        @if($role->permissions->count() > 0)
                            <!-- Search and Filter -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="permissionSearch" 
                                           placeholder="Search permissions..." onkeyup="filterPermissions()">
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" id="groupFilter" onchange="filterPermissions()">
                                        <option value="">All Groups</option>
                                        @foreach($role->permissions->groupBy('group_name') as $groupName => $permissions)
                                            <option value="{{ $groupName }}">{{ $groupName }} ({{ $permissions->count() }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- List View (Default) -->
                            <div id="listView">
                                @foreach($role->permissions->groupBy('group_name') as $groupName => $permissions)
                                    <div class="permission-group mb-4" data-group="{{ $groupName }}">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 text-primary">
                                                <i class="fas fa-folder me-1"></i>{{ $groupName }}
                                            </h6>
                                            <span class="badge bg-light text-dark">{{ $permissions->count() }} permissions</span>
                                        </div>
                                        
                                        <div class="border rounded p-3 bg-light">
                                            <div class="row g-2">
                                                @foreach($permissions as $permission)
                                                    <div class="col-md-6 col-lg-4 permission-item" data-permission="{{ $permission->name }}">
                                                        <div class="d-flex align-items-center p-2 bg-white rounded border">
                                                            <div class="flex-shrink-0 me-2">
                                                                <i class="fas fa-check-circle text-success"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <div class="fw-medium small">{{ $permission->name }}</div>
                                                                @if($permission->description)
                                                                    <div class="text-muted small">{{ $permission->description }}</div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Grid View (Hidden by default) -->
                            <div id="gridView" style="display: none;">
                                <div class="row g-2">
                                    @foreach($role->permissions as $permission)
                                        <div class="col-md-6 col-lg-4 col-xl-3 permission-item" data-permission="{{ $permission->name }}" data-group="{{ $permission->group_name }}">
                                            <div class="card border-success">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-start">
                                                        <div class="flex-shrink-0 me-2">
                                                            <i class="fas fa-shield-alt text-success"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="card-title mb-1 small">{{ $permission->name }}</h6>
                                                            <p class="card-text text-muted small mb-0">{{ $permission->group_name }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-key fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Permissions Assigned</h5>
                                <p class="text-muted">This role has no permissions assigned yet.</p>
                                <a href="{{ route('role.edit', $role->id) }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Assign Permissions
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Quick Actions</h6>
                        <div class="d-grid gap-2">
                            <a href="{{ route('role.edit', $role->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Edit Role
                            </a>
                            {{-- <a href="{{ route('role.permissions', $role->id) }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-key me-1"></i>Manage Permissions
                            </a> --}}
                            <button class="btn btn-outline-info btn-sm" onclick="duplicateRole()">
                                <i class="fas fa-copy me-1"></i>Duplicate Role
                            </button>
                            <button class="btn btn-outline-secondary btn-sm" onclick="exportRoleData()">
                                <i class="fas fa-download me-1"></i>Export Data
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Assigned Users -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">Assigned Users</h6>
                            <span class="badge bg-secondary">{{ $role->users->count() }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($role->users->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($role->users->take(10) as $user)
                                    <div class="list-group-item px-0 py-2 border-0">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-3">
                                                <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('backend/default-avatar.jpg') }}" 
                                                     alt="{{ $user->name }}" class="avatar-sm rounded-circle">
                                                @if($user->is_active)
                                                    <span class="position-absolute translate-middle badge rounded-pill bg-success" style="top: 75%; left: 75%; font-size: 0.5em;">
                                                        <span class="visually-hidden">Active</span>
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-medium small">{{ $user->name }}</div>
                                                <div class="text-muted small">{{ $user->email }}</div>
                                                @if($user->department)
                                                    <div class="text-muted small">{{ $user->department->name ?? '' }}</div>
                                                @endif
                                            </div>
                                            <div class="flex-shrink-0">
                                                @if($user->is_active)
                                                    <span class="badge bg-success-subtle text-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            @if($role->users->count() > 10)
                                <div class="text-center mt-3">
                                    <a href="{{ route('users.index', ['role' => $role->id]) }}" class="btn btn-outline-primary btn-sm">
                                        View All {{ $role->users->count() }} Users
                                    </a>
                                </div>
                            @endif
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-users fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">No users assigned to this role</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Permission Summary -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Permission Summary</h6>
                        @if($role->permissions->count() > 0)
                            @foreach($role->permissions->groupBy('group_name') as $groupName => $permissions)
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small">{{ $groupName }}</span>
                                    <span class="badge bg-light text-dark">{{ $permissions->count() }}</span>
                                </div>
                                <div class="progress mb-2" style="height: 4px;">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: {{ ($permissions->count() / $role->permissions->count()) * 100 }}%"></div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted small mb-0">No permissions to display</p>
                        @endif
                    </div>
                </div>

                <!-- Activity Timeline -->
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Role Timeline</h6>
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-marker bg-success"></div>
                                <div class="timeline-content">
                                    <div class="fw-medium small">Role Created</div>
                                    <div class="text-muted small">{{ $role->created_at->format('M d, Y H:i') }}</div>
                                </div>
                            </div>
                            @if($role->updated_at != $role->created_at)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-warning"></div>
                                    <div class="timeline-content">
                                        <div class="fw-medium small">Last Updated</div>
                                        <div class="text-muted small">{{ $role->updated_at->format('M d, Y H:i') }}</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="row">
            <div class="col-12">
                <div class="text-center mt-3">
                    <a href="{{ route('role.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Roles
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles for the show view */
.avatar-lg {
    width: 4rem;
    height: 4rem;
}

.avatar-sm {
    width: 2rem;
    height: 2rem;
}

.fs-24 {
    font-size: 1.5rem;
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

.permission-item {
    transition: all 0.2s ease;
}

.permission-item:hover {
    transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .avatar-lg {
        width: 3rem;
        height: 3rem;
    }
    
    .fs-24 {
        font-size: 1.25rem;
    }
}
</style>

<script>
// Toggle between list and grid view
function togglePermissionView(view) {
    const listView = document.getElementById('listView');
    const gridView = document.getElementById('gridView');
    const listBtn = document.getElementById('listViewBtn');
    const gridBtn = document.getElementById('gridViewBtn');
    
    if (view === 'grid') {
        listView.style.display = 'none';
        gridView.style.display = 'block';
        listBtn.classList.remove('active');
        gridBtn.classList.add('active');
    } else {
        listView.style.display = 'block';
        gridView.style.display = 'none';
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
    }
}

// Filter permissions
function filterPermissions() {
    const searchTerm = document.getElementById('permissionSearch').value.toLowerCase();
    const groupFilter = document.getElementById('groupFilter').value;
    const permissionItems = document.querySelectorAll('.permission-item');
    const permissionGroups = document.querySelectorAll('.permission-group');
    
    permissionItems.forEach(item => {
        const permissionName = item.getAttribute('data-permission').toLowerCase();
        const permissionGroup = item.getAttribute('data-group');
        
        const matchesSearch = permissionName.includes(searchTerm);
        const matchesGroup = !groupFilter || permissionGroup === groupFilter;
        
        if (matchesSearch && matchesGroup) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show/hide groups in list view
    permissionGroups.forEach(group => {
        const groupName = group.getAttribute('data-group');
        const visibleItems = group.querySelectorAll('.permission-item[style="display: block;"], .permission-item:not([style*="display: none"])');
        
        if ((!groupFilter || groupName === groupFilter) && visibleItems.length > 0) {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    });
}

// Export role data
function exportRoleData() {
    const roleData = {
        name: '{{ $role->name }}',
        guard_name: '{{ $role->guard_name }}',
        permissions: @json($role->permissions->pluck('name')),
        users_count: {{ $role->users->count() }},
        created_at: '{{ $role->created_at->toISOString() }}',
        updated_at: '{{ $role->updated_at->toISOString() }}'
    };
    
    const dataStr = JSON.stringify(roleData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = `role-${roleData.name.toLowerCase().replace(/\s+/g, '-')}-export.json`;
    link.click();
    
    URL.revokeObjectURL(url);
}

// Duplicate role
function duplicateRole() {
    const newRoleName = prompt('Enter name for the new role:', '{{ $role->name }} Copy');
    
    if (newRoleName && newRoleName.trim()) {
        fetch('{{ route("role.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                role: newRoleName.trim(),
                permissions: @json($role->permissions->pluck('name'))
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Role duplicated successfully!');
                window.location.href = '{{ route("role.index") }}';
            } else {
                alert(data.message || 'Error duplicating role');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error duplicating role');
        });
    }
}

// Delete role
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
                alert('Role deleted successfully!');
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

// Print page
function printRole() {
    window.print();
}

// Initialize tooltips if using Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any tooltips or popovers here
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

@endsection