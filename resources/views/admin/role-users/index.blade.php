@extends('backend.layouts.master')

@section('content')
<div class="page-content">
    <div class="container-xxl">
        
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1">Role Assignments</h4>
                        <p class="text-muted mb-0">Manage user role assignments and view assignment history</p>
                    </div>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item active">Role Assignments</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-primary-subtle">
                                    <div class="avatar-title rounded-circle bg-primary">
                                        <i class="fas fa-users text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ $usersWithRoles->total() ?? 0 }}</h5>
                                <p class="mb-0 text-muted">Users with Roles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-warning-subtle">
                                    <div class="avatar-title rounded-circle bg-warning">
                                        <i class="fas fa-user-clock text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ $usersWithoutRoles->count() ?? 0 }}</h5>
                                <p class="mb-0 text-muted">Users without Roles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-success-subtle">
                                    <div class="avatar-title rounded-circle bg-success">
                                        <i class="fas fa-shield-alt text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ $totalRoles ?? 0 }}</h5>
                                <p class="mb-0 text-muted">Available Roles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-sm rounded-circle bg-info-subtle">
                                    <div class="avatar-title rounded-circle bg-info">
                                        <i class="fas fa-chart-line text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ number_format((($usersWithRoles->total() / ($usersWithRoles->total() + $usersWithoutRoles->count())) * 100), 1) }}%</h5>
                                <p class="mb-0 text-muted">Assignment Rate</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        @if($usersWithoutRoles->count() > 0)
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div class="flex-grow-1">
                        <strong>{{ $usersWithoutRoles->count() }} user(s)</strong> don't have roles assigned yet.
                    </div>
                    <a href="{{ route('role-users.create') }}" class="btn btn-warning btn-sm">
                        <i class="fas fa-user-plus me-1"></i>Assign Roles Now
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Main Content Tabs -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="assignmentTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="assigned-tab" data-bs-toggle="tab" 
                                        data-bs-target="#assigned" type="button" role="tab">
                                    <i class="fas fa-users me-1"></i>Users with Roles
                                    <span class="badge bg-primary ms-1">{{ $usersWithRoles->total() }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="unassigned-tab" data-bs-toggle="tab" 
                                        data-bs-target="#unassigned" type="button" role="tab">
                                    <i class="fas fa-user-times me-1"></i>Users without Roles
                                    <span class="badge bg-warning ms-1">{{ $usersWithoutRoles->count() }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="roles-overview-tab" data-bs-toggle="tab" 
                                        data-bs-target="#roles-overview" type="button" role="tab">
                                    <i class="fas fa-shield-alt me-1"></i>Roles Overview
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body">
                        <div class="tab-content" id="assignmentTabsContent">
                            
                            <!-- Users with Roles Tab -->
                            <div class="tab-pane fade show active" id="assigned" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Users with Assigned Roles</h5>
                                    <div class="d-flex gap-2">
                                        <!-- Search and Filters -->
                                        <div class="input-group" style="width: 300px;">
                                            <input type="text" class="form-control" placeholder="Search users..." 
                                                   id="searchAssignedUsers" onkeyup="searchTable('assignedUsersTable')">
                                            <button class="btn btn-outline-secondary" type="button">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                        
                                        <select class="form-select" id="roleFilter" onchange="filterByRole()" style="width: 200px;">
                                            <option value="">All Roles</option>
                                            @if(isset($roles))
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="assignedUsersTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="selectAllAssigned">
                                                    </div>
                                                </th>
                                                <th>User</th>
                                                <th>Role</th>
                                                <th>Permissions</th>
                                                <th>Assigned Date</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($usersWithRoles as $user)
                                                <tr data-role="{{ $user->roles->first()->name ?? '' }}">
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input user-checkbox" type="checkbox" value="{{ $user->id }}">
                                                        </div>
                                                    </td>
                                                    
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0 me-3">
                                                                <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('backend/default-avatar.jpg') }}" 
                                                                     alt="{{ $user->name }}" class="avatar-xs rounded-circle">
                                                                @if($user->is_active)
                                                                    <span class="position-absolute translate-middle badge rounded-pill bg-success" style="top: 75%; left: 75%; font-size: 0.5em;">
                                                                        <span class="visually-hidden">Active</span>
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1">{{ $user->name }}</h6>
                                                                <p class="text-muted mb-0 small">{{ $user->email }}</p>
                                                                @if($user->employee_id)
                                                                    <p class="text-muted mb-0 small">ID: {{ $user->employee_id }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    
                                                    <td>
                                                        @if($user->roles->count() > 0)
                                                            @foreach($user->roles as $role)
                                                                <span class="badge bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}-subtle text-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}">
                                                                    {{ $role->name }}
                                                                </span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">No role</span>
                                                        @endif
                                                    </td>
                                                    
                                                    <td>
                                                        @php
                                                            $totalPermissions = $user->getAllPermissions()->count();
                                                        @endphp
                                                        <div class="text-center">
                                                            <span class="badge bg-info">{{ $totalPermissions }}</span>
                                                            <div class="small text-muted">permissions</div>
                                                        </div>
                                                    </td>
                                                    
                                                    <td class="small">
                                                        @if($user->roles->first())
                                                            <div>{{ $user->roles->first()->pivot->created_at ?? $user->created_at->format('M d, Y') }}</div>
                                                            <div class="text-muted">{{ $user->roles->first()->pivot->created_at ? \Carbon\Carbon::parse($user->roles->first()->pivot->created_at)->diffForHumans() : $user->created_at->diffForHumans() }}</div>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                    
                                                    <td>
                                                        @if($user->is_active)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-danger">Inactive</span>
                                                        @endif
                                                        
                                                        @if($user->last_login_at)
                                                            <div class="small text-muted">Last: {{ $user->last_login_at->diffForHumans() }}</div>
                                                        @else
                                                            <div class="small text-muted">Never logged in</div>
                                                        @endif
                                                    </td>
                                                    
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                                                    data-bs-toggle="dropdown">
                                                                Actions
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li>
                                                                    <a class="dropdown-item" href="javascript:;" onclick="viewUserRoles({{ $user->id }})">
                                                                        <i class="fas fa-eye me-2"></i>View Details
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="{{ route('role-users.edit', $user->id) }}">
                                                                        <i class="fas fa-exchange-alt me-2"></i>Change Role
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-warning" href="javascript:;" onclick="removeUserRole({{ $user->id }})">
                                                                        <i class="fas fa-user-minus me-2"></i>Remove Role
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <div class="py-3">
                                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                            <h5 class="text-muted">No users with roles found</h5>
                                                            <p class="text-muted">Start by assigning roles to users.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Replace the existing pagination section with this -->
@if($usersWithRoles->hasPages())
    <div class="row mt-3">
        <div class="col-sm-12 col-md-5 d-flex align-items-center">
            <div class="dataTables_info text-muted">
                <small>
                    Showing <strong>{{ $usersWithRoles->firstItem() }}</strong> to 
                    <strong>{{ $usersWithRoles->lastItem() }}</strong> of 
                    <strong>{{ $usersWithRoles->total() }}</strong> role assignments
                </small>
            </div>
        </div>
        <div class="col-sm-12 col-md-7">
            <div class="d-flex justify-content-end">
                <nav aria-label="Role assignments pagination">
                    <ul class="pagination pagination-sm mb-0">
                        {{-- Previous Page Link --}}
                        @if ($usersWithRoles->onFirstPage())
                            <li class="page-item disabled">
                                <span class="page-link">
                                    <i class="fas fa-chevron-left"></i>
                                </span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $usersWithRoles->previousPageUrl() }}" rel="prev">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @php
                            $start = max($usersWithRoles->currentPage() - 2, 1);
                            $end = min($start + 4, $usersWithRoles->lastPage());
                            $start = max($end - 4, 1);
                        @endphp

                        @if($start > 1)
                            <li class="page-item">
                                <a class="page-link" href="{{ $usersWithRoles->url(1) }}">1</a>
                            </li>
                            @if($start > 2)
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            @endif
                        @endif

                        @for ($i = $start; $i <= $end; $i++)
                            @if ($i == $usersWithRoles->currentPage())
                                <li class="page-item active">
                                    <span class="page-link">{{ $i }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $usersWithRoles->url($i) }}">{{ $i }}</a>
                                </li>
                            @endif
                        @endfor

                        @if($end < $usersWithRoles->lastPage())
                            @if($end < $usersWithRoles->lastPage() - 1)
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            @endif
                            <li class="page-item">
                                <a class="page-link" href="{{ $usersWithRoles->url($usersWithRoles->lastPage()) }}">{{ $usersWithRoles->lastPage() }}</a>
                            </li>
                        @endif

                        {{-- Next Page Link --}}
                        @if ($usersWithRoles->hasMorePages())
                            <li class="page-item">
                                <a class="page-link" href="{{ $usersWithRoles->nextPageUrl() }}" rel="next">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        @else
                            <li class="page-item disabled">
                                <span class="page-link">
                                    <i class="fas fa-chevron-right"></i>
                                </span>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        </div>
    </div>
