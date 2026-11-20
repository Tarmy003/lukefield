{{-- resources/views/backend/audit/activity.blade.php --}}
@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Audit Log</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Audit Log</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('audit.activity') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small mb-1" for="user_id">User</label>
                            <select name="user_id" id="user_id" class="form-select form-select-sm">
                                <option value="">Everyone</option>
                                @foreach ($users as $u)
                                    <option value="{{ $u->id }}" @selected((string) $filters['user_id'] === (string) $u->id)>{{ $u->name }} ({{ $u->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1" for="search">Action, account number or text</label>
                            <input type="text" name="search" id="search" value="{{ $filters['search'] }}" class="form-control form-control-sm" placeholder="e.g. statement or 0486001...">
                        </div>
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
                        <div class="col-md-2 d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-primary flex-fill"><i class="fas fa-filter me-1"></i>Filter</button>
                            <a href="{{ route('audit.activity') }}" class="btn btn-sm btn-outline-secondary" title="Clear filters"><i class="fas fa-times"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        {{ number_format($activities->total()) }} entr{{ $activities->total() === 1 ? 'y' : 'ies' }}
                    </h5>
                    <a href="{{ route('audit.activity.export', request()->query()) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-file-csv me-1"></i>Export these to CSV
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 small align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3" style="width: 150px;">When (Lagos)</th>
                                    <th style="width: 190px;">User</th>
                                    <th>Action</th>
                                    <th>Account or search</th>
                                    <th style="width: 120px;">IP address</th>
                                    <th style="width: 70px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activities as $activity)
                                    @php
                                        $props = $activity->properties?->toArray() ?? [];
                                        $summary = \App\Http\Controllers\Admin\AuditLogController::summariseInputs($props);
                                    @endphp
                                    <tr>
                                        <td class="ps-3 text-nowrap">{{ $activity->created_at?->timezone('Africa/Lagos')->format('j M Y H:i:s') }}</td>
                                        <td>
                                            @if ($activity->causer)
                                                {{ $activity->causer->name }}
                                                <div class="text-muted" style="font-size: 11px;">{{ $activity->causer->email }}</div>
                                            @elseif ($activity->causer_id)
                                                <span class="text-muted">Deleted user #{{ $activity->causer_id }}</span>
                                            @else
                                                <span class="text-muted">System</span>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $activity->description }}
                                            @php
                                                $onSomeoneElse = $activity->subject_type
                                                    && ! ($activity->subject_type === $activity->causer_type && (int) $activity->subject_id === (int) $activity->causer_id);
                                            @endphp
                                            @if ($onSomeoneElse)
                                                <div class="text-muted" style="font-size: 11px;">
                                                    on {{ $activity->subject instanceof \App\Models\User ? $activity->subject->name : class_basename($activity->subject_type).' #'.$activity->subject_id }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $summary ?: '-' }}</td>
                                        <td>{{ $props['ip'] ?? '-' }}</td>
                                        <td class="pe-3">
                                            @if ($props)
                                                <button type="button" class="btn btn-link btn-sm p-0" data-bs-toggle="collapse" data-bs-target="#props-{{ $activity->id }}">Details</button>
                                            @endif
                                        </td>
                                    </tr>
                                    @if ($props)
                                        <tr class="collapse" id="props-{{ $activity->id }}">
                                            <td colspan="6" class="ps-3 bg-light">
                                                <pre class="mb-0 small" style="white-space: pre-wrap;">{{ json_encode($props, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">Nothing matches these filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($activities->hasPages())
                    <div class="card-footer">{{ $activities->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection
