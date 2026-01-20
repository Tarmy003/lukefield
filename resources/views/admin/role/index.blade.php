@extends('backend.layouts.master')

@section('content')
<div class="page-content">
    <div class="container-xxl">
        
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Roles and Permissions</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Admin</a></li>
                            <li class="breadcrumb-item active">Roles & Permissions</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-primary-subtle">
                                    <div class="avatar-title rounded-circle bg-primary">
                                        <i class="fas fa-user-shield text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ $role->count() }}</h5>
                                <p class="mb-0 text-muted">Total Roles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-success-subtle">
                                    <div class="avatar-title rounded-circle bg-success">
                                        <i class="fas fa-key text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ \Spatie\Permission\Models\Permission::count() }}</h5>
                                <p class="mb-0 text-muted">Total Permissions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Roles Table -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">All Roles</h4>
                            </div>
                            <div class="col-auto">
                                <div class="d-flex gap-2">
                                    <!-- Search -->
                                    <div class="input-group" style="width: 250px;">
                                        <input type="text" class="form-control" placeholder="Search roles..." 
                                               id="searchInput">
                                        <button class="btn btn-outline-secondary" type="button" onclick="searchRoles()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    
                                    <a href="{{ route('role.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus me-1"></i>Add Role
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0" id="rolesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>Role Name</th>
                                        <th>Permissions</th>
                                        <th>Users Count</th>
                                        <th>Created Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($role as $roleItem)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input role-checkbox" type="checkbox" value="{{ $roleItem->id }}">
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="avatar-sm rounded-circle bg-{{ $roleItem->name === 'Super Admin' ? 'danger' : ($roleItem->name === 'Admin' ? 'warning' : 'primary') }}-subtle">
                                                            <div class="avatar-title rounded-circle bg-{{ $roleItem->name === 'Super Admin' ? 'danger' : ($roleItem->name === 'Admin' ? 'warning' : 'primary') }}">
                                                                <i class="fas fa-{{ $roleItem->name === 'Super Admin' ? 'crown' : ($roleItem->name === 'Admin' ? 'user-shield' : 'user') }} text-white"></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1">{{ $roleItem->name }}</h6>
                                                        <p class="text-muted mb-0 small">{{ $roleItem->guard_name }} guard</p>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="permissions-list">
                                                    @if($roleItem->permissions->count() > 0)
                                                        @php
                                                            $permissions = $roleItem->permissions;
                                                            $displayCount = 3;
                                                        @endphp
                                                        
                                                        @foreach($permissions->take($displayCount) as $permission)
                                                            <span class="badge bg-light text-dark me-1 mb-1">{{ $permission->name }}</span>
                                                        @endforeach
                                                        
                                                        @if($permissions->count() > $displayCount)
                                                            <span class="badge bg-info text-white">+{{ $permissions->count() - $displayCount }} more</span>
                                                        @endif
                                                        
                                                        <div class="mt-1">
                                                            <small class="text-muted">{{ $permissions->count() }} permission(s) total</small>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">No permissions assigned</span>
                                                    @endif
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="text-center">
                                                    <span class="badge bg-secondary fs-6">{{ $roleItem->users->count() }}</span>
                                                    <div class="small text-muted mt-1">users</div>
                                                </div>
                                            </td>
                                            
                                            <td class="small">
                                                <div class="fw-medium">{{ $roleItem->created_at->format('M d, Y') }}</div>
                                                <div class="text-muted">{{ $roleItem->created_at->format('H:i') }}</div>
                                                <div class="text-muted">{{ $roleItem->created_at->diffForHumans() }}</div>
                                            </td>
                                            
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('role.show', $roleItem->id) }}" >
                                                                <i class="fas fa-eye me-2"></i>View Details
                                                            </a>
                                                        </li>
                                                        @if($roleItem->name !== 'Super Admin')
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('role.edit', $roleItem->id) }}">
                                                                <i class="fas fa-edit me-2"></i>Edit Role
                                                            </a>
                                                        </li>
                                                        @else
                                                        <li>
                                                            <span class="dropdown-item text-muted">
                                                                <i class="fas fa-lock me-2"></i>Cannot Edit
                                                            </span>
                                                        </li>
                                                        @endif
                                                        {{-- <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="managePermissions({{ $roleItem->id }})">
                                                                <i class="fas fa-key me-2"></i>Manage Permissions
                                                            </a>
                                                        </li> --}}
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="viewUsersWithRole({{ $roleItem->id }})">
                                                                <i class="fas fa-users me-2"></i>View Users ({{ $roleItem->users->count() }})
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        @if($roleItem->name !== 'Super Admin')
                                                            <li>
                                                                <a class="dropdown-item text-danger" href="javascript:;" 
                                                                   onclick="deleteRole({{ $roleItem->id }})">
                                                                    <i class="fas fa-trash me-2"></i>Delete Role
                                                                </a>
                                                            </li>
                                                        @else
                                                            <li>
                                                                <span class="dropdown-item text-muted">
                                                                    <i class="fas fa-lock me-2"></i>Cannot Delete
                                                                </span>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4">
                                                <div class="py-3">
                                                    <i class="fas fa-user-shield fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">No roles found</h5>
                                                    <p class="text-muted">There are no roles in the system yet.</p>
                                                    <a href="{{ route('role.create') }}" class="btn btn-primary">
                                                        <i class="fas fa-plus me-1"></i>Create First Role
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="row" id="bulkActionsRow" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <strong id="selectedCount">0</strong> roles selected
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-danger btn-sm" onclick="bulkDeleteRoles()">
                                    <i class="fas fa-trash me-1"></i>Delete Selected
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Role Details Modal -->
<div class="modal fade" id="roleDetailsModal" tabindex="-1" aria-labelledby="roleDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="roleDetailsModalLabel">Role Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="roleDetailsContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
function searchRoles() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#rolesTable tbody tr');
    
    rows.forEach(row => {
        const roleName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        if (roleName.includes(search)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Enter key search
document.getElementById('searchInput').addEventListener('keyup', function(e) {
    searchRoles();
});

// Bulk selection
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.role-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActions();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('role-checkbox')) {
        updateBulkActions();
    }
});

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.role-checkbox:checked');
    const bulkActionsRow = document.getElementById('bulkActionsRow');
    const selectedCount = document.getElementById('selectedCount');
    
    if (checkboxes.length > 0) {
        bulkActionsRow.style.display = 'block';
        selectedCount.textContent = checkboxes.length;
    } else {
        bulkActionsRow.style.display = 'none';
    }
}

// Role actions
function viewRoleDetails(roleId) {
    fetch(`/role/${roleId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('roleDetailsContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('roleDetailsModal')).show();
            } else {
                showAlert('Error loading role details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading role details', 'error');
        });
}

function managePermissions(roleId) {
    window.location.href = `/role/${roleId}/permissions`;
}

function viewUsersWithRole(roleId) {
    window.location.href = `/admin/users?role=${roleId}`;
}

function deleteRole(roleId) {
    if (confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
        fetch(`/role/${roleId}`, {
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
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert(data.message || 'Error deleting role', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error deleting role', 'error');
        });
    }
}

function bulkDeleteRoles() {
    const checkboxes = document.querySelectorAll('.role-checkbox:checked');
    const roleIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (roleIds.length === 0) {
        showAlert('Please select roles first', 'warning');
        return;
    }
    
    if (confirm('Are you sure you want to delete the selected roles? This action cannot be undone.')) {
        fetch('/role/bulk-delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ role_ids: roleIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert(data.message || 'Error deleting roles', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error deleting roles', 'error');
        });
    }
}

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
</script>

@endsection