@endif
                            </div>
                            
                            <!-- Users without Roles Tab -->
                            <div class="tab-pane fade" id="unassigned" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">Users without Assigned Roles</h5>
                                    <div class="d-flex gap-2">
                                        <input type="text" class="form-control" placeholder="Search users..." 
                                               id="searchUnassignedUsers" onkeyup="searchTable('unassignedUsersTable')" style="width: 300px;">
                                        <a href="{{ route('role-users.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Assign Roles
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="unassignedUsersTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="selectAllUnassigned">
                                                    </div>
                                                </th>
                                                <th>User</th>
                                                <th>Department</th>
                                                <th>Join Date</th>
                                                <th>Last Login</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($usersWithoutRoles as $user)
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input unassigned-checkbox" type="checkbox" value="{{ $user->id }}">
                                                        </div>
                                                    </td>
                                                    
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0 me-3">
                                                                <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('backend/default-avatar.jpg') }}" 
                                                                     alt="{{ $user->name }}" class="avatar-xs rounded-circle">
                                                                <span class="position-absolute translate-middle badge rounded-pill bg-warning" style="top: 75%; left: 75%; font-size: 0.4em;">
                                                                    !
                                                                </span>
                                                            </div>
                                                            <div>
                                                                <h6 class="mb-1">{{ $user->name }}</h6>
                                                                <p class="text-muted mb-0 small">{{ $user->email }}</p>
                                                                @if($user->employee_id)
                                                                    <p class="text-muted mb-0 small">ID: {{ $user->employee_id }}</p>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                    
                                                    <td>
                                                        <div class="small">
                                                            <div>{{ $user->department->name ?? 'No Department' }}</div>
                                                            @if($user->position_title)
                                                                <div class="text-muted">{{ $user->position_title }}</div>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    
                                                    <td class="small">
                                                        <div>{{ $user->created_at->format('M d, Y') }}</div>
                                                        <div class="text-muted">{{ $user->created_at->diffForHumans() }}</div>
                                                    </td>
                                                    
                                                    <td class="small">
                                                        @if($user->last_login_at)
                                                            <div>{{ $user->last_login_at->format('M d, Y') }}</div>
                                                            <div class="text-muted">{{ $user->last_login_at->diffForHumans() }}</div>
                                                        @else
                                                            <span class="text-muted">Never logged in</span>
                                                        @endif
                                                    </td>
                                                    
                                                    <td>
                                                        @if($user->is_active)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-danger">Inactive</span>
                                                        @endif
                                                        <div class="small text-warning mt-1">No role assigned</div>
                                                    </td>
                                                    
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="{{ route('role-users.create', ['user_id' => $user->id]) }}" class="btn btn-primary btn-sm">
                                                                <i class="fas fa-user-plus"></i>
                                                            </a>
                                                            <button class="btn btn-outline-info btn-sm" onclick="quickAssignRole({{ $user->id }})">
                                                                <i class="fas fa-bolt"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center py-4">
                                                        <div class="py-3">
                                                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                                            <h5 class="text-success">Great! All users have roles assigned</h5>
                                                            <p class="text-muted">Every user in the system has been assigned a role.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Roles Overview Tab -->
                            <div class="tab-pane fade" id="roles-overview" role="tabpanel">
                                <h5 class="mb-3">Roles Overview</h5>
                                
                                <div class="row">
                                    @if(isset($roles))
                                        @foreach($roles as $role)
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="card border-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}">
                                                    <div class="card-header bg-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}-subtle">
                                                        <div class="d-flex align-items-center">
                                                            <div class="flex-shrink-0 me-2">
                                                                <i class="fas fa-{{ $role->name === 'Super Admin' ? 'crown' : ($role->name === 'Admin' ? 'user-shield' : 'user') }} text-{{ $role->name === 'Super Admin' ? 'danger' : ($role->name === 'Admin' ? 'warning' : 'primary') }}"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h6 class="card-title mb-0">{{ $role->name }}</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row text-center">
                                                            <div class="col-6">
                                                                <div class="fs-4 fw-bold text-primary">{{ $role->users->count() }}</div>
                                                                <div class="text-muted small">Users</div>
                                                            </div>
                                                            <div class="col-6">
                                                                <div class="fs-4 fw-bold text-success">{{ $role->permissions->count() }}</div>
                                                                <div class="text-muted small">Permissions</div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-3">
                                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                                <span>Users with this role</span>
                                                                <span>{{ number_format(($role->users->count() / max($usersWithRoles->total(), 1)) * 100, 1) }}%</span>
                                                            </div>
                                                            <div class="progress" style="height: 4px;">
                                                                <div class="progress-bar" style="width: {{ ($role->users->count() / max($usersWithRoles->total(), 1)) * 100 }}%"></div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mt-3">
                                                            <a href="{{ route('role.show', $role->id) }}" class="btn btn-outline-primary btn-sm">
                                                                <i class="fas fa-eye me-1"></i>View Details
                                                            </a>
                                                            <button class="btn btn-outline-info btn-sm" onclick="viewRoleUsers({{ $role->id }})">
                                                                <i class="fas fa-users me-1"></i>Users
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions for Users with Roles -->
        <div class="row" id="bulkActionsRow" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <strong id="selectedCount">0</strong> users selected
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-warning btn-sm" onclick="bulkRemoveRoles()">
                                    <i class="fas fa-user-minus me-1"></i>Remove Roles
                                </button>
                                <button class="btn btn-info btn-sm" onclick="bulkChangeRoles()">
                                    <i class="fas fa-exchange-alt me-1"></i>Change Roles
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions for Users without Roles -->
        <div class="row" id="bulkActionsUnassignedRow" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <strong id="selectedUnassignedCount">0</strong> users selected
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-primary btn-sm" onclick="bulkAssignRoles()">
                                    <i class="fas fa-user-plus me-1"></i>Assign Roles
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Assign Role Modal -->
<div class="modal fade" id="quickAssignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Quick Assign Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quickAssignForm">
                    <input type="hidden" id="quick_user_id">
                    <div class="mb-3">
                        <label class="form-label">Select Role</label>
                        <select class="form-select" id="quick_role_id" required>
                            <option value="">Choose a role...</option>
                            @if(isset($roles))
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmQuickAssign()">Assign Role</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles */
.avatar-xs {
    width: 32px;
    height: 32px;
}

