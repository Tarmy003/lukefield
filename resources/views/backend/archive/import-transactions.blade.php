@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Import Transaction History</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('archive.index') }}">Archive</a></li>
                                <li class="breadcrumb-item active">Import Transactions</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Form -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Upload Transaction History Data</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Large File Warning:</strong> This import can handle large files (125,000+ records). 
                                The process may take several minutes. Please do not close this page during import.
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Import Progress:</strong> You can monitor the import progress in real-time by opening the 
                                <a href="{{ route('archive.logs') }}" target="_blank" class="alert-link">
                                    <i class="fas fa-external-link-alt me-1"></i>Import Logs
                                </a> 
                                page in a new tab before starting the import.
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Expected Columns:</strong> customerid, OldAcctNo, AccountID, TransactionID, TransactionDate, FinancialDate, ReferenceID, Narration, Debit, Credit, OpeningBalance, BalanceAfter, EntryCode, RecordType, PostingType, InstrumentNo, IsReversed, ReversalReferenceNo, ProductCode, Branch, AccountStatus, DateOpened
                            </div>

                            <form action="{{ route('archive.import.transactions.post') }}" method="POST" enctype="multipart/form-data" id="importForm">
                                @csrf
                                
                                <div class="mb-4">
                                    <label class="form-label">Select File</label>
                                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                           accept=".xlsx,.xls,.csv" required id="fileInput">
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Maximum file size: 50MB. Supported formats: XLSX, XLS, CSV</small>
                                </div>

                                <div id="fileInfo" class="alert alert-secondary" style="display: none;">
                                    <strong>File Selected:</strong> <span id="fileName"></span><br>
                                    <strong>Size:</strong> <span id="fileSize"></span>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="replaceData">
                                        <label class="form-check-label" for="replaceData">
                                            Replace existing transaction data
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-upload me-2"></i>Import Transactions
                                    </button>
                                    <a href="{{ route('archive.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                </div>
                            </form>

                            <!-- Progress Bar -->
                            <div id="progressContainer" class="mt-4" style="display: none;">
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%">
                                        <span class="fw-bold">0%</span>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Importing transactions in batches of 1,000 records...</p>
                                    <p class="text-muted"><small>This may take 5-10 minutes for large files. Please wait.</small></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Card -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Current Statistics</h4>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="p-3">
                                        <h3 class="mb-1 text-primary">{{ number_format($totalTransactions) }}</h3>
                                        <p class="text-muted mb-0">Total Transactions</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3">
                                        <h3 class="mb-1 text-success">1,000</h3>
                                        <p class="text-muted mb-0">Batch Size</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3">
                                        <h3 class="mb-1 text-info">~5-10 min</h3>
                                        <p class="text-muted mb-0">Estimated Time</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sample Format -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Sample File Format</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>customerid</th>
                                            <th>OldAcctNo</th>
                                            <th>TransactionDate</th>
                                            <th>Narration</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>000001</td>
                                            <td>04860012010000001</td>
                                            <td>1/21/2019</td>
                                            <td>PAYMENT ON BORROWINGS</td>
                                            <td>1021917808.00</td>
                                            <td>0</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // File info display
        document.getElementById('fileInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                document.getElementById('fileInfo').style.display = 'block';
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            }
        });

        // Form submission with progress
        document.getElementById('importForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('progressContainer').style.display = 'block';
            
            // Simulate progress
            let progress = 0;
            const progressBar = document.querySelector('.progress-bar');
            const interval = setInterval(() => {
                progress += 5;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                    progressBar.querySelector('span').textContent = progress + '%';
                }
            }, 2000); // Slower for large files
        });
    </script>

@endsection