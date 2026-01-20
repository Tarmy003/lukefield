@extends('backend.layouts.master')

@section('content')
    <style>
        .account-input-wrapper {
            position: relative;
        }
        .account-loader {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
        }
        .spinner-border-sm {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 0.15em;
        }
        .account-feedback {
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        .account-feedback.success {
            color: #28a745;
        }
        .account-feedback.error {
            color: #dc3545;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Generate Statement of Account</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('archive.index') }}">Archive</a></li>
                                <li class="breadcrumb-item active">Generate Statement</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Statement Parameters</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('archive.statement.preview') }}" method="POST" id="statementForm">
                                @csrf
                                
                                <div class="mb-4">
                                    <label class="form-label">Account Number <span class="text-danger">*</span></label>
                                    <div class="account-input-wrapper">
                                        <input type="text" 
                                               name="account_number" 
                                               id="accountNumber" 
                                               class="form-control @error('account_number') is-invalid @enderror" 
                                               placeholder="Enter 17-digit account number"
                                               maxlength="17"
                                               autocomplete="off"
                                               required>
                                        <div class="account-loader">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="accountFeedback" class="account-feedback"></div>
                                    @error('account_number')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Account number must be exactly 17 digits</small>
                                </div>

                                <!-- Account Details Card -->
                                <div id="accountDetails" class="card bg-light mb-4 fade-in" style="display: none;">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="card-title mb-0">
                                                <i class="fas fa-check-circle text-success me-2"></i>Account Details
                                            </h6>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetAccount()">
                                                <i class="fas fa-redo me-1"></i>Change Account
                                            </button>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-2"><strong>Account Number:</strong> <span id="accountNo">-</span></p>
                                                <p class="mb-2"><strong>Customer ID:</strong> <span id="customerId">-</span></p>
                                                <p class="mb-2"><strong>Branch:</strong> <span id="branch">-</span></p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-2"><strong>Product Code:</strong> <span id="productCode">-</span></p>
                                                <p class="mb-2"><strong>Current Balance:</strong> <span id="balance" class="fw-bold">-</span></p>
                                                <p class="mb-2"><strong>Total Transactions:</strong> <span id="transactionCount">-</span></p>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <p class="mb-0"><strong>Available Date Range:</strong> <span id="dateRange" class="text-primary">-</span></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                                        <input type="date" name="start_date" id="startDate" 
                                               class="form-control @error('start_date') is-invalid @enderror" 
                                               required>
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                                        <input type="date" name="end_date" id="endDate" 
                                               class="form-control @error('end_date') is-invalid @enderror" 
                                               required>
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Quick Date Ranges -->
                                <div class="mb-4">
                                    <label class="form-label">Quick Select:</label>
                                    <div class="btn-group flex-wrap" role="group">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange(7)">Last 7 Days</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange(30)">Last 30 Days</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setDateRange(90)">Last 90 Days</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setCurrentMonth()">This Month</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setLastMonth()">Last Month</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setCurrentYear()">This Year</button>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>Preview Statement
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="exportExcel()">
                                        <i class="fas fa-file-excel me-2"></i>Export to Excel
                                    </button>
                                    <button type="button" class="btn btn-danger" onclick="exportPdf()">
                                        <i class="fas fa-file-pdf me-2"></i>Export to PDF
                                    </button>
                                    <a href="{{ route('archive.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