.fs-18 {
    font-size: 1.125rem;
}

/* Tab content spacing */
.tab-pane {
    min-height: 400px;
}

/* Table hover effects */
.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

/* Badge spacing */
.badge + .badge {
    margin-left: 0.25rem;
}

/* Loading state */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

/* Responsive table adjustments */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
    }
}
</style>

<script>
// Search functionality
function searchTable(tableId) {
    const input = document.getElementById(tableId === 'assignedUsersTable' ? 'searchAssignedUsers' : 'searchUnassignedUsers');
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Filter by role
function filterByRole() {
    const filter = document.getElementById('roleFilter').value;
    const rows = document.querySelectorAll('#assignedUsersTable tbody tr');

    rows.forEach(row => {
        const roleData = row.getAttribute('data-role');
        if (!filter || roleData === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Bulk selection for assigned users
document.getElementById('selectAllAssigned').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActions();
});

// Bulk selection for unassigned users
document.getElementById('selectAllUnassigned').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.unassigned-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActionsUnassigned();
});

// Listen for individual checkbox changes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('user-checkbox')) {
        updateBulkActions();
    }
    if (e.target.classList.contains('unassigned-checkbox')) {
        updateBulkActionsUnassigned();
    }
});

// Update bulk actions for assigned users
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const bulkActionsRow = document.getElementById('bulkActionsRow');
    const selectedCount = document.getElementById('selectedCount');
    
    if (checkboxes.length > 0) {
        bulkActionsRow.style.display = 'block';
        selectedCount.textContent = checkboxes.length;
    } else {
        bulkActionsRow.style.display = 'none';
    }
}

