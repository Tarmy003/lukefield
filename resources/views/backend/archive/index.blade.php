@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Archive Repository</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Archive Repository</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-4">
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
                                    <h5 class="mb-1">{{ number_format($totalAccounts) }}</h5>
                                    <p class="mb-0 text-muted">Total Accounts</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-success-subtle">
                                        <div class="avatar-title rounded-circle bg-success">
                                            <i class="fas fa-exchange-alt text-white fs-18"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">{{ number_format($totalTransactions) }}</h5>
                                    <p class="mb-0 text-muted">Total Transactions</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-info-subtle">
                                        <div class="avatar-title rounded-circle bg-info">
                                            <i class="fas fa-check-circle text-white fs-18"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="mb-1">{{ number_format($activeAccounts) }}</h5>
                                    <p class="mb-0 text-muted">Active Accounts</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Quick Actions</h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <a href="{{ route('archive.import.accounts') }}" class="btn btn-primary w-100">
                                        <i class="fas fa-file-import me-2"></i>Import Accounts
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('archive.import.transactions') }}" class="btn btn-success w-100">
                                        <i class="fas fa-file-upload me-2"></i>Import Transactions
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="{{ route('archive.statement.form') }}" class="btn btn-info w-100">
                                        <i class="fas fa-file-alt me-2"></i>Generate Statement
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#clearDataModal">
                                        <i class="fas fa-trash me-2"></i>Clear Data
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Accounts -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Recent Accounts</h4>
                        </div>
                        <div class="card-body pt-0">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Number</th>
                                            <th>Customer ID</th>
                                            <th>Branch</th>
                                            <th>Product Code</th>
                                            {{-- <th>Balance</th> --}}
                                            <th>Status</th>
                                            <th>Transactions</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($accounts as $account)
                                            <tr>
                                                <td><strong>{{ $account->old_acct_no }}</strong></td>
                                                <td>{{ $account->customerid }}</td>
                                                <td>{{ $account->branch }}</td>
                                                <td>{{ $account->product_code }}</td>
                                                {{-- <td>NGN {{ number_format($account->bk_balance_corrected, 2) }}</td> --}}
                                                <td>
                                                    <span class="badge bg-{{ $account->account_status == 1 ? 'success' : 'danger' }}">
                                                        {{ $account->status_label }}
                                                    </span>
                                                </td>
                                                <td>{{ number_format($account->transactionHistory->count()) }}</td>
                                                <td>
                                                    <a href="{{ route('archive.statement.form') }}?account={{ $account->old_acct_no }}" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="fas fa-file-alt"></i> Statement
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">No accounts found</h5>
                                                    <p class="text-muted">Import accounts to get started</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if(isset($accounts) && $accounts->hasPages())
                                <div class="row mt-4 border-top pt-3">
                                    <!-- Results Info -->
                                    <div class="col-lg-4 col-md-6 d-flex align-items-center mb-2 mb-lg-0">
                                        <div class="dataTables_info">
                                            <small class="text-muted">
                                                <strong>{{ $accounts->firstItem() ?: 0 }}</strong> -
                                                <strong>{{ $accounts->lastItem() ?: 0 }}</strong> of
                                                <strong>{{ $accounts->total() }}</strong> Accounts
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Per Page Selector & Quick Actions -->
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
                                                    style="width: 60px;" min="1" max="{{ $accounts->lastPage() }}"
                                                    value="{{ $accounts->currentPage() }}"
                                                    onkeypress="jumpToUserPage(event, this.value)" title="Jump to page">
                                                <small class="text-muted">of {{ $accounts->lastPage() }}</small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pagination Navigation -->
                                    <div class="col-lg-4 col-md-12 d-flex justify-content-lg-end justify-content-center">
                                        <nav aria-label="Users pagination">
                                            <ul class="pagination pagination-sm mb-0 shadow-sm">
                                                {{-- First & Previous --}}
                                                @if ($accounts->onFirstPage())
                                                    <li class="page-item disabled">
                                                        <span class="page-link border-0">
                                                            <i class="fas fa-angle-double-left text-muted"></i>
                                                        </span>
                                                    </li>
                                                    <li class="page-item disabled">
                                                        <span class="page-link border-0">
                                                            <i class="fas fa-chevron-left text-muted"></i>
                                                        </span>
                                                    </li>
                                                @else
                                                    <li class="page-item">
                                                        <a class="page-link border-0 text-primary" href="{{ $accounts->url(1) }}"
                                                            title="First page">
                                                            <i class="fas fa-angle-double-left"></i>
                                                        </a>
                                                    </li>
                                                    <li class="page-item">
                                                        <a class="page-link border-0 text-primary"
                                                            href="{{ $accounts->previousPageUrl() }}" rel="prev" title="Previous page">
                                                            <i class="fas fa-chevron-left"></i>
                                                        </a>
                                                    </li>
                                                @endif

                                                {{-- Page Numbers --}}
                                                @php
                                                    $start = max($accounts->currentPage() - 1, 1);
                                                    $end = min($start + 2, $accounts->lastPage());
                                                    $start = max($end - 2, 1);
                                                @endphp

                                                @if($start > 1)
                                                    <li class="page-item">
                                                        <a class="page-link border-0 text-primary" href="{{ $accounts->url(1) }}">1</a>
                                                    </li>
                                                    @if($start > 2)
                                                        <li class="page-item disabled">
                                                            <span class="page-link border-0 bg-light">...</span>
                                                        </li>
                                                    @endif
                                                @endif

                                                @for ($i = $start; $i <= $end; $i++)
                                                    @if ($i == $accounts->currentPage())
                                                        <li class="page-item active">
                                                            <span class="page-link bg-primary border-primary shadow-sm">{{ $i }}</span>
                                                        </li>
                                                    @else
                                                        <li class="page-item">
                                                            <a class="page-link border-0 text-primary"
                                                                href="{{ $accounts->url($i) }}">{{ $i }}</a>
                                                        </li>
                                                    @endif
                                                @endfor

                                                @if($end < $accounts->lastPage())
                                                    @if($end < $accounts->lastPage() - 1)
                                                        <li class="page-item disabled">
                                                            <span class="page-link border-0 bg-light">...</span>
                                                        </li>
                                                    @endif
                                                    <li class="page-item">
                                                        <a class="page-link border-0 text-primary"
                                                            href="{{ $accounts->url($accounts->lastPage()) }}">{{ $accounts->lastPage() }}</a>
                                                    </li>
                                                @endif

                                                {{-- Next & Last --}}
                                                @if ($accounts->hasMorePages())
                                                    <li class="page-item">
                                                        <a class="page-link border-0 text-primary"
                                                            href="{{ $accounts->nextPageUrl() }}" rel="next" title="Next page">
                                                            <i class="fas fa-chevron-right"></i>
                                                        </a>
                                                    </li>
                                                    <li class="page-item">
                                                        <a class="page-link border-0 text-primary"
                                                            href="{{ $accounts->url($accounts->lastPage()) }}" title="Last page">
                                                            <i class="fas fa-angle-double-right"></i>
                                                        </a>
                                                    </li>
                                                @else
                                                    <li class="page-item disabled">
                                                        <span class="page-link border-0">
                                                            <i class="fas fa-chevron-right text-muted"></i>
                                                        </span>
                                                    </li>
                                                    <li class="page-item disabled">
                                                        <span class="page-link border-0">
                                                            <i class="fas fa-angle-double-right text-muted"></i>
                                                        </span>
                                                    </li>
                                                @endif
                                            </ul>
                                        </nav>
                                    </div>
                                </div>

                                {{-- Additional User Stats Row --}}
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex gap-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-user-check text-success me-1"></i>
                                                    {{ $accounts->where('account_status', 1)->count() }} Active
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-times text-danger me-1"></i>
                                                    {{ $accounts->where('account_status', 2)->count() }} Inactive
                                                </small>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-clock text-warning me-1"></i>
                                                    {{ $accounts->where('account_status', 0)->count() }} Pending Setup
                                                </small>
                                            </div>

                                            <div class="text-muted small">
                                                Page {{ $accounts->currentPage() }} of {{ $accounts->lastPage() }}
                                                @if($accounts->hasMorePages())
                                                    • <a href="{{ $accounts->nextPageUrl() }}" class="text-decoration-none">Next →</a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Clear Data Modal -->
    <div class="modal fade" id="clearDataModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clear Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('archive.clear.data') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning!</strong> This action cannot be undone.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">What would you like to clear?</label>
                            <select name="type" class="form-select" required>
                                <option value="">Select option...</option>
                                <option value="accounts">Accounts Only</option>
                                <option value="transactions">Transactions Only</option>
                                <option value="all">All Data (Accounts & Transactions)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Clear Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<style>
