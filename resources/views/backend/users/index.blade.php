@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">User Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Users</li>
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
                                    <h5 class="mb-1">{{ $totalUsers ?? 0 }}</h5>
                                    <p class="mb-0 text-muted">Total Users</p>
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
                                            <i class="fas fa-user-check text-white fs-18"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">{{ $activeUsers ?? 0 }}</h5>
                                    <p class="mb-0 text-muted">Active Users</p>
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
                                    <h5 class="mb-1">{{ $pendingUsers ?? 0 }}</h5>
                                    <p class="mb-0 text-muted">Pending Verification</p>
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
                                            <i class="fas fa-user-times text-white fs-18"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">{{ $inactiveUsers ?? 0 }}</h5>
                                    <p class="mb-0 text-muted">Inactive Users</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="card-title">All Users</h4>
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

                                        <!-- Status Filter -->
                                        <select class="form-select" id="statusFilter" onchange="filterUsers()"
                                            style="width: 150px;">
                                            <option value="">All Status</option>
                                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                                                Active</option>
                                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                                Inactive</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                                Pending</option>
                                        </select>

                                        <a href="{{ route('users.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus me-1"></i>Add User
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th>User</th>
                                            <th>Contact</th>
                                            <th>Status</th>
                                            <th>Last Login</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($users ?? [] as $user)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input user-checkbox" type="checkbox"
                                                            value="{{ $user->id }}">
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="position-relative me-3">
                                                            <img src="{{ asset('backend/default-avatar.jpg') }}"
                                                                height="60px;"
                                                                alt="{{ $user->name }}" class="avatar-sm rounded-circle">
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-1">{{ $user->name }}</h6>
                                                            <p class="text-muted mb-0 small">ID: {{ $user->id }}</p>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="small">
                                                        <div class="mb-1">
                                                            <i class="fas fa-envelope text-muted me-1"></i>
                                                            {{ $user->email }}
                                                        </div>
                                                        @if ($user->phone)
                                                            <div class="mb-1">
                                                                <i class="fas fa-phone text-muted me-1"></i>
                                                                {{ $user->phone }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td>
                                                    <div class="d-flex flex-column gap-1">
                                                        @if ($user->is_active)
                                                            <span class="badge bg-success-subtle text-success">Active</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger">Inactive</span>
                                                        @endif

                                                        @if ($user->email_verified_at)
                                                            <span class="badge bg-info-subtle text-info">Verified</span>
                                                        @else
                                                            <span class="badge bg-warning-subtle text-warning">Unverified</span>
                                                        @endif

                                                        @if ($user->two_factor_enabled)
                                                            <span class="badge bg-success-subtle text-success">
                                                                <i class="fas fa-shield-alt"></i> 2FA
                                                            </span>
                                                        @endif

                                                        @if ($user->isLocked())
                                                            <span class="badge bg-danger-subtle text-danger">
                                                                <i class="fas fa-lock"></i> Locked
                                                            </span>
                                                        @endif
                                                    </div>
                                                </td>

                                                <td class="small">
                                                    @if ($user->last_login_at)
                                                        <div class="fw-medium">{{ $user->last_login_at->format('M d, Y') }}</div>
                                                        <div class="text-muted">{{ $user->last_login_at->format('H:i') }}</div>
                                                        <div class="text-muted">{{ $user->last_login_at->diffForHumans() }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted">Never logged in</span>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                                            type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route('users.show', $user) }}">
                                                                    <i class="fas fa-eye me-2"></i>View Details
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item"
                                                                    href="{{ route('users.edit', $user) }}">
                                                                    <i class="fas fa-edit me-2"></i>Edit
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>

                                                            @if (!$user->email_verified_at)
                                                                <li>
                                                                    <a class="dropdown-item text-info" href="#"
                                                                        onclick="sendWelcomeEmail({{ $user->id }})">
                                                                        <i class="fas fa-envelope me-2"></i>Send Welcome Email
                                                                    </a>
                                                                </li>
                                                            @endif

                                                            <li>
                                                                <a class="dropdown-item" href="#"
                                                                    onclick="resetPassword({{ $user->id }})">
                                                                    <i class="fas fa-key me-2"></i>Reset Password
                                                                </a>
                                                            </li>

                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>

                                                            @if ($user->is_active)
                                                                <li>
                                                                    <a class="dropdown-item text-warning" href="#"
                                                                        onclick="toggleUserStatus({{ $user->id }}, false)">
                                                                        <i class="fas fa-pause me-2"></i>Deactivate
                                                                    </a>
                                                                </li>
                                                            @else
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="#"
                                                                        onclick="toggleUserStatus({{ $user->id }}, true)">
                                                                        <i class="fas fa-play me-2"></i>Activate
                                                                    </a>
                                                                </li>
                                                            @endif

                                                            @if ($user->id !== auth()->id())
                                                                <li>
                                                                    <hr class="dropdown-divider">
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger" href="#"
                                                                        onclick="deleteUser({{ $user->id }})">
                                                                        <i class="fas fa-trash me-2"></i>Delete
                                                                    </a>
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
                                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                        <h5 class="text-muted">No users found</h5>
                                                        <p class="text-muted">There are no users matching your criteria.</p>
                                                        <a href="{{ route('users.create') }}" class="btn btn-primary">
                                                            <i class="fas fa-plus me-1"></i>Add First User
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if (isset($users) && $users->hasPages())
                                <div class="row mt-4 border-top pt-3">
                                    <!-- Results Info -->
                                    <div class="col-lg-4 col-md-6 d-flex align-items-center mb-2 mb-lg-0">
                                        <div class="dataTables_info">
                                            <small class="text-muted">
                                                <strong>{{ $users->firstItem() ?: 0 }}</strong> -
                                                <strong>{{ $users->lastItem() ?: 0 }}</strong> of
                                                <strong>{{ $users->total() }}</strong> users
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Per Page Selector -->
                                    <div
                                        class="col-lg-4 col-md-6 d-flex justify-content-center align-items-center mb-2 mb-lg-0">
                                        <div class="d-flex align-items-center">
                                            <label class="form-label mb-0 me-2 small text-muted">Show:</label>
                                            <select class="form-select form-select-sm me-2" style="width: 80px;"
                                                onchange="changePerPage(this.value)">
                                                <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10
                                                </option>
                                                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15
                                                </option>
                                                <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25
                                                </option>
                                                <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50
                                                </option>
                                                <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100
                                                </option>
                                            </select>

                                            <!-- Jump to Page -->
                                            <div class="d-flex align-items-center ms-3">
                                                <label class="form-label mb-0 me-2 small text-muted">Page:</label>
                                                <input type="number" class="form-control form-control-sm me-2"
                                                    style="width: 60px;" min="1" max="{{ $users->lastPage() }}"
                                                    value="{{ $users->currentPage() }}"
                                                    onkeypress="jumpToUserPage(event, this.value)" title="Jump to page">
                                                <small class="text-muted">of {{ $users->lastPage() }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pagination -->
                                    <div class="col-lg-4 col-md-12 d-flex justify-content-lg-end justify-content-center">
                                        {{ $users->links() }}
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
                                    <button class="btn btn-success btn-sm" onclick="bulkAction('activate')">
                                        <i class="fas fa-check me-1"></i>Activate
                                    </button>
                                    <button class="btn btn-warning btn-sm" onclick="bulkAction('deactivate')">
                                        <i class="fas fa-pause me-1"></i>Deactivate
                                    </button>
                                    <button class="btn btn-primary btn-sm" onclick="bulkAction('send_welcome')">
                                        <i class="fas fa-envelope me-1"></i>Send Welcome
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="bulkAction('delete')">
                                        <i class="fas fa-trash me-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pagination functions
        function changePerPage(perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        }

        function jumpToUserPage(event, page) {
            if (event.key === 'Enter') {
                const pageNum = parseInt(page);
                if (pageNum >= 1 && pageNum <= {{ $users->lastPage() }}) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', pageNum);
                    window.location.href = url.toString();
                } else {
                    alert('Invalid page number');
                }
            }
        }

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

        // User actions
        function toggleUserStatus(userId, activate) {
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

        function sendWelcomeEmail(userId) {
            if (confirm('Send welcome email with login credentials to this user?')) {
                fetch(`/users/${userId}/send-welcome`, {
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
                            showAlert(data.message || 'Error sending email', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error sending email', 'error');
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
                            setTimeout(() => window.location.reload(), 1000);
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

        // Bulk actions
        function bulkAction(action) {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            const userIds = Array.from(checkboxes).map(cb => cb.value);

            if (userIds.length === 0) {
                showAlert('Please select users first', 'warning');
                return;
            }

            const messages = {
                activate: 'activate selected users',
                deactivate: 'deactivate selected users',
                send_welcome: 'send welcome emails to selected users',
                delete: 'delete selected users (this cannot be undone)'
            };

            if (confirm(`Are you sure you want to ${messages[action]}?`)) {
                fetch('/users/bulk-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            action: action,
                            user_ids: userIds
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert(data.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            showAlert(data.message || 'Error performing bulk action', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error performing bulk action', 'error');
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