// Update bulk actions for unassigned users
function updateBulkActionsUnassigned() {
    const checkboxes = document.querySelectorAll('.unassigned-checkbox:checked');
    const bulkActionsRow = document.getElementById('bulkActionsUnassignedRow');
    const selectedCount = document.getElementById('selectedUnassignedCount');
    
    if (checkboxes.length > 0) {
        bulkActionsRow.style.display = 'block';
        selectedCount.textContent = checkboxes.length;
    } else {
        bulkActionsRow.style.display = 'none';
    }
}

// View user roles details
function viewUserRoles(userId) {
    // You can implement a modal or redirect to user details
    fetch(`/users/${userId}/roles`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show modal with user role details
                showUserRolesModal(data.user);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading user details', 'error');
        });
}

// Change user role
function changeUserRole(userId) {
    // Redirect to edit form or show modal
    window.location.href = `/assign-role/${userId}/edit`;
}

// Remove user role
function removeUserRole(userId) {
    if (confirm('Are you sure you want to remove the role from this user?')) {
        fetch(`/assign-role/${userId}`, {
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
                showAlert(data.message || 'Error removing role', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error removing role', 'error');
        });
    }
}

// Quick assign role
function quickAssignRole(userId) {
    document.getElementById('quick_user_id').value = userId;
    new bootstrap.Modal(document.getElementById('quickAssignModal')).show();
}

// Confirm quick assign
function confirmQuickAssign() {
    const userId = document.getElementById('quick_user_id').value;
    const roleId = document.getElementById('quick_role_id').value;
    
    if (!roleId) {
        alert('Please select a role');
        return;
    }
    
    fetch('/assign-role', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            user_id: userId,
            role_id: roleId,
            confirm_assignment: true
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('quickAssignModal')).hide();
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert(data.message || 'Error assigning role', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error assigning role', 'error');
    });
}

