{{-- resources/views/backend/audit/logins.blade.php --}}
@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Sign-in History</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Sign-in History</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-sign-in-alt text-success fs-2 me-3"></i>
                            <div>
                                <h4 class="mb-0">{{ number_format($signInsLast24h) }}</h4>
                                <p class="text-muted mb-0 small">Completed sign-ins in the last 24 hours</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body d-flex align-items-center">
                            <i class="fas fa-ban text-danger fs-2 me-3"></i>
                            <div>
                                <h4 class="mb-0">{{ number_format($failedLast24h) }}</h4>
                                <p class="text-muted mb-0 small">Failed sign-in attempts in the last 24 hours</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'sessions' ? 'active' : '' }}" href="{{ route('audit.logins', array_merge(request()->except(['tab', 'page', 'outcome', 'search']), ['tab' => 'sessions'])) }}">
                        Completed sign-ins
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab === 'attempts' ? 'active' : '' }}" href="{{ route('audit.logins', array_merge(request()->except(['tab', 'page']), ['tab' => 'attempts'])) }}">
                        All attempts (including failed)
                    </a>
                </li>
            </ul>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('audit.logins') }}" class="row g-2 align-items-end">
                        <input type="hidden" name="tab" value="{{ $tab }}">
                        <div class="col-md-3">
                            <label class="form-label small mb-1" for="user_id">User</label>
                            <select name="user_id" id="user_id" class="form-select form-select-sm">
                                <option value="">Everyone</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected((string) $filters['user_id'] === (string) $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($tab === 'attempts')
                            <div class="col-md-2">
                                <label class="form-label small mb-1" for="search">Email entered</label>
                                <input type="text" name="search" id="search" value="{{ $filters['search'] }}" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1" for="outcome">Outcome</label>
                                <select name="outcome" id="outcome" class="form-select form-select-sm">
                                    <option value="">All</option>
                                    <option value="failed" @selected($filters['outcome'] === 'failed')>Failed</option>
                                    <option value="passed" @selected($filters['outcome'] === 'passed')>Passed password step</option>
                                </select>
                            </div>
                        @endif
                        <div class="col-md-2">
                            <label class="form-label small mb-1" for="ip">IP address</label>
                            <input type="text" name="ip" id="ip" value="{{ $filters['ip'] }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small mb-1" for="date_from">From</label>
                            <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label small mb-1" for="date_to">To</label>
                            <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] }}" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-1 d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter"><i class="fas fa-filter"></i></button>
                            <a href="{{ route('audit.logins', ['tab' => $tab]) }}" class="btn btn-sm btn-outline-secondary" title="Clear filters"><i class="fas fa-times"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        {{ number_format(($tab === 'sessions' ? $sessions : $attempts)->total()) }} record(s)
                    </h5>
                    <a href="{{ route('audit.logins.export', request()->query() + ['tab' => $tab]) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-file-csv me-1"></i>Export these to CSV
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        @if ($tab === 'sessions')
                            <table class="table table-sm table-hover mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Signed in (Lagos)</th>
                                        <th>Signed out</th>
                                        <th>User</th>
                                        <th>IP address</th>
                                        <th>Browser and system</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sessions as $login)
                                        <tr>
                                            <td class="ps-3 text-nowrap">{{ $login->logged_in_at?->timezone('Africa/Lagos')->format('j M Y H:i:s') }}</td>
                                            <td class="text-nowrap">
                                                {{ $login->logged_out_at ? $login->logged_out_at->timezone('Africa/Lagos')->format('j M Y H:i:s') : '-' }}
                                            </td>
                                            <td>
                                                @if ($login->user)
                                                    {{ $login->user->name }}
                                                    <div class="text-muted" style="font-size: 11px;">{{ $login->user->email }}</div>
                                                @else
                                                    <span class="text-muted">Deleted user #{{ $login->user_id }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('audit.logins', ['tab' => 'sessions', 'ip' => $login->ip_address]) }}">{{ $login->ip_address }}</a>
                                            </td>
                                            <td>{{ $login->browser ?: '-' }} on {{ $login->platform ?: '-' }} {{ $login->device_type ? '('.$login->device_type.')' : '' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">Nothing matches these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @else
                            <table class="table table-sm table-hover mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">When (Lagos)</th>
                                        <th>Email entered</th>
                                        <th>IP address</th>
                                        <th>Outcome</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attempts as $attempt)
                                        <tr>
                                            <td class="ps-3 text-nowrap">{{ $attempt->created_at?->timezone('Africa/Lagos')->format('j M Y H:i:s') }}</td>
                                            <td>{{ $attempt->email }}</td>
                                            <td>
                                                <a href="{{ route('audit.logins', ['tab' => 'attempts', 'ip' => $attempt->ip_address]) }}">{{ $attempt->ip_address }}</a>
                                            </td>
                                            <td>
                                                @if ($attempt->successful)
                                                    <span class="badge bg-success-subtle text-success">Passed password</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Failed</span>
                                                @endif
                                            </td>
                                            <td>{{ $attempt->failure_reason ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-center text-muted py-4">Nothing matches these filters.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
                @php $paginator = $tab === 'sessions' ? $sessions : $attempts; @endphp
                @if ($paginator->hasPages())
                    <div class="card-footer">{{ $paginator->links() }}</div>
                @endif
            </div>

            <p class="text-muted small">
                "Passed password" means the email and password were right and a sign-in code was sent; the sign-in
                completed only if it also appears under Completed sign-ins.
            </p>
        </div>
    </div>
@endsection