.page-link {
    transition: all 0.2s ease;
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
}

.page-link:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #0d6efd;
    transform: translateY(-1px);
}

.page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
    box-shadow: 0 2px 4px rgba(13, 110, 253, 0.25);
}

.pagination-sm .page-link {
    padding: 0.375rem 0.5rem;
    font-size: 0.8125rem;
}

.user-stats-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}
</style>

<script>
// Enhanced pagination functions for users
function changePerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', perPage);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function jumpToUserPage(event, page) {
    if (event.key === 'Enter') {
        const pageNum = parseInt(page);
        if (pageNum >= 1 && pageNum <= {{ $accounts->lastPage() }}) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', pageNum);
            window.location.href = url.toString();
        } else {
            event.target.value = {{ $accounts->currentPage() }};
            showAlert('Invalid page number. Please enter a number between 1 and {{ $accounts->lastPage() }}', 'warning');
        }
    }
}

// Keyboard shortcuts for navigation
document.addEventListener('keydown', function(e) {
    // Only activate shortcuts when not in input fields
    if (!e.target.matches('input, textarea, select')) {
        if (e.key === 'ArrowLeft' && e.ctrlKey) {
            e.preventDefault();
            @if(!$accounts->onFirstPage())
                window.location.href = '{{ $accounts->previousPageUrl() }}';
            @endif
        } else if (e.key === 'ArrowRight' && e.ctrlKey) {
            e.preventDefault();
            @if($accounts->hasMorePages())
                window.location.href = '{{ $accounts->nextPageUrl() }}';
            @endif
        }
    }
});
</script>

@endsection