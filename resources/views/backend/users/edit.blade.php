@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Edit User: {{ $user->name }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active">Edit</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <form action="{{ route('users.update', $user) }}" method="POST" id="userForm">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Main Information -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user me-2"></i>Basic Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Name -->
                                    <div class="col-md-12 mb-3">
                                        <label for="name" class="form-label">
                                            Full Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $user->name) }}" required
                                            placeholder="Enter full name">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Email -->
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            Email Address <span class="text-danger">*</span>
                                        </label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                                            id="email" name="email" value="{{ old('email', $user->email) }}" required
                                            placeholder="user@example.com">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                            id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                                            placeholder="+234 800 000 0000">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Password Change Section -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            Leave password fields blank to keep current password
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">New Password</label>
                                        <div class="input-group">
                                            <input type="password"
                                                class="form-control @error('password') is-invalid @enderror" id="password"
                                                name="password" placeholder="Enter new password (optional)">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye" id="eyeIcon"></i>
                                            </button>
                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <small class="text-muted">Minimum 8 characters</small>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="password_confirmation"
                                            name="password_confirmation" placeholder="Confirm new password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Account Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Account Created</label>
                                        <input type="text" class="form-control" value="{{ $user->created_at->format('M d, Y H:i') }}" disabled>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Updated</label>
                                        <input type="text" class="form-control" value="{{ $user->updated_at->format('M d, Y H:i') }}" disabled>
                                    </div>

                                    @if($user->email_verified_at)
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Email Verified</label>
                                            <input type="text" class="form-control" value="{{ $user->email_verified_at->format('M d, Y H:i') }}" disabled>
                                        </div>
                                    @endif

                                    @if($user->last_login_at)
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Last Login</label>
                                            <input type="text" class="form-control" 
                                                value="{{ $user->last_login_at->format('M d, Y H:i') }} ({{ $user->last_login_at->diffForHumans() }})" 
                                                disabled>
                                        </div>

                                        @if($user->last_login_ip)
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Last Login IP</label>
                                                <input type="text" class="form-control" value="{{ $user->last_login_ip }}" disabled>
                                            </div>
                                        @endif
                                    @endif

                                    @if($user->failed_login_attempts > 0)
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Failed Login Attempts</label>
                                            <input type="text" class="form-control text-danger" value="{{ $user->failed_login_attempts }}" disabled>
                                        </div>
                                    @endif

                                    @if($user->locked_until && $user->locked_until->isFuture())
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Account Locked Until</label>
                                            <input type="text" class="form-control text-danger" 
                                                value="{{ $user->locked_until->format('M d, Y H:i') }} ({{ $user->locked_until->diffForHumans() }})" 
                                                disabled>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Settings & Permissions -->
                    <div class="col-lg-4">
                        <!-- Status Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Active Status -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                            value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            <strong>Active User</strong>
                                            <p class="text-muted small mb-0">User account is active and operational</p>
                                        </label>
                                    </div>
                                </div>

                                <!-- Two Factor -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="two_factor_enabled"
                                            name="two_factor_enabled" value="1"
                                            {{ old('two_factor_enabled', $user->two_factor_enabled) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="two_factor_enabled">
                                            <strong>Enable 2FA</strong>
                                            <p class="text-muted small mb-0">Require two-factor authentication</p>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Roles -->
                        @if(isset($roles) && $roles->count() > 0)
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-user-shield me-2"></i>Roles & Permissions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Assign Roles</label>
                                        @foreach ($roles as $role)
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="roles[]"
                                                    value="{{ $role->id }}" id="role_{{ $role->id }}"
                                                    {{ in_array($role->id, old('roles', $userRoles ?? [])) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="role_{{ $role->id }}">
                                                    {{ $role->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Action Buttons -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update User
                                    </button>
                                    <a href="{{ route('users.show', $user) }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-1"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (password.type === 'password') {
                password.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });

        // Form validation
        document.getElementById('userForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;

            if (password || confirmPassword) {
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }

                if (password.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters long!');
                    return false;
                }
            }
        });
    </script>
@endsection