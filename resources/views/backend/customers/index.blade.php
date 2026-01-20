@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Customer Management</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Customers</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="mb-1">{{ number_format($stats['total']) }}</h4>
                                    <p class="text-muted mb-0">Total Customers</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-primary bg-opacity-10">
                                        <span class="avatar-title rounded-circle text-primary fs-3">
                                            <i class="fas fa-users"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="mb-1">{{ number_format($stats['active']) }}</h4>
                                    <p class="text-muted mb-0">Active Customers</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-success bg-opacity-10">
                                        <span class="avatar-title rounded-circle text-success fs-3">
                                            <i class="fas fa-user-check"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="mb-1">{{ number_format($stats['individual']) }}</h4>
                                    <p class="text-muted mb-0">Individual</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-info bg-opacity-10">
                                        <span class="avatar-title rounded-circle text-info fs-3">
                                            <i class="fas fa-user"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h4 class="mb-1">{{ number_format($stats['pep']) }}</h4>
                                    <p class="text-muted mb-0">PEP Customers</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <div class="avatar-sm rounded-circle bg-warning bg-opacity-10">
                                        <span class="avatar-title rounded-circle text-warning fs-3">
                                            <i class="fas fa-star"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Actions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="card-title mb-0">Customer List</h4>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                                            <i class="fas fa-filter me-1"></i> Filters
                                        </button>
                                        
                                        <a href="{{ route('customers.import.form') }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-upload me-1"></i> Import
                                        </a>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Search Bar -->
                            <div class="row mb-3">
                                <div class="col-md-8">
                                    <form action="{{ route('customers.index') }}" method="GET" id="searchForm">
                                        <div class="input-group">
                                            <input type="text" name="search" class="form-control" 
                                                   placeholder="Search by Customer ID, Name, Email, Phone, BVN..." 
                                                   value="{{ request('search') }}">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-search"></i> Search
                                            </button>
                                            @if(request('search') || request()->except('page'))
                                                <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                                                    <i class="fas fa-times"></i> Clear
                                                </a>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-2">
                                        <span class="text-muted">Show:</span>
                                        <select class="form-select form-select-sm" style="width: auto;" id="perPageSelect">
                                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                            <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                            <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Filters Display -->
                            @if(request()->except('page'))
                                <div class="mb-3">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <span class="text-muted">Active Filters:</span>
                                        @if(request('status'))
                                            <span class="badge bg-primary">
                                                Status: {{ request('status') }}
                                                <a href="{{ route('customers.index', array_merge(request()->except('status'), ['page' => 1])) }}" class="text-white ms-1">×</a>
                                            </span>
                                        @endif
                                        @if(request('customer_type'))
                                            <span class="badge bg-primary">
                                                Type: {{ request('customer_type') }}
                                                <a href="{{ route('customers.index', array_merge(request()->except('customer_type'), ['page' => 1])) }}" class="text-white ms-1">×</a>
                                            </span>
                                        @endif
                                        @if(request('sex'))
                                            <span class="badge bg-primary">
                                                Gender: {{ request('sex') }}
                                                <a href="{{ route('customers.index', array_merge(request()->except('sex'), ['page' => 1])) }}" class="text-white ms-1">×</a>
                                            </span>
                                        @endif
                                        @if(request('pep'))
                                            <span class="badge bg-warning">
                                                PEP: {{ request('pep') }}
                                                <a href="{{ route('customers.index', array_merge(request()->except('pep'), ['page' => 1])) }}" class="text-white ms-1">×</a>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>Customer ID</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Accounts</th>
                                            <th>Date Opened</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($customers as $customer)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" class="form-check-input customer-checkbox" value="{{ $customer->id }}">
                                                </td>
                                                <td>
                                                    <strong>{{ $customer->customer_id }}</strong>
                                                    @if($customer->pep === 'YES')
                                                        <span class="badge bg-warning text-dark ms-1" title="Politically Exposed Person">PEP</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong>{{ $customer->full_name }}</strong>
                                                        @if($customer->bvn)
                                                            <br><small class="text-muted">BVN: {{ $customer->bvn }}</small>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($customer->mobile_phone)
                                                        <div><i class="fas fa-phone text-muted me-1"></i> {{ $customer->mobile_phone }}</div>
                                                    @endif
                                                    @if($customer->email)
                                                        <div><i class="fas fa-envelope text-muted me-1"></i> {{ $customer->email }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $customer->customer_type ?? 'N/A' }}</span>
                                                    @if($customer->sex)
                                                        <br><small class="text-muted">{{ $customer->sex }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($customer->status === 'Active')
                                                        <span class="badge bg-success">Active</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $customer->status }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary">{{ $customer->accounts_count ?? 0 }}</span>
                                                </td>
                                                <td>
                                                    {{ $customer->date_opened ? $customer->date_opened->format('d M Y') : 'N/A' }}
                                                </td>
                                                
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-5">
                                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">No customers found</h5>
                                                    @if(request('search') || request()->except('page'))
                                                        <p>Try adjusting your search or filters</p>
                                                        <a href="{{ route('customers.index') }}" class="btn btn-sm btn-primary">Clear Filters</a>
                                                    @else
                                                        <p>Get started by importing customers or adding a new one</p>
                                                        <div class="d-flex gap-2 justify-content-center mt-3">
                                                            <a href="{{ route('customers.import.form') }}" class="btn btn-sm btn-info">
                                                                <i class="fas fa-upload me-1"></i> Import Customers
                                                            </a>
                                                            <a href="{{ route('customers.create') }}" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-plus me-1"></i> Add Customer
                                                            </a>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            @if($customers->hasPages())
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted">
                                        Showing {{ $customers->firstItem() }} to {{ $customers->lastItem() }} of {{ number_format($customers->total()) }} customers
                                    </div>
                                    <div>
                                        {{ $customers->links() }}
                                    </div>
                                </div>
                            @endif

                            <!-- Bulk Actions -->
                            <div id="bulkActions" class="mt-3" style="display: none;">
                                <div class="alert alert-info d-flex justify-content-between align-items-center">
                                    <span><strong id="selectedCount">0</strong> customers selected</span>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-danger" onclick="bulkDelete()">
                                            <i class="fas fa-trash me-1"></i> Delete Selected
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="bulkExport()">
                                            <i class="fas fa-file-excel me-1"></i> Export Selected
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filterModalLabel">Filter Customers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('customers.index') }}" method="GET">
                    <div class="modal-body">
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Active" {{ request('status') === 'Active' ? 'selected' : '' }}>Active</option>
                                <option value="Inactive" {{ request('status') === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="Closed" {{ request('status') === 'Closed' ? 'selected' : '' }}>Closed</option>
                                <option value="Dormant" {{ request('status') === 'Dormant' ? 'selected' : '' }}>Dormant</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Customer Type</label>
                            <select name="customer_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="Individual" {{ request('customer_type') === 'Individual' ? 'selected' : '' }}>Individual</option>
                                <option value="Corporate" {{ request('customer_type') === 'Corporate' ? 'selected' : '' }}>Corporate</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select name="sex" class="form-select">
                                <option value="">All</option>
                                <option value="MALE" {{ request('sex') === 'MALE' ? 'selected' : '' }}>Male</option>
                                <option value="FEMALE" {{ request('sex') === 'FEMALE' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">PEP Status</label>
                            <select name="pep" class="form-select">
                                <option value="">All</option>
                                <option value="YES" {{ request('pep') === 'YES' ? 'selected' : '' }}>Yes</option>
                                <option value="NO" {{ request('pep') === 'NO' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date Opened Range</label>
                            <div class="row">
                                <div class="col-6">
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" placeholder="From">
                                </div>
                                <div class="col-6">
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" placeholder="To">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-danger">Reset Filters</a>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        @csrf
        @method('DELETE')
    </form>

    <script>
        // Per page selector
        document.getElementById('perPageSelect').addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        });

        // Select all checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.customer-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });

        // Individual checkboxes
        document.querySelectorAll('.customer-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActions);
        });

        function updateBulkActions() {
            const checkedBoxes = document.querySelectorAll('.customer-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            const selectedCount = document.getElementById('selectedCount');
            
            if (checkedBoxes.length > 0) {
                bulkActions.style.display = 'block';
                selectedCount.textContent = checkedBoxes.length;
            } else {
                bulkActions.style.display = 'none';
            }
        }

        // Delete single customer
        function deleteCustomer(id) {
            if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
                const form = document.getElementById('deleteForm');
                form.action = '{{ route("customers.index") }}/' + id;
                form.submit();
            }
        }

        // Bulk delete
        function bulkDelete() {
            const checkedBoxes = document.querySelectorAll('.customer-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            
            if (confirm(`Are you sure you want to delete ${ids.length} customers? This action cannot be undone.`)) {
                // Create form for bulk delete
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '#';
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                
                const idsInput = document.createElement('input');
                idsInput.type = 'hidden';
                idsInput.name = 'ids';
                idsInput.value = JSON.stringify(ids);
                form.appendChild(idsInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Bulk export
        function bulkExport() {
            const checkedBoxes = document.querySelectorAll('.customer-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            
            const url = '#' + ids.join(',');
            window.location.href = url;
        }
    </script>

@endsection