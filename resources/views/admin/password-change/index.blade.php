@extends('backend.layouts.master')

@section('content')
<div class="page-content">
    <div class="container-xxl">
        
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Password Reset Management</h4>
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="javascript: void(0);">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0);">User Management</a></li>
                            <li class="breadcrumb-item active">Password Reset</li>
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
                                        <i class="fas fa-key text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ $totalResets ?? 0 }}</h5>
                                <p class="mb-0 text-muted">Total Resets Today</p>
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
                                        <i class="fas fa-clock text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ $pendingResets ?? 0 }}</h5>
                                <p class="mb-0 text-muted">Pending Password Changes</p>
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
                                <div class="avatar-sm rounded-circle bg-danger-subtle">
                                    <div class="avatar-title rounded-circle bg-danger">
                                        <i class="fas fa-lock text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ $lockedAccounts ?? 0 }}</h5>
                                <p class="mb-0 text-muted">Locked Accounts</p>
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
                                        <i class="fas fa-shield-alt text-white fs-18"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="mb-1">{{ $temporaryPasswords ?? 0 }}</h5>
                                <p class="mb-0 text-muted">Temporary Passwords</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Requiring Password Reset -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title">Users Requiring Password Reset</h4>
                            </div>
                            <div class="col-auto">
                                <div class="d-flex gap-2">
                                    <!-- Search -->
                                    <div class="input-group" style="width: 250px;">
                                        <input type="text" class="form-control" placeholder="Search users..." 
                                               id="searchInput" value="{{ request('search') }}">
                                        <button class="btn btn-outline-secondary" type="button" onclick="searchUsers()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Filter -->
                                    <select class="form-select" id="statusFilter" onchange="filterUsers()" style="width: 150px;">
                                        <option value="">All Status</option>
                                        <option value="locked" {{ request('status') == 'locked' ? 'selected' : '' }}>Locked</option>
                                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                        <option value="temporary" {{ request('status') == 'temporary' ? 'selected' : '' }}>Temporary</option>
                                        <option value="force_change" {{ request('status') == 'force_change' ? 'selected' : '' }}>Force Change</option>
                                    </select>
                                    
                                    <button class="btn btn-warning" onclick="initializeBulkPasswordReset()">
                                        <i class="fas fa-users-cog me-1"></i>Bulk Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0" id="usersTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAll">
                                            </div>
                                        </th>
                                        <th>User</th>
                                        <th>Password Status</th>
                                        <th>Last Login</th>
                                        <th>Reset History</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($users ?? [] as $user)
                                        <tr>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input user-checkbox" type="checkbox" value="{{ $user->id }}">
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0 me-3">
                                                        <div class="position-relative">
                                                            <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('backend/default-avatar.jpg') }}" 
                                                                 alt="{{ $user->name }}" class="avatar-sm rounded-circle">
                                                            @if($user->locked_until && $user->locked_until->gt(now()))
                                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.5em;">
                                                                    <i class="fas fa-lock"></i>
                                                                </span>
                                                            @elseif($user->password_change_required)
                                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning" style="font-size: 0.5em;">
                                                                    <i class="fas fa-exclamation"></i>
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-1">
                                                            {{ $user->preferred_name ?: ($user->first_name . ' ' . $user->last_name) ?: $user->name }}
                                                        </h6>
                                                        <p class="text-muted mb-0 small">{{ $user->email }}</p>
                                                        <p class="text-muted mb-0 small">ID: {{ $user->employee ?: 'Not Set' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    @if($user->locked_until && $user->locked_until->gt(now()))
                                                        <span class="badge bg-danger-subtle text-danger">
                                                            <i class="fas fa-lock me-1"></i>Locked
                                                        </span>
                                                        <small class="text-muted">Until {{ $user->locked_until->format('M d, H:i') }}</small>
                                                    @endif
                                                    
                                                    @if($user->password_change_required)
                                                        <span class="badge bg-warning-subtle text-warning">
                                                            <i class="fas fa-exclamation-triangle me-1"></i>Change Required
                                                        </span>
                                                    @endif
                                                    
                                                    @if($user->is_temporary_password ?? false)
                                                        <span class="badge bg-info-subtle text-info">
                                                            <i class="fas fa-clock me-1"></i>Temporary
                                                        </span>
                                                        @if($user->password_expires_at)
                                                            <small class="text-muted">Expires {{ $user->password_expires_at->diffForHumans() }}</small>
                                                        @endif
                                                    @endif
                                                    
                                                    @if($user->password_changed_at)
                                                        <small class="text-muted">
                                                            Changed {{ $user->password_changed_at->diffForHumans() }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </td>
                                            
                                            <td class="small">
                                                @if($user->last_login_at)
                                                    <div class="fw-medium">{{ $user->last_login_at->format('M d, Y') }}</div>
                                                    <div class="text-muted">{{ $user->last_login_at->format('H:i') }}</div>
                                                    <div class="text-muted">{{ $user->last_login_at->diffForHumans() }}</div>
                                                @else
                                                    <span class="text-muted">Never</span>
                                                @endif
                                                
                                                @if($user->password_attempts > 0)
                                                    <div class="mt-1">
                                                        <span class="badge bg-warning-subtle text-warning small">
                                                            {{ $user->password_attempts }} failed attempts
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>
                                            
                                            <td>
                                                @if(isset($user->login_history) && is_array($user->login_history))
                                                    @php
                                                        $resetHistory = collect($user->login_history)->filter(function($entry) {
                                                            return isset($entry['method']) && in_array($entry['method'], ['auto', 'manual', 'temporary']);
                                                        })->take(2);
                                                    @endphp
                                                    
                                                    @if($resetHistory->count() > 0)
                                                        @foreach($resetHistory as $reset)
                                                            <div class="small mb-1">
                                                                <span class="badge bg-{{ $reset['method'] === 'temporary' ? 'info' : ($reset['method'] === 'auto' ? 'primary' : 'secondary') }}-subtle text-{{ $reset['method'] === 'temporary' ? 'info' : ($reset['method'] === 'auto' ? 'primary' : 'secondary') }}">
                                                                    {{ ucfirst($reset['method']) }}
                                                                </span>
                                                                <div class="text-muted small">{{ \Carbon\Carbon::parse($reset['date'])->format('M d, H:i') }}</div>
                                                            </div>
                                                        @endforeach
                                                        
                                                        @if(count($user->login_history) > 2)
                                                            <small class="text-muted">+{{ count($user->login_history) - 2 }} more</small>
                                                        @endif
                                                    @else
                                                        <span class="text-muted small">No resets</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">No history</span>
                                                @endif
                                            </td>
                                            
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" 
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="resetPassword({{ $user->id }})">
                                                                <i class="fas fa-key me-2"></i>Reset Password
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="generateTemporaryPassword({{ $user->id }})">
                                                                <i class="fas fa-clock me-2"></i>Generate Temporary
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        
                                                        @if($user->locked_until && $user->locked_until->gt(now()))
                                                            <li>
                                                                <a class="dropdown-item text-success" href="javascript:;" onclick="unlockAccount({{ $user->id }})">
                                                                    <i class="fas fa-unlock me-2"></i>Unlock Account
                                                                </a>
                                                            </li>
                                                        @else
                                                            <li>
                                                                <a class="dropdown-item text-warning" href="javascript:;" onclick="lockAccount({{ $user->id }})">
                                                                    <i class="fas fa-lock me-2"></i>Lock Account
                                                                </a>
                                                            </li>
                                                        @endif
                                                        
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="forcePasswordChange({{ $user->id }})">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>Force Change
                                                            </a>
                                                        </li>
                                                        
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="clearFailedAttempts({{ $user->id }})">
                                                                <i class="fas fa-eraser me-2"></i>Clear Failed Attempts
                                                            </a>
                                                        </li>
                                                        
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="viewPasswordHistory({{ $user->id }})">
                                                                <i class="fas fa-history me-2"></i>View History
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
                                                    <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">No users requiring password reset</h5>
                                                    <p class="text-muted">All users have secure, up-to-date passwords.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        @if(isset($users) && $users->hasPages())
                            <div class="row mt-3">
                                <div class="col-sm-12 col-md-5">
                                    <div class="dataTables_info">
                                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} entries
                                    </div>
                                </div>
                                <div class="col-sm-12 col-md-7">
                                    <div class="dataTables_paginate paging_simple_numbers float-end">
                                        {{ $users->links() }}
                                    </div>
                                </div>
                            </div>
                        @endif
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
                                <strong id="selectedCount">0</strong> users selected
                            </div>
                            <div class="btn-group">
                                <button class="btn btn-primary btn-sm" onclick="bulkPasswordReset()">
                                    <i class="fas fa-key me-1"></i>Reset Passwords
                                </button>
                                <button class="btn btn-info btn-sm" onclick="bulkGenerateTemporary()">
                                    <i class="fas fa-clock me-1"></i>Generate Temporary
                                </button>
                                <button class="btn btn-success btn-sm" onclick="bulkUnlockAccounts()">
                                    <i class="fas fa-unlock me-1"></i>Unlock Accounts
                                </button>
                                <button class="btn btn-warning btn-sm" onclick="bulkForcePasswordChange()">
                                    <i class="fas fa-exclamation-triangle me-1"></i>Force Password Change
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Password Reset Modal -->
<div class="modal fade" id="passwordResetModal" tabindex="-1" aria-labelledby="passwordResetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="passwordResetModalLabel">
                    <i class="fas fa-key me-2"></i>Reset User Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- User Info Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <img src="" alt="User Avatar" class="avatar-sm rounded-circle me-3" id="userAvatar">
                                    <div>
                                        <h6 class="mb-1" id="userName">Loading...</h6>
                                        <p class="text-muted mb-0" id="userEmail">Loading...</p>
                                        <small class="text-muted" id="userDepartment">Loading...</small>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge bg-info" id="userEmployeeId">ID: Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password Reset Options -->
                <div class="row">
                    <div class="col-12">
                        <ul class="nav nav-tabs mb-3" id="resetTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="auto-tab" data-bs-toggle="tab" data-bs-target="#auto-reset" 
                                        type="button" role="tab" aria-controls="auto-reset" aria-selected="true">
                                    <i class="fas fa-magic me-1"></i>Auto Generate
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-reset" 
                                        type="button" role="tab" aria-controls="manual-reset" aria-selected="false">
                                    <i class="fas fa-edit me-1"></i>Manual Set
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="temporary-tab" data-bs-toggle="tab" data-bs-target="#temporary-reset" 
                                        type="button" role="tab" aria-controls="temporary-reset" aria-selected="false">
                                    <i class="fas fa-clock me-1"></i>Temporary Password
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="resetTabContent">
                            <!-- Auto Generate Tab -->
                            <div class="tab-pane fade show active" id="auto-reset" role="tabpanel" aria-labelledby="auto-tab">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-cogs text-primary me-2"></i>Automatic Password Generation
                                        </h6>
                                        <p class="text-muted small mb-3">Generate a secure password automatically with customizable options.</p>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Password Length</label>
                                                <input type="range" class="form-range" id="passwordLength" min="8" max="32" value="12">
                                                <div class="d-flex justify-content-between small text-muted">
                                                    <span>8</span>
                                                    <span id="lengthDisplay">12</span>
                                                    <span>32</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Password Options</label>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="includeUppercase" checked>
                                                    <label class="form-check-label" for="includeUppercase">Uppercase Letters</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="includeLowercase" checked>
                                                    <label class="form-check-label" for="includeLowercase">Lowercase Letters</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="includeNumbers" checked>
                                                    <label class="form-check-label" for="includeNumbers">Numbers</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="includeSymbols">
                                                    <label class="form-check-label" for="includeSymbols">Special Characters</label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-outline-primary" onclick="generatePassword()">
                                                <i class="fas fa-sync-alt me-1"></i>Generate Password
                                            </button>
                                        </div>
                                        
                                        <div class="mt-3" id="generatedPasswordContainer" style="display: none;">
                                            <label class="form-label">Generated Password</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="generatedPassword" readonly>
                                                <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('generatedPassword')">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('generatedPassword')">
                                                    <i class="fas fa-eye" id="generatedPasswordEye"></i>
                                                </button>
                                            </div>
                                            <div class="password-strength mt-2" id="autoPasswordStrength"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Manual Set Tab -->
                            <div class="tab-pane fade" id="manual-reset" role="tabpanel" aria-labelledby="manual-tab">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-keyboard text-warning me-2"></i>Manual Password Entry
                                        </h6>
                                        <p class="text-muted small mb-3">Set a custom password manually.</p>
                                        
                                        <div class="mb-3">
                                            <label for="manualPassword" class="form-label">New Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="manualPassword" 
                                                       placeholder="Enter new password..." oninput="checkPasswordStrength('manualPassword')">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('manualPassword')">
                                                    <i class="fas fa-eye" id="manualPasswordEye"></i>
                                                </button>
                                            </div>
                                            <div class="password-strength mt-2" id="manualPasswordStrength"></div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirmPassword" 
                                                       placeholder="Confirm new password..." oninput="validatePasswordMatch()">
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('confirmPassword')">
                                                    <i class="fas fa-eye" id="confirmPasswordEye"></i>
                                                </button>
                                            </div>
                                            <div id="passwordMatchMessage" class="form-text"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Temporary Password Tab -->
                            <div class="tab-pane fade" id="temporary-reset" role="tabpanel" aria-labelledby="temporary-tab">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-clock text-info me-2"></i>Temporary Password
                                        </h6>
                                        <p class="text-muted small mb-3">Generate a temporary password that expires after a set time or first login.</p>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label class="form-label">Temporary Password</label>
                                                <div class="input-group mb-3">
                                                    <input type="text" class="form-control" id="temporaryPassword" readonly>
                                                    <button class="btn btn-outline-primary" type="button" onclick="generateTemporaryPassword()">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('temporaryPassword')">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Expiry Options</label>
                                                <select class="form-select" id="temporaryExpiry">
                                                    <option value="first_login">Expires on first login</option>
                                                    <option value="1_hour">Expires in 1 hour</option>
                                                    <option value="6_hours">Expires in 6 hours</option>
                                                    <option value="24_hours">Expires in 24 hours</option>
                                                    <option value="3_days">Expires in 3 days</option>
                                                    <option value="7_days">Expires in 7 days</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Note:</strong> User will be forced to change password on first login with temporary password.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification Options -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-bell me-2"></i>Notification Options
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="sendEmail" checked>
                                            <label class="form-check-label" for="sendEmail">
                                                <i class="fas fa-envelope text-primary me-1"></i>Send email notification
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="sendSMS">
                                            <label class="form-check-label" for="sendSMS">
                                                <i class="fas fa-sms text-success me-1"></i>Send SMS notification
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="forcePasswordChange" checked>
                                            <label class="form-check-label" for="forcePasswordChange">
                                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>Force password change on next login
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="logoutAllSessions" checked>
                                            <label class="form-check-label" for="logoutAllSessions">
                                                <i class="fas fa-sign-out-alt text-danger me-1"></i>Logout from all devices
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <label for="customMessage" class="form-label">Custom Message (Optional)</label>
                                    <textarea class="form-control" id="customMessage" rows="3" 
                                              placeholder="Add a personal message to include in the notification..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="resetPasswordBtn" onclick="resetUserPassword()">
                    <i class="fas fa-key me-1"></i>Reset Password
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Password Reset Modal -->
<div class="modal fade" id="bulkPasswordResetModal" tabindex="-1" aria-labelledby="bulkPasswordResetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="bulkPasswordResetModalLabel">
                    <i class="fas fa-users-cog me-2"></i>Bulk Password Reset
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> You are about to reset passwords for multiple users. This action cannot be undone.
                </div>
                
                <div class="mb-3">
                    <h6>Selected Users (<span id="bulkUserCount">0</span>):</h6>
                    <div id="bulkUserList" class="border rounded p-3 bg-light" style="max-height: 200px; overflow-y: auto;">
                        <!-- Selected users will be listed here -->
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Password Generation</label>
                        <select class="form-select" id="bulkPasswordType">
                            <option value="auto">Auto-generate secure passwords</option>
                            <option value="pattern">Use pattern-based passwords</option>
                            <option value="temporary">Generate temporary passwords</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password Length</label>
                        <select class="form-select" id="bulkPasswordLength">
                            <option value="8">8 characters</option>
                            <option value="10">10 characters</option>
                            <option value="12" selected>12 characters</option>
                            <option value="16">16 characters</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulkSendEmail" checked>
                        <label class="form-check-label" for="bulkSendEmail">
                            Send email notifications to all users
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulkForceChange" checked>
                        <label class="form-check-label" for="bulkForceChange">
                            Force password change on next login
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="bulkLogoutSessions" checked>
                        <label class="form-check-label" for="bulkLogoutSessions">
                            Logout from all devices
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="executeBulkPasswordReset()">
                    <i class="fas fa-users-cog me-1"></i>Reset All Passwords
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Password History Modal -->
<div class="modal fade" id="passwordHistoryModal" tabindex="-1" aria-labelledby="passwordHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="passwordHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>Password Reset History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="passwordHistoryContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Password Reset Successful
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="py-3">
                    <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                    <h5 id="successMessage">Password has been reset successfully!</h5>
                    <p class="text-muted" id="successDetails">The user has been notified via email with their new login credentials.</p>
                    
                    <div class="alert alert-info mt-3" id="passwordDisplay" style="display: none;">
                        <strong>New Password:</strong>
                        <div class="input-group mt-2">
                            <input type="text" class="form-control" id="displayPassword" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('displayPassword')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <small class="text-muted">Make sure to securely share this with the user.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                    <i class="fas fa-check me-1"></i>Done
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.password-strength {
    height: 5px;
    border-radius: 3px;
    transition: all 0.3s ease;
}
.strength-weak { background-color: #dc3545; }
.strength-medium { background-color: #ffc107; }
.strength-strong { background-color: #198754; }
.avatar-sm { width: 32px; height: 32px; }
</style>

<script>
let currentUserId = null;
let selectedUsers = [];

// Search functionality
function searchUsers() {
    const search = document.getElementById('searchInput').value;
    const url = new URL(window.location.href);
    
    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }
    
    window.location.href = url.toString();
}

// Enter key search
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        searchUsers();
    }
});

// Filter functionality
function filterUsers() {
    const status = document.getElementById('statusFilter').value;
    const url = new URL(window.location.href);
    
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    
    window.location.href = url.toString();
}

// Bulk selection
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.user-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActions();
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('user-checkbox')) {
        updateBulkActions();
    }
});

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

