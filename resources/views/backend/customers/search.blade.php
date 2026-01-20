@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Search Panel -->
            <div class="card mb-3">
                <div class="card-header bg-primary bg-opacity-10">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-search me-2"></i>Customer Search
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('customers.search') }}" method="GET" id="searchForm">
                        <div class="row g-3">
                            <!-- Row 1 -->
                            <div class="col-md-4">
                                <label class="form-label">Customer Name:</label>
                                <input type="text" name="customer_name" class="form-control" 
                                       value="{{ request('customer_name') }}" 
                                       placeholder="Enter customer name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Customer ID:</label>
                                <input type="text" name="customer_id" class="form-control" 
                                       value="{{ request('customer_id') }}" 
                                       placeholder="Enter customer ID">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Officer:</label>
                                <select name="account_officer" class="form-select">
                                    <option value="">Select an Officer...</option>
                                    @foreach($accountOfficers as $officer)
                                        <option value="{{ $officer }}" {{ request('account_officer') == $officer ? 'selected' : '' }}>
                                            {{ $officer }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 2 -->
                            <div class="col-md-4">
                                <label class="form-label">Status:</label>
                                <select name="status" class="form-select">
                                    <option value="">--All--</option>
                                    <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                                    <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="Closed" {{ request('status') == 'Closed' ? 'selected' : '' }}>Closed</option>
                                    <option value="Dormant" {{ request('status') == 'Dormant' ? 'selected' : '' }}>Dormant</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account No.:</label>
                                <input type="text" name="account_no" class="form-control" 
                                       value="{{ request('account_no') }}" 
                                       placeholder="Enter account number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Customer Type:</label>
                                <select name="customer_type" class="form-select">
                                    <option value="">Select a Type...</option>
                                    <option value="Individual" {{ request('customer_type') == 'Individual' ? 'selected' : '' }}>Individual</option>
                                    <option value="Corporate" {{ request('customer_type') == 'Corporate' ? 'selected' : '' }}>Corporate</option>
                                </select>
                            </div>

                            <!-- Row 3 -->
                            <div class="col-md-4">
                                <label class="form-label">BVN:</label>
                                <input type="text" name="bvn" class="form-control" 
                                       value="{{ request('bvn') }}" 
                                       placeholder="Enter BVN">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch:</label>
                                <select name="branch" class="form-select">
                                    <option value="">Select a Branch...</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch }}" {{ request('branch') == $branch ? 'selected' : '' }}>
                                            {{ $branch }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sector:</label>
                                <select name="sector" class="form-select">
                                    <option value="">Select a Sector...</option>
                                    @foreach($sectors as $sector)
                                        <option value="{{ $sector }}" {{ request('sector') == $sector ? 'selected' : '' }}>
                                            {{ $sector }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Row 4 -->
                            <div class="col-md-4">
                                <label class="form-label">Phone No.:</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="{{ request('phone') }}" 
                                       placeholder="Enter phone number">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email:</label>
                                <input type="text" name="email" class="form-control" 
                                       value="{{ request('email') }}" 
                                       placeholder="Enter email">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">PEP Status:</label>
                                <select name="pep" class="form-select">
                                    <option value="">--All--</option>
                                    <option value="YES" {{ request('pep') == 'YES' ? 'selected' : '' }}>YES</option>
                                    <option value="NO" {{ request('pep') == 'NO' ? 'selected' : '' }}>NO</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4 justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                            <a href="{{ route('customers.search') }}" class="btn btn-success">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Results Panel -->
            @if(isset($customers))
                <div class="card">
                    <div class="card-header bg-primary bg-opacity-10 d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Customer List
                        </h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-success" onclick="exportToExcel()">
                                <i class="fas fa-file-excel me-1"></i>To Excel
                            </button>
                            <button class="btn btn-sm btn-info" onclick="exportToCsv()">
                                <i class="fas fa-file-csv me-1"></i>To Csv
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Pagination Controls -->
                        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-outline-secondary" {{ $customers->onFirstPage() ? 'disabled' : '' }}
                                        onclick="window.location='{{ $customers->previousPageUrl() }}'">
                                    <i class="fas fa-angle-double-left"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" {{ $customers->onFirstPage() ? 'disabled' : '' }}
                                        onclick="window.location='{{ $customers->previousPageUrl() }}'">
                                    <i class="fas fa-angle-left"></i>
                                </button>
                                <span class="mx-2">
                                    Page <input type="number" value="{{ $customers->currentPage() }}" 
                                               class="form-control form-control-sm d-inline-block" 
                                               style="width: 60px;" min="1" max="{{ $customers->lastPage() }}"
                                               onchange="goToPage(this.value)"> 
                                    of {{ $customers->lastPage() }}
                                </span>
                                <button class="btn btn-sm btn-outline-secondary" {{ !$customers->hasMorePages() ? 'disabled' : '' }}
                                        onclick="window.location='{{ $customers->nextPageUrl() }}'">
                                    <i class="fas fa-angle-right"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" {{ !$customers->hasMorePages() ? 'disabled' : '' }}
                                        onclick="window.location='{{ $customers->url($customers->lastPage()) }}'">
                                    <i class="fas fa-angle-double-right"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                                    <i class="fas fa-sync"></i>
                                </button>
                            </div>
                            <div>
                                <span class="text-muted">
                                    Displaying {{ $customers->firstItem() }} - {{ $customers->lastItem() }} of {{ $customers->total() }}
                                </span>
                            </div>
                        </div>

                        <!-- Results Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 30px;">#</th>
                                        <th>Name</th>
                                        <th>Customer Type</th>
                                        <th>Customer ID</th>
                                        <th>Phone No.</th>
                                        <th>Email</th>
                                        <th>BVN</th>
                                        <th>Branch</th>
                                        <th>Status</th>
                                        <th>Accounts</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customers as $index => $customer)
                                        <tr>
                                            <td>{{ $customers->firstItem() + $index }}</td>
                                            <td>
                                                <strong>{{ $customer->full_name }}</strong>
                                                @if($customer->pep === 'YES')
                                                    <span class="badge bg-warning text-dark ms-1">PEP</span>
                                                @endif
                                            </td>
                                            <td>{{ $customer->customer_type ?? 'N/A' }}</td>
                                            <td>{{ $customer->customer_id }}</td>
                                            <td>{{ $customer->mobile_phone ?? 'N/A' }}</td>
                                            <td>{{ $customer->email ?? 'N/A' }}</td>
                                            <td>{{ $customer->bvn ?? 'N/A' }}</td>
                                            <td>{{ $customer->accounts->first()->branch ?? 'N/A' }}</td>
                                            <td>
                                                @if($customer->status === 'Active')
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $customer->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $customer->accounts_count }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('customers.show', $customer->id) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="text-center py-4">
                                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">No customers found</h5>
                                                <p>Try adjusting your search criteria</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script>
        function goToPage(page) {
            const url = new URL(window.location.href);
            url.searchParams.set('page', page);
            window.location.href = url.toString();
        }

        function exportToExcel() {
            const url = new URL('{{ route("customers.export") }}', window.location.origin);
            const params = new URLSearchParams(window.location.search);
            params.set('format', 'excel');
            window.location.href = url + '?' + params.toString();
        }

        function exportToCsv() {
            const url = new URL('{{ route("customers.export") }}', window.location.origin);
            const params = new URLSearchParams(window.location.search);
            params.set('format', 'csv');
            window.location.href = url + '?' + params.toString();
        }
    </script>

@endsection