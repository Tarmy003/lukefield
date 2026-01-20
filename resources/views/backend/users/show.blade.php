@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">User Details: {{ $user->name }}</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                                <li class="breadcrumb-item active">Details</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- User Profile Card -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <img src="{{ asset('backend/default-avatar.jpg') }}" alt="{{ $user->name }}"
                                    class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                            </div>

                            <h4 class="mb-1">{{ $user->name }}</h4>
                            <p class="text-muted mb-3">{{ $user->email }}</p>

                            <!-- Status Badges -->
                            <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">
                                @if ($user->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                @endif

                                @if ($user->email_verified_at)
                                    <span class="badge bg-info-subtle text-info">Email Verified</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Email Not Verified</span>
                                @endif

                                @if ($user->two_factor_enabled)
                                    <span class="badge bg-success-subtle text-success">
                                        <i class="fas fa-shield-alt"></i> 2FA Enabled
                                    </span>
                                @endif

                                @if ($user->isLocked())
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="fas fa-lock"></i> Account Locked
                                    </span>
                                @endif
                            </div>

                            <!-- Roles -->
                            @if ($user->roles->count() > 0)
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Roles</h6>
                                    @foreach ($user->roles as $role)
                                        <span class="badge bg-primary-subtle text-primary me-1">{{ $role->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2 mt-4">
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                                    <i class="fas fa-edit me-1"></i> Edit User
                                </a>

                                @if ($user->is_active)
                                    <button class="btn btn-warning" onclick="toggleUserStatus({{ $user->id }}, false)">
                                        <i class="fas fa-pause me-1"></i> Deactivate User
                                    </button>
                                @else
                                    <button class="btn btn-success" onclick="toggleUserStatus({{ $user->id }}, true)">
                                        <i class="fas fa-play me-1"></i> Activate User
                                    </button>
                                @endif

                                <button class="btn btn-info" onclick="resetPassword({{ $user->id }})">
                                    <i class="fas fa-key me-1"></i> Reset Password
                                </button>

                                @if ($user->id !== auth()->id())
                                    <button class="btn btn-danger" onclick="deleteUser({{ $user->id }})">
                                        <i class="fas fa-trash me-1"></i> Delete User
                                    </button>
                                @endif

                                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details & Activities -->
                <div class="col-lg-8">
                    <!-- User Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>User Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Name</label>
                                    <p class="fw-medium">{{ $user->name }}</p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Email</label>
                                    <p class="fw-medium">{{ $user->email }}</p>
                                </div>

                                @if ($user->phone)
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Phone</label>
                                        <p class="fw-medium">{{ $user->phone }}</p>
                                    </div>
                                @endif

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Account Created</label>
                                    <p class="fw-medium">{{ $user->created_at->format('M d, Y H:i') }}</p>
                                    <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Last Updated</label>
                                    <p class="fw-medium">{{ $user->updated_at->format('M d, Y H:i') }}</p>
                                    <small class="text-muted">{{ $user->updated_at->diffForHumans() }}</small>
                                </div>

                                @if ($user->email_verified_at)
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Email Verified</label>
                                        <p class="fw-medium">{{ $user->email_verified_at->format('M d, Y H:i') }}</p>
                                        <small class="text-muted">{{ $user->email_verified_at->diffForHumans() }}</small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Security Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt me-2"></i>Security Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @if ($user->last_login_at)
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Last Login</label>
                                        <p class="fw-medium">{{ $user->last_login_at->format('M d, Y H:i') }}</p>
                                        <small class="text-muted">{{ $user->last_login_at->diffForHumans() }}</small>
                                    </div>
                                @else
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Last Login</label>
                                        <p class="text-muted">Never logged in</p>
                                    </div>
                                @endif

                                @if ($user->last_login_ip)
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Last Login IP</label>
                                        <p class="fw-medium">{{ $user->last_login_ip }}</p>
                                    </div>
                                @endif

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Two-Factor Authentication</label>
                                    <p class="fw-medium">
                                        @if ($user->two_factor_enabled)
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Enabled</span>
                                        @else
                                            <span class="text-danger"><i class="fas fa-times-circle"></i> Disabled</span>
                                        @endif
                                    </p>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Failed Login Attempts</label>
                                    <p class="fw-medium {{ $user->failed_login_attempts > 0 ? 'text-danger' : '' }}">
                                        {{ $user->failed_login_attempts }}
                                    </p>
                                </div>

                                @if ($user->locked_until)
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Account Locked Until</label>
                                        <p class="fw-medium text-danger">
                                            @if ($user->locked_until->isFuture())
                                                {{ $user->locked_until->format('M d, Y H:i') }}
                                                <br>
                                                <small>{{ $user->locked_until->diffForHumans() }}</small>
                                            @else
                                                <span class="text-success">Account is not locked</span>
                                            @endif
                                        </p>
                                    </div>
                                @endif

                                @if ($user->device_fingerprint)
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Device Fingerprint</label>
                                        <p class="fw-medium small text-muted">{{ substr($user->device_fingerprint, 0, 20) }}...</p>
                                    </div>
                                @endif

                                @if ($user->trusted_ips && count($user->trusted_ips) > 0)
                                    <div class="col-md-12 mb-3">
                                        <label class="text-muted small">Trusted IPs</label>
                                        <div>
                                            @foreach ($user->trusted_ips as $ip)
                                                <span class="badge bg-info-subtle text-info me-1">{{ $ip }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Login History -->
                    @if (isset($user->loginHistories) && $user->loginHistories->count() > 0)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Recent Login History
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>IP Address</th>
                                                <th>Device</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($user->loginHistories as $history)
                                                <tr>
                                                    <td>
                                                        {{ $history->created_at->format('M d, Y H:i') }}
                                                        <br>
                                                        <small
                                                            class="text-muted">{{ $history->created_at->diffForHumans() }}</small>
                                                    </td>
                                                    <td>{{ $history->ip_address }}</td>
                                                    <td>
                                                        <small>{{ $history->user_agent }}</small>
                                                    </td>
                                                    <td>
                                                        @if ($history->successful)
                                                            <span class="badge bg-success-subtle text-success">Success</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger">Failed</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Activity Log -->
                    @if (isset($activities) && $activities->count() > 0)
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-clock me-2"></i>Recent Activity
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-feed">
                                    @foreach ($activities as $activity)
                                        <div class="d-flex mb-3 pb-3 border-bottom">
                                            <div class="flex-shrink-0">
                                                <div class="avatar-sm rounded-circle bg-primary-subtle">
                                                    <div class="avatar-title rounded-circle bg-primary">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <p class="mb-1">{{ $activity->description }}</p>
                                                <small class="text-muted">
                                                    {{ $activity->created_at->format('M d, Y H:i') }} -
                                                    {{ $activity->created_at->diffForHumans() }}
                                                </small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleUserStatus(userId, activate) {
            const action = activate ? 'activate' : 'deactivate';
            const message = activate ? 'activate this user' : 'deactivate this user';

            if (confirm(`Are you sure you want to ${message}?`)) {
                fetch(`/users/${userId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            is_active: activate
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showAlert(data.message || 'Error updating user', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error updating user', 'error');
                    });
            }
        }

        function resetPassword(userId) {
            if (confirm('Reset password and send new credentials to user?')) {
                fetch(`/users/${userId}/reset-password`, {
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
                        } else {
                            showAlert(data.message || 'Error resetting password', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error resetting password', 'error');
                    });
            }
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                fetch(`/users/${userId}`, {
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
                            setTimeout(() => window.location.href = '/users', 1000);
                        } else {
                            showAlert(data.message || 'Error deleting user', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error deleting user', 'error');
                    });
            }
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className =
                `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} alert-dismissible fade show`;
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