// View role users
function viewRoleUsers(roleId) {
    // Filter the assigned users table by role
    document.getElementById('roleFilter').value = document.querySelector(`#roleFilter option[value]`).textContent;
    filterByRole();
    
    // Switch to assigned users tab
    document.getElementById('assigned-tab').click();
}

// Bulk remove roles
function bulkRemoveRoles() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const userIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (userIds.length === 0) {
        showAlert('Please select users first', 'warning');
        return;
    }
    
    if (confirm(`Are you sure you want to remove roles from ${userIds.length} user(s)?`)) {
        fetch('/assign-role/bulk-remove', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ user_ids: userIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
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

// Bulk change roles
function bulkChangeRoles() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const userIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (userIds.length === 0) {
        showAlert('Please select users first', 'warning');
        return;
    }
    
    // Show modal or redirect to bulk change form
    window.location.href = `/assign-role/bulk-change?users=${userIds.join(',')}`;
}

// Bulk assign roles
function bulkAssignRoles() {
    const checkboxes = document.querySelectorAll('.unassigned-checkbox:checked');
    const userIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (userIds.length === 0) {
        showAlert('Please select users first', 'warning');
        return;
    }
    
    // Redirect to bulk assign form
    window.location.href = `/assign-role/bulk-assign?users=${userIds.join(',')}`;
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

// Show user roles modal
function showUserRolesModal(user) {
    // Create and show modal with user role details
    const modalHTML = `
        <div class="modal fade" id="userRolesModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">User Role Details - ${user.name}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>User Information</h6>
                                <p><strong>Name:</strong> ${user.name}</p>
                                <p><strong>Email:</strong> ${user.email}</p>
                                <p><strong>Employee ID:</strong> ${user.employee_id || 'Not set'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6>Role Information</h6>
                                <p><strong>Current Role:</strong> ${user.roles.map(r => r.name).join(', ')}</p>
                                <p><strong>Permissions:</strong> ${user.permissions.length}</p>
                                <p><strong>Assigned Date:</strong> ${user.role_assigned_date || 'Unknown'}</p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h6>Permissions</h6>
                            <div class="d-flex flex-wrap gap-1">
                                ${user.permissions.map(p => `<span class="badge bg-primary">${p.name}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="changeUserRole(${user.id})">Change Role</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.getElementById('userRolesModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to page and show
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    new bootstrap.Modal(document.getElementById('userRolesModal')).show();
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Clear search on tab change
    document.querySelectorAll('#assignmentTabs button[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function() {
            document.getElementById('searchAssignedUsers').value = '';
            document.getElementById('searchUnassignedUsers').value = '';
            document.getElementById('roleFilter').value = '';
            
            // Reset table filters
            searchTable('assignedUsersTable');
            searchTable('unassignedUsersTable');
            filterByRole();
        });
    });
});
</script>

@endsection