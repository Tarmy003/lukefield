@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Import Customers</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">Customers</a></li>
                                <li class="breadcrumb-item active">Import Customers</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Import Form -->
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Upload Customer Data</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Expected Format:</strong> Excel or CSV file with columns: CustomerID, Title, FirstName, Surname, OtherName, CustomerType, Sex, StateOfOrigin, DoB, ResidentialAddress, ResidentialState, ResidentialTown, Email, MobilePhone, BusinessPhoneNo, OfficeName, OfficeAddress, OfficePhone, AcctOfficer, Occupation, EduLevel, Status, DateOpened, BVN, NIN, TIN, IDType, IDNo, IDIssueDate, IDExpiryDate, MaritalStatus, NextOfKin, NextOfKinPhoneNo, NextOfKinAddress, RegNo, Sector, PEP, PEPDetails
                            </div>

                            @if(session('success'))
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Success!</strong> {{ session('success') }}
                                    @if(session('stats'))
                                        <hr>
                                        <ul class="mb-0">
                                            <li>Total Rows: {{ number_format(session('stats')['total_rows']) }}</li>
                                            <li>Inserted: {{ number_format(session('stats')['inserted']) }}</li>
                                            @if(session('stats')['updated'] > 0)
                                                <li>Updated: {{ number_format(session('stats')['updated']) }}</li>
                                            @endif
                                            @if(session('stats')['duplicates_skipped'] > 0)
                                                <li>Duplicates Skipped: {{ number_format(session('stats')['duplicates_skipped']) }}</li>
                                            @endif
                                            <li>Errors: {{ number_format(session('stats')['errors']) }}</li>
                                            <li>Duration: {{ session('stats')['duration'] }}s</li>
                                            <li>Processing Rate: {{ session('stats')['rate'] }} rows/sec</li>
                                        </ul>
                                    @endif
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong>Error!</strong> {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @endif

                            <form action="{{ route('customers.import.post') }}" method="POST" enctype="multipart/form-data" id="importForm">
                                @csrf
                                
                                <div class="mb-4">
                                    <label class="form-label">Select File <span class="text-danger">*</span></label>
                                    <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" 
                                           accept=".xlsx,.xls,.csv" required id="fileInput">
                                    @error('file')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted">Maximum file size: 20MB. Supported formats: XLSX, XLS, CSV</small>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="replace_data" id="replaceData" value="1">
                                        <label class="form-check-label" for="replaceData">
                                            <strong>Replace/Update Existing Data</strong>
                                            <br>
                                            <small class="text-muted">When checked, existing customer records will be updated. When unchecked, duplicate CustomerIDs will be skipped.</small>
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <i class="fas fa-upload me-2"></i>Import Customers
                                    </button>
                                    <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Customers
                                    </a>
                                    <a href="{{ route('customers.download.template') }}" class="btn btn-success ms-auto">
                                        <i class="fas fa-download me-2"></i>Download Template
                                    </a>
                                </div>
                            </form>

                            <!-- Progress Bar -->
                            <div id="progressContainer" class="mt-4" style="display: none;">
                                <div class="progress" style="height: 30px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                         role="progressbar" style="width: 0%; font-size: 14px; line-height: 30px;">
                                        <span id="progressText">0%</span>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <p class="mb-1"><strong>Importing customers, please wait...</strong></p>
                                    <p class="text-muted mb-0">This may take several minutes for large files. Do not close this page.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Card -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Current Database Statistics</h4>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="p-3 border-end">
                                        <h3 class="mb-1 text-primary">{{ number_format($totalCustomers) }}</h3>
                                        <p class="text-muted mb-0">Total Customers</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 border-end">
                                        <h3 class="mb-1 text-success">{{ number_format($activeCustomers) }}</h3>
                                        <p class="text-muted mb-0">Active Customers</p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3">
                                        <h3 class="mb-1 text-info">{{ now()->format('d M Y H:i') }}</h3>
                                        <p class="text-muted mb-0">Current Time</p>
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
                                            <th>CustomerID</th>
                                            <th>Title</th>
                                            <th>FirstName</th>
                                            <th>Surname</th>
                                            <th>OtherName</th>
                                            <th>CustomerType</th>
                                            <th>Sex</th>
                                            <th>DoB</th>
                                            <th>Email</th>
                                            <th>MobilePhone</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>1</td>
                                            <td>Mr.</td>
                                            <td>BABATUNJI</td>
                                            <td>FABAMIGBE</td>
                                            <td>NULL</td>
                                            <td>Individual</td>
                                            <td>MALE</td>
                                            <td>11/16/1974</td>
                                            <td>fabamigbet@yahoo.com</td>
                                            <td>8182141204</td>
                                            <td>Active</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-muted mt-3 mb-0">
                                <i class="fas fa-lightbulb me-1"></i>
                                <small>The sample shows only some key columns. Your file should include all 37 columns as listed in the expected format above.</small>
                            </p>
                        </div>
                    </div>

                    <!-- Important Notes -->
                    <div class="card border-warning">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Important Notes</h5>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li><strong>CustomerID</strong> is required and must be unique</li>
                                <li><strong>FirstName</strong> and <strong>Surname</strong> are required</li>
                                <li>Date fields can be in format: MM/DD/YYYY or Excel date numbers</li>
                                <li>Use "NULL" or leave blank for empty fields</li>
                                <li>The import can handle 5000+ records efficiently</li>
                                <li>Progress is logged to <code>storage/logs/import-customers.log</code></li>
                                <li>Maximum file size is 20MB</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('fileInput');
            
            if (!fileInput.files.length) {
                e.preventDefault();
                alert('Please select a file to import');
                return;
            }

            const fileSize = fileInput.files[0].size / 1024 / 1024; // in MB
            if (fileSize > 20) {
                e.preventDefault();
                alert('File size exceeds 20MB limit');
                return;
            }

            // Disable submit button
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
            
            // Show progress container
            document.getElementById('progressContainer').style.display = 'block';
            
            // Simulate progress (actual progress happens on server)
            let progress = 0;
            const progressBar = document.querySelector('.progress-bar');
            const progressText = document.getElementById('progressText');
            
            const interval = setInterval(() => {
                progress += 5;
                if (progress <= 95) {
                    progressBar.style.width = progress + '%';
                    progressText.textContent = progress + '%';
                } else {
                    clearInterval(interval);
                    progressText.textContent = 'Finalizing...';
                }
            }, 1000);
        });
    </script>

    <style>
        .border-end {
            border-right: 1px solid #dee2e6;
        }
        
        @media (max-width: 768px) {
            .border-end {
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }
        }
    </style>
@endsection