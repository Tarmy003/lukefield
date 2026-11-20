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

            @if (session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>{{ session('error') }}</div>
            @endif

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

                                @if ($user->isLocked())
                                    <span class="badge bg-danger-subtle text-danger">
                                        <i class="fas fa-lock"></i> Account Locked
                                    </span>
                                @endif

                                @if ($passwordChangeReason)
                                    <span class="badge bg-warning-subtle text-warning">
                                        <i class="fas fa-key"></i> Must set new password
                                    </span>
                                @endif
                            </div>

                            @if ($user->roles->count() > 0)
                                <div class="mb-3">
                                    <h6 class="text-muted mb-2">Roles</h6>
                                    @foreach ($user->roles as $role)
                                        <span class="badge bg-primary-subtle text-primary me-1">{{ $role->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="d-grid gap-2 mt-4">
                                @can('users update')
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                                        <i class="fas fa-edit me-1"></i> Edit User
                                    </a>

                                    @if (! $user->password_changed_at)
                                        <button class="btn btn-info" onclick="userAction({{ $user->id }}, 'send-welcome', 'Email this user a link to set up their account? It expires in {{ config('security.links.welcome_hours', 24) }} hours.')">
                                            <i class="fas fa-envelope me-1"></i> Send account setup link
                                        </button>
                                    @endif

                                    <button class="btn btn-outline-info" onclick="userAction({{ $user->id }}, 'reset-password', 'Email this user a link to choose a new password? It expires in {{ config('security.links.reset_minutes', 60) }} minutes.')">
                                        <i class="fas fa-key me-1"></i> Email password reset link
                                    </button>

                                    @if ($user->isLocked() || $user->failed_login_attempts > 0)
                                        <button class="btn btn-outline-success" onclick="userAction({{ $user->id }}, 'unlock', 'Unlock this account? Do this only after confirming the person locked themselves out.', true)">
                                            <i class="fas fa-unlock me-1"></i> Unlock account
                                        </button>
                                    @endif

                                    @if (! $user->must_change_password && $user->id !== auth()->id())
                                        <button class="btn btn-outline-secondary" onclick="userAction({{ $user->id }}, 'require-password-change', 'Require this user to set a new password before doing anything else?', true)">
                                            <i class="fas fa-redo me-1"></i> Require password change
                                        </button>
                                    @endif

                                    @if ($user->id !== auth()->id())
                                        @if ($user->is_active)
                                            <button class="btn btn-warning" onclick="toggleUserStatus({{ $user->id }}, false)">
                                                <i class="fas fa-pause me-1"></i> Deactivate User
                                            </button>
                                        @else
                                            <button class="btn btn-success" onclick="toggleUserStatus({{ $user->id }}, true)">
                                                <i class="fas fa-play me-1"></i> Activate User
                                            </button>
                                        @endif
                                    @endif
                                @endcan

                                @can('users delete')
                                    @if ($user->id !== auth()->id())
                                        <button class="btn btn-danger" onclick="deleteUser({{ $user->id }})">
                                            <i class="fas fa-trash me-1"></i> Delete User
                                        </button>
                                    @endif
                                @endcan

                                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details & Activities -->
                <div class="col-lg-8">
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
                                    <p class="fw-medium mb-0">{{ $user->name }}</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Email</label>
                                    <p class="fw-medium mb-0">{{ $user->email }}</p>
                                </div>
                                @if ($user->phone)
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Phone</label>
                                        <p class="fw-medium mb-0">{{ $user->phone }}</p>
                                    </div>
                                @endif
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Account Created</label>
                                    <p class="fw-medium mb-0">{{ $user->created_at->timezone('Africa/Lagos')->format('M d, Y H:i') }}</p>
                                    <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Last Updated</label>
                                    <p class="fw-medium mb-0">{{ $user->updated_at->timezone('Africa/Lagos')->format('M d, Y H:i') }}</p>
                                    <small class="text-muted">{{ $user->updated_at->diffForHumans() }}</small>
                                </div>
                                @if ($user->email_verified_at)
                                    <div class="col-md-6 mb-3">
                                        <label class="text-muted small">Email Verified</label>
                                        <p class="fw-medium mb-0">{{ $user->email_verified_at->timezone('Africa/Lagos')->format('M d, Y H:i') }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Password and sign-in security -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-shield-alt me-2"></i>Password and Sign-in Security
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Password last changed</label>
                                    <p class="fw-medium mb-0">
                                        {{ $user->password_changed_at ? $user->password_changed_at->timezone('Africa/Lagos')->format('M d, Y H:i') : 'Not yet set by the user' }}
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Password expires</label>
                                    <p class="fw-medium mb-0">
                                        @if ($passwordExpiresAt)
                                            {{ $passwordExpiresAt->timezone('Africa/Lagos')->format('M d, Y') }}
                                            @if ($passwordExpiresAt->isPast())
                                                <span class="badge bg-danger-subtle text-danger">Expired</span>
                                            @endif
                                        @else
                                            <span class="text-muted">When the user sets a password</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Password link</label>
                                    <p class="fw-medium mb-0">
                                        @if ($pendingLink)
                                            {{ $pendingLink->purpose === 'welcome' ? 'Setup' : 'Reset' }} link sent,
                                            valid until {{ $pendingLink->expires_at->timezone('Africa/Lagos')->format('M d, H:i') }}
                                        @else
                                            <span class="text-muted">None outstanding</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Sign-in code by email</label>
                                    <p class="fw-medium mb-0 text-success"><i class="fas fa-check-circle"></i> Always required</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Last sign-in</label>
                                    @if ($user->last_login_at)
                                        <p class="fw-medium mb-0">{{ $user->last_login_at->timezone('Africa/Lagos')->format('M d, Y H:i') }}</p>
                                        <small class="text-muted">{{ $user->last_login_at->diffForHumans() }}{{ $user->last_login_ip ? ' from '.$user->last_login_ip : '' }}</small>
                                    @else
                                        <p class="text-muted mb-0">Never signed in</p>
                                    @endif
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="text-muted small">Failed attempts since last success</label>
                                    <p class="fw-medium mb-0 {{ $user->failed_login_attempts > 0 ? 'text-danger' : '' }}">
                                        {{ $user->failed_login_attempts }}
                                        @if ($user->isLocked())
                                            <br><small>Locked until {{ $user->locked_until->timezone('Africa/Lagos')->format('H:i') }} ({{ $user->locked_until->diffForHumans() }})</small>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sign-in History -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-history me-2"></i>Recent Sign-ins
                            </h5>
                            @can('system audit')
                                <a href="{{ route('audit.logins', ['user_id' => $user->id]) }}" class="small">Full history</a>
                            @endcan
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">Signed in</th>
                                            <th>Signed out</th>
                                            <th>IP Address</th>
                                            <th>Browser</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($user->loginHistories as $history)
                                            <tr>
                                                <td class="ps-3">
                                                    {{ $history->logged_in_at?->timezone('Africa/Lagos')->format('M d, Y H:i') }}
                                                    <br><small class="text-muted">{{ $history->logged_in_at?->diffForHumans() }}</small>
                                                </td>
                                                <td>{{ $history->logged_out_at ? $history->logged_out_at->timezone('Africa/Lagos')->format('M d, H:i') : '-' }}</td>
                                                <td>{{ $history->ip_address }}</td>
                                                <td>{{ $history->browser ?: '-' }} on {{ $history->platform ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-muted py-3">No sign-ins recorded.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Log -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2"></i>Recent Activity (by or about this user)
                            </h5>
                            @can('system audit')
                                <a href="{{ route('audit.activity', ['user_id' => $user->id]) }}" class="small">Full audit log</a>
                            @endcan
                        </div>
                        <div class="card-body">
                            @forelse ($activities as $activity)
                                <div class="d-flex mb-3 pb-3 border-bottom">
                                    <div class="flex-grow-1">
                                        <p class="mb-1">
                                            {{ $activity->description }}
                                            @if ($activity->causer && $activity->causer->id !== $user->id)
                                                <span class="text-muted small">by {{ $activity->causer->name }}</span>
                                            @endif
                                        </p>
                                        <small class="text-muted">
                                            {{ $activity->created_at->timezone('Africa/Lagos')->format('M d, Y H:i') }} -
                                            {{ $activity->created_at->diffForHumans() }}
                                            @if ($activity->properties['ip'] ?? null)
                                                - IP {{ $activity->properties['ip'] }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No activity recorded.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function jsonPost(url, body) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: body ? JSON.stringify(body) : null
            }).then(response => response.json());
        }

        function userAction(userId, action, question, reload) {
            if (!confirm(question)) {
                return;
            }
            jsonPost(`/users/${userId}/${action}`)
                .then(data => {
                    showAlert(data.message || 'Something went wrong.', data.success ? 'success' : 'error');
                    if (data.success && reload) {
                        setTimeout(() => window.location.reload(), 1200);
                    }
                })
                .catch(() => showAlert('Something went wrong.', 'error'));
        }

        function toggleUserStatus(userId, activate) {
            const message = activate ? 'activate this user' : 'deactivate this user and sign them out';
            if (!confirm(`Are you sure you want to ${message}?`)) {
                return;
            }
            jsonPost(`/users/${userId}/toggle-status`, { is_active: activate })
                .then(data => {
                    showAlert(data.message || 'Error updating user', data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => window.location.reload(), 1000);
                    }
                })
                .catch(() => showAlert('Error updating user', 'error'));
        }

        function deleteUser(userId) {
            if (!confirm('Are you sure you want to delete this user? They will be signed out and lose access.')) {
                return;
            }
            fetch(`/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
                .then(response => response.json())
                .then(data => {
                    showAlert(data.message || 'Error deleting user', data.success ? 'success' : 'error');
                    if (data.success) {
                        setTimeout(() => window.location.href = '{{ route('users.index') }}', 1000);
                    }
                })
                .catch(() => showAlert('Error deleting user', 'error'));
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} alert-dismissible fade show`;
            alertDiv.textContent = message;
            const closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = 'btn-close';
            closeButton.setAttribute('data-bs-dismiss', 'alert');
            alertDiv.appendChild(closeButton);

            const pageContent = document.querySelector('.page-content .container-xxl');
            pageContent.insertBefore(alertDiv, pageContent.firstChild);
            setTimeout(() => alertDiv.remove(), 6000);
        }
    </script>
@endsection
