@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Import Accounts</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('archive.index') }}">Archive</a></li>
                                <li class="breadcrumb-item active">Import Accounts</li>
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
                            <h4 class="card-title">Upload Account Data</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Expected Format:</strong> Excel or CSV file with columns: customerid, OldAcctNo, Branch, ProductCode, BkBalance(Corrected), AccruedTillDate, LastAccruedDate, LienStatus, AccountStatus, AccountTier, DateOpened
                            </div>

                            <form action="{{ route('archive.import.accounts.post') }}" method="POST" enctype="multipart/form-data" id="importForm">
                                @csrf
                                
                                <div class="mb-4">
                                    <label class="form-label">Select File</label>
                                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                           accept=".xlsx,.xls,.csv" required>
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Maximum file size: 10MB. Supported formats: XLSX, XLS, CSV</small>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="replaceData">
                                        <label class="form-check-label" for="replaceData">
                                            Replace existing data
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-upload me-2"></i>Import Accounts
                                    </button>
                                    <a href="{{ route('archive.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </a>
                                </div>
                            </form>

                            <!-- Progress Bar -->
                            <div id="progressContainer" class="mt-4" style="display: none;">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%">
                                        0%
                                    </div>
                                </div>
                                <p class="text-center mt-2">Importing accounts, please wait...</p>
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
                                <div class="col-md-6">
                                    <div class="p-3">
                                        <h3 class="mb-1">{{ number_format($totalAccounts) }}</h3>
                                        <p class="text-muted mb-0">Total Accounts</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="p-3">
                                        <h3 class="mb-1">{{ now()->format('d M Y') }}</h3>
                                        <p class="text-muted mb-0">Last Updated</p>
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
                                            <th>Branch</th>
                                            <th>ProductCode</th>
                                            <th>BkBalance(Corrected)</th>
                                            <th>AccountStatus</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>000001</td>
                                            <td>04860012010000001</td>
                                            <td>Head Office</td>
                                            <td>201</td>
                                            <td>321.03</td>
                                            <td>2</td>
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
        document.getElementById('importForm').addEventListener('submit', function() {
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('progressContainer').style.display = 'block';
            
            // Simulate progress
            let progress = 0;
            const progressBar = document.querySelector('.progress-bar');
            const interval = setInterval(() => {
                progress += 10;
                if (progress <= 90) {
                    progressBar.style.width = progress + '%';
                    progressBar.textContent = progress + '%';
                }
            }, 500);
        });
    </script>

@endsection