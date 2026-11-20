@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Create New User</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active">Create</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->has('roles.*'))
                <div class="alert alert-danger">You cannot assign one of the selected roles.</div>
            @endif

            <form action="{{ route('users.store') }}" method="POST" id="userForm">
                @csrf

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
                                            id="name" name="name" value="{{ old('name') }}" required
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
                                            id="email" name="email" value="{{ old('email') }}" required
                                            placeholder="user@example.com">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <!-- Phone -->
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                            id="phone" name="phone" value="{{ old('phone') }}"
                                            placeholder="+234 800 000 0000">
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                </div>

                                <div class="alert alert-info small mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    You do not set a password for the new user. They receive an email with a
                                    single-use link (valid {{ config('security.links.welcome_hours', 24) }} hours) to choose their own,
                                    so only they ever know it.
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
                                            value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">
                                            <strong>Active User</strong>
                                            <p class="text-muted small mb-0">User account is active and operational</p>
                                        </label>
                                    </div>
                                </div>

                                <!-- Setup email -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="send_welcome" value="0">
                                        <input class="form-check-input" type="checkbox" id="send_welcome"
                                            name="send_welcome" value="1"
                                            {{ old('send_welcome', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="send_welcome">
                                            <strong>Email the account setup link now</strong>
                                            <p class="text-muted small mb-0">Untick to send it later from the user's page</p>
                                        </label>
                                    </div>
                                </div>

                                <div class="small text-muted">
                                    <i class="fas fa-shield-alt text-success me-1"></i>
                                    The emailed sign-in code is always required for every user.
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
                                                    {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
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
                                        <i class="fas fa-save me-1"></i> Create User
                                    </button>
                                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


@endsection