<script>
    let searchTimeout = null;

    document.getElementById('accountNumber').addEventListener('input', function(e) {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');
        
        const accountNumber = this.value;
        const feedback = document.getElementById('accountFeedback');
        const loader = document.querySelector('.account-loader');
        
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Reset feedback
        feedback.textContent = '';
        feedback.className = 'account-feedback';
        
        // Hide account details if input is cleared
        if (accountNumber.length === 0) {
            document.getElementById('accountDetails').style.display = 'none';
            return;
        }
        
        // Check if 17 digits entered
        if (accountNumber.length === 17) {
            // Show loader
            loader.style.display = 'block';
            
            // Debounce search
            searchTimeout = setTimeout(() => {
                searchAccount(accountNumber);
            }, 300);
        }
    });

    function searchAccount(accountNumber) {
        const loader = document.querySelector('.account-loader');
        const feedback = document.getElementById('accountFeedback');
        
        fetch(`/archive/accounts/${accountNumber}/details`)
            .then(response => {
                loader.style.display = 'none';
                if (!response.ok) {
                    throw new Error('Account not found');
                }
                return response.json();
            })
            .then(data => {
                if (data.account) {
                    feedback.innerHTML = '<i class="fas fa-check-circle me-1"></i>Account found successfully!';
                    feedback.className = 'account-feedback success';
                    loadAccountDetails(data);
                } else {
                    throw new Error('Account not found');
                }
            })
            .catch(error => {
                feedback.innerHTML = '<i class="fas fa-times-circle me-1"></i>Account number not found. Please check and try again.';
                feedback.className = 'account-feedback error';
                document.getElementById('accountDetails').style.display = 'none';
                console.error('Error:', error);
            });
    }

    function loadAccountDetails(data) {
        const account = data.account;
        
        // Show account details
        document.getElementById('accountDetails').style.display = 'block';
        document.getElementById('accountNo').textContent = account.old_acct_no;
        document.getElementById('customerId').textContent = account.customerid;
        document.getElementById('branch').textContent = account.branch || 'N/A';
        document.getElementById('productCode').textContent = account.product_code || 'N/A';
        document.getElementById('balance').textContent = 'NGN ' + parseFloat(account.bk_balance_corrected).toLocaleString('en-US', {minimumFractionDigits: 2});
        document.getElementById('transactionCount').textContent = data.transaction_count.toLocaleString();
        
        // Handle date range
        if (data.first_transaction_date && data.last_transaction_date) {
            document.getElementById('dateRange').textContent = 
                formatDate(data.first_transaction_date) + ' to ' + formatDate(data.last_transaction_date);
            
            // Auto-set date range
            document.getElementById('startDate').value = data.first_transaction_date;
            document.getElementById('endDate').value = data.last_transaction_date;
        } else {
            document.getElementById('dateRange').textContent = 'No transactions found';
        }
    }

    function resetAccount() {
        document.getElementById('accountNumber').value = '';
        document.getElementById('accountFeedback').textContent = '';
        document.getElementById('accountFeedback').className = 'account-feedback';
        document.getElementById('accountDetails').style.display = 'none';
        document.getElementById('accountNumber').focus();
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-GB', {day: '2-digit', month: 'short', year: 'numeric'});
    }

    // Date range helpers
    function setDateRange(days) {
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - days);
        
        document.getElementById('startDate').value = startDate.toISOString().split('T')[0];
        document.getElementById('endDate').value = endDate.toISOString().split('T')[0];
    }

    function setCurrentMonth() {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), 1);
        const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
        
        document.getElementById('startDate').value = start.toISOString().split('T')[0];
        document.getElementById('endDate').value = end.toISOString().split('T')[0];
    }

    function setLastMonth() {
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        const end = new Date(now.getFullYear(), now.getMonth(), 0);
        
        document.getElementById('startDate').value = start.toISOString().split('T')[0];
        document.getElementById('endDate').value = end.toISOString().split('T')[0];
    }

    function setCurrentYear() {
        const now = new Date();
        const start = new Date(now.getFullYear(), 0, 1);
        const end = new Date(now.getFullYear(), 11, 31);
        
        document.getElementById('startDate').value = start.toISOString().split('T')[0];
        document.getElementById('endDate').value = end.toISOString().split('T')[0];
    }

    // Export functions
    function exportExcel() {
        const form = document.getElementById('statementForm');
        if (form.checkValidity()) {
            form.action = '{{ route("archive.statement.excel") }}';
            form.submit();
            form.action = '{{ route("archive.statement.preview") }}';
        } else {
            form.reportValidity();
        }
    }

    function exportPdf() {
        const form = document.getElementById('statementForm');
        if (form.checkValidity()) {
            form.action = '{{ route("archive.statement.pdf") }}';
            form.submit();
            form.action = '{{ route("archive.statement.preview") }}';
        } else {
            form.reportValidity();
        }
    }
</script>
@endpush