// Password reset functions
function resetPassword(userId) {
    showAlert('Loading user details...', 'info');
    
    fetch(`/admin/users/${userId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                initializePasswordReset(userId, data.user);
            } else {
                showAlert('Error loading user details: ' + (data.message || 'Unknown error'), 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading user details. Please try again.', 'error');
        });
}

function initializePasswordReset(userId, userData = null) {
    currentUserId = userId;
    
    if (userData) {
        document.getElementById('userAvatar').src = userData.avatar || '/backend/default-avatar.jpg';
        document.getElementById('userName').textContent = userData.name;
        document.getElementById('userEmail').textContent = userData.email;
        document.getElementById('userDepartment').textContent = userData.department || 'No Department';
        document.getElementById('userEmployeeId').textContent = `ID: ${userData.employee_id || 'Not Set'}`;
    }
    
    generateTemporaryPassword();
    
    const modal = new bootstrap.Modal(document.getElementById('passwordResetModal'));
    modal.show();
}

function generatePassword() {
    const length = parseInt(document.getElementById('passwordLength').value);
    const includeUppercase = document.getElementById('includeUppercase').checked;
    const includeLowercase = document.getElementById('includeLowercase').checked;
    const includeNumbers = document.getElementById('includeNumbers').checked;
    const includeSymbols = document.getElementById('includeSymbols').checked;
    
    let charset = '';
    if (includeUppercase) charset += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if (includeLowercase) charset += 'abcdefghijklmnopqrstuvwxyz';
    if (includeNumbers) charset += '0123456789';
    if (includeSymbols) charset += '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    if (charset === '') {
        showAlert('Please select at least one password option.', 'warning');
        return;
    }
    
    let password = '';
    for (let i = 0; i < length; i++) {
        password += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    
    document.getElementById('generatedPassword').value = password;
    document.getElementById('generatedPasswordContainer').style.display = 'block';
    checkPasswordStrength('generatedPassword');
}

function generateTemporaryPassword() {
    const adjectives = ['Quick', 'Smart', 'Bright', 'Fast', 'Cool', 'Strong', 'Safe', 'Swift'];
    const nouns = ['Cat', 'Dog', 'Bird', 'Fish', 'Star', 'Moon', 'Sun', 'Tree'];
    const numbers = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    
    const adjective = adjectives[Math.floor(Math.random() * adjectives.length)];
    const noun = nouns[Math.floor(Math.random() * nouns.length)];
    
    const tempPassword = `${adjective}${noun}${numbers}`;
    document.getElementById('temporaryPassword').value = tempPassword;
}

function checkPasswordStrength(inputId) {
    const password = document.getElementById(inputId).value;
    const strengthBar = document.getElementById(inputId + 'Strength');
    
    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    strengthBar.className = 'password-strength mt-2 ';
    if (strength <= 2) {
        strengthBar.className += 'strength-weak';
        strengthBar.style.width = '33%';
    } else if (strength <= 4) {
        strengthBar.className += 'strength-medium';
        strengthBar.style.width = '66%';
    } else {
        strengthBar.className += 'strength-strong';
        strengthBar.style.width = '100%';
    }
}

function validatePasswordMatch() {
    const password = document.getElementById('manualPassword').value;
    const confirm = document.getElementById('confirmPassword').value;
    const message = document.getElementById('passwordMatchMessage');
    
    if (confirm === '') {
        message.textContent = '';
        message.className = 'form-text';
    } else if (password === confirm) {
        message.textContent = 'Passwords match!';
        message.className = 'form-text text-success';
    } else {
        message.textContent = 'Passwords do not match.';
        message.className = 'form-text text-danger';
    }
}

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const eye = document.getElementById(inputId + 'Eye');
    
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'fas fa-eye';
    }
}

function copyToClipboard(inputId) {
    const input = document.getElementById(inputId);
    input.select();
    document.execCommand('copy');
    
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check text-success"></i>';
    setTimeout(() => {
        btn.innerHTML = originalHtml;
    }, 1000);
}

function resetUserPassword() {
    const activeTab = document.querySelector('#resetTabs .nav-link.active').id;
    let password = '';
    let method = '';
    let isTemporary = false;
    let expiresAt = null;
    
    if (activeTab === 'auto-tab') {
        password = document.getElementById('generatedPassword').value;
        method = 'auto';
        if (!password) {
            showAlert('Please generate a password first.', 'warning');
            return;
        }
    } else if (activeTab === 'manual-tab') {
        password = document.getElementById('manualPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        method = 'manual';
        
        if (!password) {
            showAlert('Please enter a password.', 'warning');
            return;
        }
        
        if (password !== confirmPassword) {
            showAlert('Passwords do not match.', 'error');
            return;
        }
        
        if (password.length < 8) {
            showAlert('Password must be at least 8 characters long.', 'error');
            return;
        }
    } else if (activeTab === 'temporary-tab') {
        password = document.getElementById('temporaryPassword').value;
        method = 'temporary';
        isTemporary = true;
        
        const expiryOption = document.getElementById('temporaryExpiry').value;
        if (expiryOption !== 'first_login') {
            const hours = {
                '1_hour': 1,
                '6_hours': 6,
                '24_hours': 24,
                '3_days': 72,
                '7_days': 168
            };
            expiresAt = new Date(Date.now() + (hours[expiryOption] * 60 * 60 * 1000));
        }
    }
    
    const resetData = {
        user_id: currentUserId,
        password: password,
        method: method,
        is_temporary: isTemporary,
        expires_at: expiresAt,
        send_email: document.getElementById('sendEmail').checked,
        send_sms: document.getElementById('sendSMS').checked,
        force_password_change: document.getElementById('forcePasswordChange').checked,
        logout_all_sessions: document.getElementById('logoutAllSessions').checked,
        custom_message: document.getElementById('customMessage').value
    };
    
    const resetBtn = document.getElementById('resetPasswordBtn');
    const originalText = resetBtn.innerHTML;
    resetBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Resetting...';
    resetBtn.disabled = true;
    
    fetch('/admin/users/reset-password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(resetData)
    })
    .then(response => response.json())
    .then(data => {
        resetBtn.innerHTML = originalText;
        resetBtn.disabled = false;
        
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('passwordResetModal')).hide();
            showSuccessModal(data.message, password, data.details);
            setTimeout(() => window.location.reload(), 3000);
        } else {
            showAlert(data.message || 'Error resetting password', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        resetBtn.innerHTML = originalText;
        resetBtn.disabled = false;
        showAlert('Error resetting password. Please try again.', 'error');
    });
}

function showSuccessModal(message, password = null, details = null) {
    document.getElementById('successMessage').textContent = message;
    document.getElementById('successDetails').textContent = details || 'The user has been notified of the password reset.';
    
    if (password) {
        document.getElementById('displayPassword').value = password;
        document.getElementById('passwordDisplay').style.display = 'block';
    } else {
        document.getElementById('passwordDisplay').style.display = 'none';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();
}

// Additional action functions
function generateTemporaryPassword(userId) {
    if (confirm('Generate a temporary password for this user?')) {
        // Similar implementation to resetPassword but with temporary settings
        resetPassword(userId);
        // Auto-select temporary tab
        setTimeout(() => {
            document.getElementById('temporary-tab').click();
        }, 500);
    }
}

function unlockAccount(userId) {
    if (confirm('Unlock this user account?')) {
        fetch(`/admin/users/${userId}/unlock`, {
            method: 'POST',
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
                showAlert(data.message || 'Error unlocking account', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error unlocking account', 'error');
        });
    }
}

function lockAccount(userId) {
    if (confirm('Lock this user account?')) {
        fetch(`/admin/users/${userId}/lock`, {
            method: 'POST',
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
                showAlert(data.message || 'Error locking account', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error locking account', 'error');
        });
    }
}

function forcePasswordChange(userId) {
    if (confirm('Force password change for this user on next login?')) {
        fetch(`/admin/users/${userId}/force-password-change`, {
            method: 'POST',
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
                showAlert(data.message || 'Error setting force password change', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error setting force password change', 'error');
        });
    }
}

function clearFailedAttempts(userId) {
    if (confirm('Clear failed login attempts for this user?')) {
        fetch(`/admin/users/${userId}/clear-failed-attempts`, {
            method: 'POST',
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
                showAlert(data.message || 'Error clearing failed attempts', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error clearing failed attempts', 'error');
        });
    }
}

function viewPasswordHistory(userId) {
    fetch(`/admin/users/${userId}/password-history`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('passwordHistoryContent').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('passwordHistoryModal')).show();
            } else {
                showAlert('Error loading password history', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading password history', 'error');
        });
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

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    generateTemporaryPassword();
    
    document.getElementById('passwordLength').addEventListener('input', function() {
        document.getElementById('lengthDisplay').textContent = this.value;
    });
    
    document.getElementById('auto-tab').addEventListener('shown.bs.tab', function() {
        if (!document.getElementById('generatedPassword').value) {
            generatePassword();
        }
    });
});
</script>

@endsection