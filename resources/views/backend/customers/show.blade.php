@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Customer- {{ strtoupper($customer->full_name) }}</h4>
                        <div class="page-title-right">
                            <a href="{{ route('customers.search') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back to Search
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Basic Info Card -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-2"><strong>Customer Name:</strong> {{ strtoupper($customer->full_name) }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-2"><strong>Customer ID:</strong> {{ $customer->customer_id }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-2"><strong>BVN:</strong> {{ $customer->bvn ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-2"><strong>Type:</strong> {{ $customer->customer_type ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-2"><strong>Email:</strong> {{ $customer->email ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-2"><strong>Phone No.:</strong> {{ $customer->mobile_phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-12">
                            <a href="{{ route('customers.edit', $customer->id) }}" class="text-primary">
                                <i class="fas fa-edit me-1"></i>View/Edit Customer Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debug Info -->
            @if(config('app.debug'))
                <div class="alert alert-info">
                    <strong>Debug Info:</strong><br>
                    Customer ID: {{ $customer->customer_id }}<br>
                    Number of Accounts Found: {{ count($customer->accounts ?? []) }}<br>
                    @php
                        $totalTransactions = 0;
                        if ($customer->accounts) {
                            foreach ($customer->accounts as $account) {
                                $totalTransactions += \App\Models\NominalAccountsTrxPostingHistory::where('old_acct_no', $account->old_acct_no)->count();
                            }
                        }
                    @endphp
                    Total Transactions: {{ $totalTransactions }}
                </div>
            @endif

            <!-- Customer Accounts -->
            @if(isset($customer->accounts) && count($customer->accounts) > 0)
                @foreach($customer->accounts as $accountIndex => $account)
                    @php
                        // Get paginated transactions for this specific account
                        $transactions = \App\Models\NominalAccountsTrxPostingHistory::where('old_acct_no', $account->old_acct_no)
                            ->orderBy('transaction_date', 'desc')
                            ->orderBy('id', 'desc')
                            ->paginate(10, ['*'], 'page_account_' . $accountIndex);
                        
                        $totalTransactionsForAccount = \App\Models\NominalAccountsTrxPostingHistory::where('old_acct_no', $account->old_acct_no)->count();
                    @endphp

                    <div class="card mb-4">
                        <!-- Account Information Header -->
                        <div class="card-header bg-primary bg-opacity-10">
                            <h5 class="card-title mb-0">Account Information - {{ $account->old_acct_no }}</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Account Name:</strong></div>
                                        <div class="col-7">{{ strtoupper($customer->full_name) }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Ledger Balance:</strong></div>
                                        <div class="col-7">₦{{ number_format($account->bk_balance_corrected, 2) }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Available Balance:</strong></div>
                                        <div class="col-7">₦{{ number_format($account->bk_balance_corrected, 2) }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Withdrawable Amount:</strong></div>
                                        <div class="col-7">
                                            ₦{{ number_format($account->bk_balance_corrected, 2) }}
                                            @if($account->lien_status)
                                                <a href="#" class="text-primary">(See Why?)</a>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Account Number:</strong></div>
                                        <div class="col-7">{{ $account->old_acct_no }}</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Product Code:</strong></div>
                                        <div class="col-7">{{ $account->product_code }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Account Officer:</strong></div>
                                        <div class="col-7">{{ $customer->acct_officer ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Introducer Name:</strong></div>
                                        <div class="col-7">{{ $customer->next_of_kin ?? 'N/A' }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Account Status:</strong></div>
                                        <div class="col-7">
                                            @if($account->account_status == 1)
                                                <span class="badge bg-success">Active</span>
                                            @elseif($account->account_status == 2)
                                                <span class="badge bg-secondary">Closed</span>
                                            @elseif($account->account_status == 3)
                                                <span class="badge bg-warning">Dormant</span>
                                            @elseif($account->account_status == 4)
                                                <span class="badge bg-danger">Frozen</span>
                                            @else
                                                <span class="badge bg-secondary">Unknown</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Branch:</strong></div>
                                        <div class="col-7">{{ $account->branch }}</div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-5"><strong>Date Opened:</strong></div>
                                        <div class="col-7">{{ $account->date_opened ? $account->date_opened->format('d-M-Y') : 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Most Recent Transactions -->
                        <div class="card-header bg-primary bg-opacity-10">
                            <h5 class="card-title mb-0">
                                Most Recent Transactions 
                                <span class="badge bg-primary ms-2">{{ number_format($totalTransactionsForAccount) }} Total</span>
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            @if($transactions->count() > 0)
                                <!-- Pagination Controls -->
                                <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="btn btn-sm btn-outline-secondary" {{ $transactions->onFirstPage() ? 'disabled' : '' }}
                                                onclick="window.location='{{ $transactions->url(1) }}'">
                                            <i class="fas fa-angle-double-left"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" {{ $transactions->onFirstPage() ? 'disabled' : '' }}
                                                onclick="window.location='{{ $transactions->previousPageUrl() }}'">
                                            <i class="fas fa-angle-left"></i>
                                        </button>
                                        <span class="mx-2">
                                            Page {{ $transactions->currentPage() }} of {{ $transactions->lastPage() }}
                                        </span>
                                        <button class="btn btn-sm btn-outline-secondary" {{ !$transactions->hasMorePages() ? 'disabled' : '' }}
                                                onclick="window.location='{{ $transactions->nextPageUrl() }}'">
                                            <i class="fas fa-angle-right"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" {{ !$transactions->hasMorePages() ? 'disabled' : '' }}
                                                onclick="window.location='{{ $transactions->url($transactions->lastPage()) }}'">
                                            <i class="fas fa-angle-double-right"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    </div>
                                    <div>
                                        <span class="text-muted">
                                            Displaying {{ $transactions->firstItem() }} - {{ $transactions->lastItem() }} of {{ number_format($transactions->total()) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Transactions Table -->
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 30px;">#</th>
                                                <th>Financial Date</th>
                                                <th>Transaction Date</th>
                                                <th>Teller</th>
                                                <th>Reference</th>
                                                <th>Debit</th>
                                                <th>Credit</th>
                                                <th>Balance After</th>
                                                <th>Narration</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($transactions as $index => $transaction)
                                                <tr>
                                                    <td>{{ $transactions->firstItem() + $index }}</td>
                                                    <td>{{ $transaction->financial_date ? $transaction->financial_date->format('d-M-Y') : 'N/A' }}</td>
                                                    <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d-M-Y H:i') : 'N/A' }}</td>
                                                    <td>{{ $transaction->entry_code ?? 'N/A' }}</td>
                                                    <td>{{ $transaction->reference_id ?? 'N/A' }}</td>
                                                    <td class="text-end">
                                                        @if($transaction->debit > 0)
                                                            <span class="text-danger">{{ number_format($transaction->debit, 2) }}</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-end">
                                                        @if($transaction->credit > 0)
                                                            <span class="text-success">{{ number_format($transaction->credit, 2) }}</span>
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="text-end">{{ number_format($transaction->balance_after, 2) }}</td>
                                                    <td>{{ Str::limit($transaction->narration ?? 'N/A', 50) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No transactions found for this account</h5>
                                    <p class="text-muted">
                                        Account Number: {{ $account->old_acct_no }}<br>
                                        Customer ID in Account: {{ $account->customerid }}<br>
                                        Customer ID in Customer: {{ $customer->customer_id }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-piggy-bank fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No accounts found for this customer</h5>
                        <p class="text-muted">Customer ID: {{ $customer->customer_id }}</p>
                    </div>
                </div>
            @endif

        </div>
    </div>

@endsection