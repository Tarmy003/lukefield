{{-- resources/views/backend/system/health.blade.php --}}
@extends('backend.layouts.master')

@php
    $statusMeta = [
        'ok' => ['icon' => 'fa-check-circle', 'class' => 'text-success', 'badge' => 'bg-success-subtle text-success', 'label' => 'OK'],
        'warn' => ['icon' => 'fa-exclamation-triangle', 'class' => 'text-warning', 'badge' => 'bg-warning-subtle text-warning', 'label' => 'Attention'],
        'fail' => ['icon' => 'fa-times-circle', 'class' => 'text-danger', 'badge' => 'bg-danger-subtle text-danger', 'label' => 'Problem'],
        'info' => ['icon' => 'fa-info-circle', 'class' => 'text-secondary', 'badge' => 'bg-secondary-subtle text-secondary', 'label' => 'Info'],
    ];
    $overallText = [
        'ok' => 'All checks passed',
        'warn' => 'Some items need attention',
        'fail' => 'There is a problem that needs fixing now',
    ][$overall];
@endphp

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">System Health</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">System Health</li>
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

            <!-- Summary -->
            <div class="card">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center">
                        <i class="fas {{ $statusMeta[$overall]['icon'] }} {{ $statusMeta[$overall]['class'] }} fs-1 me-3"></i>
                        <div>
                            <h5 class="mb-1">{{ $overallText }}</h5>
                            <p class="text-muted mb-0 small">
                                Checked {{ $checkedAt->timezone('Africa/Lagos')->format('j M Y, H:i:s') }}.
                                Archive figures and the certificate are refreshed every 10 to 60 minutes.
                            </p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('system.health.test-email') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send test email to me
                            </button>
                        </form>
                        <a href="{{ route('system.health', ['refresh' => 1]) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-sync me-1"></i> Re-check everything
                        </a>
                    </div>
                </div>
            </div>

            <!-- Check sections -->
            <div class="row">
                @foreach ($sections as $title => $checks)
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">{{ $title }}</h5>
                            </div>
                            <div class="card-body p-0">
                                <table class="table mb-0 align-middle">
                                    <tbody>
                                        @foreach ($checks as $check)
                                            @php $meta = $statusMeta[$check['status']]; @endphp
                                            <tr>
                                                <td class="ps-3" style="width: 36px;">
                                                    <i class="fas {{ $meta['icon'] }} {{ $meta['class'] }}" title="{{ $meta['label'] }}"></i>
                                                </td>
                                                <td class="small text-muted" style="width: 38%;">{{ $check['label'] }}</td>
                                                <td class="small">
                                                    <span class="fw-medium">{{ $check['value'] }}</span>
                                                    @if ($check['detail'])
                                                        <div class="{{ $check['status'] === 'info' ? 'text-muted' : $meta['class'] }} mt-1" style="font-size: 12px;">{{ $check['detail'] }}</div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row">
                <!-- Locked accounts -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-lock me-2"></i>Locked accounts</h5>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $lockedUsers->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0 small align-middle">
                                <tbody>
                                    @forelse ($lockedUsers as $locked)
                                        <tr>
                                            <td class="ps-3">
                                                <a href="{{ route('users.show', $locked) }}">{{ $locked->name }}</a>
                                                <div class="text-muted">{{ $locked->email }}</div>
                                            </td>
                                            <td>Until {{ $locked->locked_until->timezone('Africa/Lagos')->format('H:i') }}</td>
                                            <td class="text-end pe-3">
                                                @can('users update')
                                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="unlockUser({{ $locked->id }}, this)">
                                                        <i class="fas fa-unlock me-1"></i>Unlock
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-center text-muted py-3">No locked accounts.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer small text-muted">
                            An account locks for 30 minutes after 5 wrong passwords or codes. Unlock only once you have spoken to the person.
                        </div>
                    </div>
                </div>

                <!-- Failed sign-ins by IP -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-user-shield me-2"></i>Failed sign-ins by IP (24 hours)</h5>
                            @can('system audit')
                                <a href="{{ route('audit.logins', ['tab' => 'attempts', 'outcome' => 'failed']) }}" class="small">All attempts</a>
                            @endcan
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0 small">
                                <thead class="table-light">
                                    <tr><th class="ps-3">IP address</th><th>Attempts</th><th>Different emails</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($topFailedIps as $row)
                                        <tr>
                                            <td class="ps-3">
                                                @can('system audit')
                                                    <a href="{{ route('audit.logins', ['tab' => 'attempts', 'ip' => $row->ip_address]) }}">{{ $row->ip_address }}</a>
                                                @else
                                                    {{ $row->ip_address }}
                                                @endcan
                                            </td>
                                            <td>{{ $row->attempts }}</td>
                                            <td>
                                                {{ $row->emails }}
                                                @if ($row->emails >= 3)
                                                    <span class="badge bg-danger-subtle text-danger ms-1">Guessing accounts</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-3">No failed sign-ins in the last 24 hours.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Passwords expiring -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-key me-2"></i>Passwords expiring soon or expired</h5>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $expiringUsers->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0 small">
                                <tbody>
                                    @forelse ($expiringUsers as $expiring)
                                        @php $days = $passwords->daysUntilExpiry($expiring); @endphp
                                        <tr>
                                            <td class="ps-3"><a href="{{ route('users.show', $expiring) }}">{{ $expiring->name }}</a></td>
                                            <td class="text-end pe-3">
                                                @if ($days !== null && $days >= 0)
                                                    <span class="badge bg-warning-subtle text-warning">{{ $days }} day(s) left</span>
                                                @else
                                                    <span class="badge bg-danger-subtle text-danger">Expired: must change at next action</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-center text-muted py-3">None in the next {{ config('security.password.warn_days', 14) }} days.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Dormant accounts -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-user-clock me-2"></i>Dormant accounts</h5>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $dormantUsers->count() }}</span>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0 small">
                                <tbody>
                                    @forelse ($dormantUsers as $dormant)
                                        <tr>
                                            <td class="ps-3"><a href="{{ route('users.show', $dormant) }}">{{ $dormant->name }}</a></td>
                                            <td class="text-end pe-3 text-muted">
                                                {{ $dormant->last_login_at ? 'Last sign-in '.$dormant->last_login_at->diffForHumans() : 'Never signed in' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-center text-muted py-3">Every active account has signed in within {{ config('security.dormant_days', 90) }} days.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer small text-muted">
                            Auditors expect unused access to be removed. Deactivate accounts that are no longer needed.
                        </div>
                    </div>
                </div>

                <!-- Recent errors -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-bug me-2"></i>Recent application errors</h5>
                            <span class="small text-muted">From storage/logs/laravel.log, newest first</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="max-height: 420px;">
                                <table class="table table-sm mb-0 small">
                                    <thead class="table-light">
                                        <tr><th class="ps-3" style="width: 170px;">Time</th><th style="width: 90px;">Level</th><th>Message</th></tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentErrors as $error)
                                            <tr>
                                                <td class="ps-3 text-nowrap">{{ $error['at'] }}</td>
                                                <td><span class="badge bg-danger-subtle text-danger">{{ $error['level'] }}</span></td>
                                                <td style="word-break: break-word;">{{ $error['message'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-3">No recent errors in the log.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function unlockUser(userId, button) {
            if (!confirm('Unlock this account? Do this only after confirming the person locked themselves out.')) {
                return;
            }
            button.disabled = true;
            fetch('{{ url('users') }}/' + userId + '/unlock', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.success) {
                        button.closest('tr').remove();
                        if (window.toastr) { toastr.success(data.message); }
                    } else {
                        button.disabled = false;
                        alert(data.message || 'Could not unlock the account.');
                    }
                })
                .catch(function () {
                    button.disabled = false;
                    alert('Could not unlock the account.');
                });
        }
    </script>
@endpush
