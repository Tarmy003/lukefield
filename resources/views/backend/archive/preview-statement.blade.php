@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Statement Preview</h4>
                        <div class="page-title-right">
                            <div class="btn-group">
                                <form action="{{ route('archive.statement.excel') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="account_number" value="{{ $account->old_acct_no }}">
                                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-file-excel me-1"></i>Export Excel
                                    </button>
                                </form>
                                
                                <form action="{{ route('archive.statement.pdf') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="account_number" value="{{ $account->old_acct_no }}">
                                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="fas fa-file-pdf me-1"></i>Export PDF
                                    </button>
                                </form>
                                
                                <a href="{{ route('archive.statement.form') }}" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left me-1"></i>Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statement Container -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Statement Header -->
                            <div class="text-center mb-4 border-bottom pb-3">
                                <h3 class="mb-1">STATEMENT OF ACCOUNT</h3>
                                <p class="text-muted mb-0">Lukefield Finance Company Limited</p>
                            </div>

                            <!-- Account Information -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Account Number:</strong></td>
                                            <td>{{ $account->old_acct_no }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Customer ID:</strong></td>
                                            <td>{{ $account->customerid }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Branch:</strong></td>
                                            <td>{{ $account->branch }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Product Code:</strong></td>
                                            <td>{{ $account->product_code }}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Statement Period:</strong></td>
                                            <td>{{ $startDate->format('d M Y') }} to {{ $endDate->format('d M Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Opening Balance:</strong></td>
                                            <td><strong>NGN {{ number_format($openingBalance, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Closing Balance:</strong></td>
                                            <td><strong>NGN {{ number_format($closingBalance, 2) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Generated On:</strong></td>
                                            <td>{{ now()->format('d M Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Transactions Table -->
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th style="width: 12%;">Date</th>
                                            <th style="width: 15%;">Reference</th>
                                            <th style="width: 33%;">Narration</th>
                                            <th class="text-end" style="width: 13%;">Debit (NGN)</th>
                                            <th class="text-end" style="width: 13%;">Credit (NGN)</th>
                                            <th class="text-end" style="width: 14%;">Balance (NGN)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($transactions as $transaction)
                                            <tr>
                                                <td>{{ $transaction->transaction_date ? $transaction->transaction_date->format('d-M-Y') : 'N/A' }}</td>
                                                <td>{{ $transaction->reference_id ?? '-' }}</td>
                                                <td>{{ $transaction->narration ?? '-' }}</td>
                                                <td class="text-end">
                                                    @if($transaction->debit > 0)
                                                        {{ number_format($transaction->debit, 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    @if($transaction->credit > 0)
                                                        {{ number_format($transaction->credit, 2) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="text-end"><strong>{{ number_format($transaction->balance_after, 2) }}</strong></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">No transactions found for the selected period</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if($transactions->count() > 0)
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">TOTAL:</th>
                                                <th class="text-end">{{ number_format($totalDebit, 2) }}</th>
                                                <th class="text-end">{{ number_format($totalCredit, 2) }}</th>
                                                <th class="text-end"><strong>{{ number_format($closingBalance, 2) }}</strong></th>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>

                            <!-- Summary Section -->
                            @if($transactions->count() > 0)
                                <div class="row mt-4 border-top pt-3">
                                    <div class="col-md-8">
                                        <h6>Statement Summary</h6>
                                        <p class="mb-1"><strong>Total Transactions:</strong> {{ number_format($transactions->count()) }}</p>
                                        <p class="mb-1"><strong>Total Debits:</strong> NGN {{ number_format($totalDebit, 2) }}</p>
                                        <p class="mb-1"><strong>Total Credits:</strong> NGN {{ number_format($totalCredit, 2) }}</p>
                                        <p class="mb-1"><strong>Net Movement:</strong> NGN {{ number_format($totalCredit - $totalDebit, 2) }}</p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <div class="alert alert-info">
                                            <h6 class="mb-2">Current Balance</h6>
                                            <h3 class="mb-0">NGN {{ number_format($closingBalance, 2) }}</h3>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Footer -->
                            <div class="text-center mt-4 border-top pt-3">
                                <p class="text-muted mb-0"><small>This is a computer-generated statement and does not require a signature.</small></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <style>
        @media print {
            .page-title-box, .btn-group {
                display: none !important;
            }
        }
    </style>

@endsection