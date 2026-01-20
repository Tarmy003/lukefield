{{-- resources/views/dashboard.blade.php --}}
@extends('backend.layouts.master')

@section('content')

<!-- Page Content-->
<div class="page-content">
    <div class="container-xxl">

        <!-- User Welcome Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-1">
                                    <i class="las la-database text-primary me-2"></i>
                                    Welcome back, {{ auth()->user()->name }}
                                </h4>
                                <p class="text-muted mb-2">Archive Repository System Dashboard</p>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-primary">
                                        <i class="las la-shield-alt me-1"></i>Secure Access
                                    </span>
                                    <span class="badge bg-success">
                                        <i class="las la-clock me-1"></i>Last Login: {{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'First time' }}
                                    </span>
                                    <span class="badge bg-info">
                                        <i class="las la-map-marker me-1"></i>IP: {{ auth()->user()->last_login_ip ?? request()->ip() }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quickLinksModal">
                                    <i class="las la-rocket me-1"></i> Quick Access
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Alert Banner -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="las la-info-circle fs-3 me-3"></i>
                        <div>
                            <strong>System Notice:</strong> All activities in this archive system are logged and monitored for security compliance. 
                            Unauthorized access attempts will be reported to system administrators.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <!-- Main Stats Row -->
        <div class="row">
            <!-- Total Archived Accounts -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm hover-lift">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md rounded-circle bg-soft-primary">
                                    <span class="avatar-title bg-transparent text-primary rounded-circle">
                                        <i class="las la-user-friends fs-24"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Archived Accounts</h6>
                                <h3 class="mb-0 counter-value" data-target="0" id="totalAccounts">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </h3>
                                <small class="text-success">
                                    <i class="las la-arrow-up"></i> All time
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Transactions -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm hover-lift">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md rounded-circle bg-soft-success">
                                    <span class="avatar-title bg-transparent text-success rounded-circle">
                                        <i class="las la-exchange-alt fs-24"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Total Transactions</h6>
                                <h3 class="mb-0 counter-value" data-target="0" id="totalTransactions">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </h3>
                                <small class="text-success">
                                    <i class="las la-database"></i> Archived records
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Unique Customers -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm hover-lift">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md rounded-circle bg-soft-warning">
                                    <span class="avatar-title bg-transparent text-warning rounded-circle">
                                        <i class="las la-users fs-24"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Unique Customers</h6>
                                <h3 class="mb-0 counter-value" data-target="0" id="totalCustomers">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </h3>
                                <small class="text-warning">
                                    <i class="las la-id-card"></i> Customer records
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archive Size -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm hover-lift">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avatar-md rounded-circle bg-soft-info">
                                    <span class="avatar-title bg-transparent text-info rounded-circle">
                                        <i class="las la-hdd fs-24"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="text-muted mb-1">Archive Size</h6>
                                <h3 class="mb-0" id="archiveSize">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </h3>
                                <small class="text-info">
                                    <i class="las la-server"></i> Total storage
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--end row-->

        <!-- Activity Stats Row -->
        <div class="row mt-4">
            <!-- Search Queries Today -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="avatar-sm mx-auto mb-3 rounded-circle bg-soft-primary">
                            <span class="avatar-title bg-transparent text-primary rounded-circle">
                                <i class="las la-search fs-20"></i>
                            </span>
                        </div>
                        <h4 class="mb-1" id="searchQueriesToday">--</h4>
                        <p class="text-muted mb-0">Search Queries Today</p>
                    </div>
                </div>
            </div>

            <!-- Statements Generated -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="avatar-sm mx-auto mb-3 rounded-circle bg-soft-success">
                            <span class="avatar-title bg-transparent text-success rounded-circle">
                                <i class="las la-file-alt fs-20"></i>
                            </span>
                        </div>
                        <h4 class="mb-1" id="statementsGenerated">--</h4>
                        <p class="text-muted mb-0">Statements Generated</p>
                    </div>
                </div>
            </div>

            <!-- API Requests -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="avatar-sm mx-auto mb-3 rounded-circle bg-soft-warning">
                            <span class="avatar-title bg-transparent text-warning rounded-circle">
                                <i class="las la-code fs-20"></i>
                            </span>
                        </div>
                        <h4 class="mb-1" id="apiRequests">--</h4>
                        <p class="text-muted mb-0">API Requests Today</p>
                    </div>
                </div>
            </div>

            <!-- Active Users -->
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="avatar-sm mx-auto mb-3 rounded-circle bg-soft-info">
                            <span class="avatar-title bg-transparent text-info rounded-circle">
                                <i class="las la-user-check fs-20"></i>
                            </span>
                        </div>
                        <h4 class="mb-1" id="activeUsers">--</h4>
                        <p class="text-muted mb-0">Active Users</p>
                    </div>
                </div>
            </div>
        </div><!--end row-->

        <!-- Charts and Activities Row -->
        <div class="row mt-4">
            <!-- Archive Growth Chart -->
            <div class="col-md-12 col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title mb-0">
                                    <i class="las la-chart-line text-primary me-2"></i>
                                    Archive Growth Trend
                                </h4>
                            </div>
                            <div class="col-auto">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="las la-calendar me-1"></i> Last 7 Days
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="#">Last 7 Days</a></li>
                                        <li><a class="dropdown-item" href="#">Last 30 Days</a></li>
                                        <li><a class="dropdown-item" href="#">Last 90 Days</a></li>
                                        <li><a class="dropdown-item" href="#">This Year</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="archiveGrowthChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-md-12 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0">
                            <i class="las la-history text-primary me-2"></i>
                            Recent Activities
                        </h4>
                    </div>
                    <div class="card-body" style="max-height: 380px; overflow-y: auto;">
                        <div class="timeline-alt pb-0" id="recentActivities">
                            <div class="timeline-item">
                                <i class="las la-sign-in-alt bg-success text-white timeline-icon"></i>
                                <div class="timeline-item-content">
                                    <h6 class="timeline-item-title">Logged into system</h6>
                                    <p class="timeline-item-desc text-muted">
                                        <small>{{ now()->format('h:i A') }}</small>
                                    </p>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <i class="las la-shield-alt bg-info text-white timeline-icon"></i>
                                <div class="timeline-item-content">
                                    <h6 class="timeline-item-title">2FA verification successful</h6>
                                    <p class="timeline-item-desc text-muted">
                                        <small>{{ now()->format('h:i A') }}</small>
                                    </p>
                                </div>
                            </div>
                            <!-- More activities will be loaded dynamically -->
                        </div>
                    </div>
                </div>
            </div>
        </div><!--end row-->

        <!-- Data Integrity & Security Section -->
        <div class="row mt-4">
            <!-- Data Integrity Status -->
            <div class="col-md-12 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0">
                            <i class="las la-check-circle text-success me-2"></i>
                            Data Integrity Status
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="text-center p-3 bg-soft-success rounded">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="las la-database text-success fs-2 me-2"></i>
                                        <h3 class="mb-0 text-success" id="dataIntegrity">100%</h3>
                                    </div>
                                    <small class="text-muted">Data Integrity</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-center p-3 bg-soft-primary rounded">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="las la-sync text-primary fs-2 me-2"></i>
                                        <h3 class="mb-0 text-primary" id="lastSync">--</h3>
                                    </div>
                                    <small class="text-muted">Last Sync</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-soft-warning rounded">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="las la-exclamation-triangle text-warning fs-2 me-2"></i>
                                        <h3 class="mb-0 text-warning" id="errorCount">0</h3>
                                    </div>
                                    <small class="text-muted">Errors Detected</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-soft-info rounded">
                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                        <i class="las la-clock text-info fs-2 me-2"></i>
                                        <h3 class="mb-0 text-info" id="uptime">99.9%</h3>
                                    </div>
                                    <small class="text-muted">System Uptime</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Logs -->
            <div class="col-md-12 col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="card-title mb-0">
                                    <i class="las la-shield-alt text-primary me-2"></i>
                                    Security Overview
                                </h4>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                                <div>
                                    <i class="las la-check-circle text-success me-2"></i>
                                    <strong>2FA Enabled</strong>
                                </div>
                                <span class="badge bg-success">Active</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                                <div>
                                    <i class="las la-lock text-primary me-2"></i>
                                    <strong>Encryption</strong>
                                </div>
                                <span class="badge bg-primary">256-bit AES</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                                <div>
                                    <i class="las la-user-shield text-info me-2"></i>
                                    <strong>Login Attempts (24h)</strong>
                                </div>
                                <span class="badge bg-info" id="loginAttempts">--</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                                <div>
                                    <i class="las la-history text-warning me-2"></i>
                                    <strong>Activity Logs</strong>
                                </div>
                                <span class="badge bg-warning" id="activityLogs">--</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0">
                                <div>
                                    <i class="las la-ban text-danger me-2"></i>
                                    <strong>Failed Logins (24h)</strong>
                                </div>
                                <span class="badge bg-danger" id="failedLogins">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--end row-->

        <!-- Quick Stats Summary -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="card-title mb-0">
                            <i class="las la-chart-bar text-primary me-2"></i>
                            Archive Repository Statistics
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="p-3">
                                    <i class="las la-calendar-check text-primary fs-1 mb-2"></i>
                                    <h5 class="mb-1" id="oldestRecord">--</h5>
                                    <p class="text-muted mb-0 small">Oldest Record</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <i class="las la-calendar text-success fs-1 mb-2"></i>
                                    <h5 class="mb-1" id="newestRecord">--</h5>
                                    <p class="text-muted mb-0 small">Newest Record</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <i class="las la-file-export text-warning fs-1 mb-2"></i>
                                    <h5 class="mb-1" id="exportsToday">--</h5>
                                    <p class="text-muted mb-0 small">Exports Today</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <i class="las la-tachometer-alt text-info fs-1 mb-2"></i>
                                    <h5 class="mb-1" id="avgResponseTime">--</h5>
                                    <p class="text-muted mb-0 small">Avg Response Time</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--end row-->

    </div><!-- container -->

    <!-- Quick Links Modal -->
    <div class="modal fade" id="quickLinksModal" tabindex="-1" aria-labelledby="quickLinksModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="quickLinksModalLabel">
                        <i class="las la-rocket me-2"></i>Quick Access Menu
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Search Archive -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-soft-primary h-100">
                                <div class="card-body text-center">
                                    <i class="las la-search h1 text-primary mb-3"></i>
                                    <h6 class="card-title">Search Archive</h6>
                                    <p class="text-muted small">Find accounts, transactions, and customer records</p>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i class="las la-user me-1"></i>Search Accounts
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i class="las la-exchange-alt me-1"></i>Search Transactions
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-primary">
                                            <i class="las la-users me-1"></i>Search Customers
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Generate Reports -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-soft-success h-100">
                                <div class="card-body text-center">
                                    <i class="las la-file-alt h1 text-success mb-3"></i>
                                    <h6 class="card-title">Generate Statements</h6>
                                    <p class="text-muted small">Create account statements and reports</p>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="#" class="btn btn-sm btn-outline-success">
                                            <i class="las la-file-invoice me-1"></i>Account Statement
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-success">
                                            <i class="las la-chart-line me-1"></i>Transaction Report
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-success">
                                            <i class="las la-file-excel me-1"></i>Export to Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Import -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-soft-warning h-100">
                                <div class="card-body text-center">
                                    <i class="las la-file-import h1 text-warning mb-3"></i>
                                    <h6 class="card-title">Data Import</h6>
                                    <p class="text-muted small">Import data from BankOne SQL Server</p>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="#" class="btn btn-sm btn-outline-warning">
                                            <i class="las la-database me-1"></i>Import Accounts
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-warning">
                                            <i class="las la-sync me-1"></i>Sync Transactions
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-warning">
                                            <i class="las la-clock me-1"></i>Schedule Import
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- API Access -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-soft-info h-100">
                                <div class="card-body text-center">
                                    <i class="las la-code h1 text-info mb-3"></i>
                                    <h6 class="card-title">API Access</h6>
                                    <p class="text-muted small">Access archive data programmatically</p>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="#" class="btn btn-sm btn-outline-info">
                                            <i class="las la-book me-1"></i>API Documentation
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-info">
                                            <i class="las la-key me-1"></i>API Keys
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-info">
                                            <i class="las la-chart-bar me-1"></i>Usage Stats
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security & Logs -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-soft-danger h-100">
                                <div class="card-body text-center">
                                    <i class="las la-shield-alt h1 text-danger mb-3"></i>
                                    <h6 class="card-title">Security & Audit</h6>
                                    <p class="text-muted small">Monitor system security and activities</p>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="#" class="btn btn-sm btn-outline-danger">
                                            <i class="las la-history me-1"></i>Activity Logs
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-danger">
                                            <i class="las la-user-shield me-1"></i>Login History
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-danger">
                                            <i class="las la-lock me-1"></i>Security Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Administration -->
                        <div class="col-md-6">
                            <div class="card border-0 bg-soft-secondary h-100">
                                <div class="card-body text-center">
                                    <i class="las la-cog h1 text-secondary mb-3"></i>
                                    <h6 class="card-title">Administration</h6>
                                    <p class="text-muted small">Manage system settings and users</p>
                                    <div class="d-grid gap-2 mt-3">
                                        <a href="#" class="btn btn-sm btn-outline-secondary">
                                            <i class="las la-users-cog me-1"></i>User Management
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-secondary">
                                            <i class="las la-sliders-h me-1"></i>System Settings
                                        </a>
                                        <a href="#" class="btn btn-sm btn-outline-secondary">
                                            <i class="las la-database me-1"></i>Database Health
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <!-- End Quick Links Modal -->

    <!-- Footer -->
    <footer class="footer text-center text-sm-start d-print-none">
        <div class="container-xxl">
            <div class="row">
                <div class="col-12">
                    <div class="card mb-0 rounded-bottom-0 border-0">
                        <div class="card-body">
                            <p class="text-muted mb-0">
                                © <script>document.write(new Date().getFullYear())</script> Lukefield Finance Company Limited - Archive Repository System
                                <span class="text-muted d-none d-sm-inline-block float-end">
                                    <i class="las la-shield-alt text-success"></i> Secure & CBN Compliant
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

</div>
<!-- end page content -->

@endsection

@push('styles')
<style>
/* Card Hover Effects */
.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

/* Avatar Sizes */
.avatar-md {
    width: 64px;
    height: 64px;
}

/* Soft Background Colors */
.bg-soft-primary {
    background-color: rgba(59, 130, 246, 0.1) !important;
}

.bg-soft-success {
    background-color: rgba(16, 185, 129, 0.1) !important;
}

.bg-soft-warning {
    background-color: rgba(251, 191, 36, 0.1) !important;
}

.bg-soft-info {
    background-color: rgba(14, 165, 233, 0.1) !important;
}

.bg-soft-danger {
    background-color: rgba(239, 68, 68, 0.1) !important;
}

.bg-soft-secondary {
    background-color: rgba(107, 114, 128, 0.1) !important;
}

/* Timeline Styles */
.timeline-alt {
    position: relative;
    padding: 0;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: 0;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.timeline-item-content h6 {
    margin-bottom: 5px;
    font-size: 14px;
}

.timeline-item-desc {
    font-size: 12px;
    margin-bottom: 0;
}

/* Counter Animation */
@keyframes countUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.counter-value {
    animation: countUp 0.5s ease-out;
}

/* Loading Spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
    border-width: 0.15em;
}

/* Scrollbar Styling */
.card-body::-webkit-scrollbar {
    width: 6px;
}

.card-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.card-body::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .avatar-md {
        width: 48px;
        height: 48px;
    }
    
    .fs-24 {
        font-size: 20px !important;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Archive Growth Chart
    initializeArchiveChart();
    
    // Load Dashboard Data
    loadDashboardData();
    
    // Auto-refresh every 5 minutes
    setInterval(loadDashboardData, 300000);
    
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function initializeArchiveChart() {
    const ctx = document.getElementById('archiveGrowthChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [
                    {
                        label: 'Accounts Added',
                        data: [120, 190, 150, 210, 180, 220, 250],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Transactions Archived',
                        data: [5400, 7200, 6800, 8100, 7500, 8900, 9500],
                        borderColor: 'rgb(16, 185, 129)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }
}

function loadDashboardData() {
    // Simulated data - replace with actual API calls
    updateStatistic('totalAccounts', 45678, true);
    updateStatistic('totalTransactions', 2345789, true);
    updateStatistic('totalCustomers', 23456, true);
    updateStatistic('archiveSize', '127.5 GB');
    updateStatistic('searchQueriesToday', 156);
    updateStatistic('statementsGenerated', 47);
    updateStatistic('apiRequests', 892);
    updateStatistic('activeUsers', 12);
    updateStatistic('lastSync', '2 hours ago');
    updateStatistic('loginAttempts', 34);
    updateStatistic('activityLogs', 1247);
    updateStatistic('oldestRecord', 'Jan 2010');
    updateStatistic('newestRecord', 'Today');
    updateStatistic('exportsToday', 23);
    updateStatistic('avgResponseTime', '125ms');
}

function updateStatistic(elementId, value, animate = false) {
    const element = document.getElementById(elementId);
    if (element) {
        if (animate && typeof value === 'number') {
            animateNumber(element, 0, value, 1000);
        } else {
            element.textContent = typeof value === 'number' ? value.toLocaleString() : value;
        }
    }
}

function animateNumber(element, start, end, duration) {
    const range = end - start;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        const easeOutCubic = 1 - Math.pow(1 - progress, 3);
        const current = Math.floor(start + (range * easeOutCubic));
        
        element.textContent = current.toLocaleString();
        
        if (progress < 1) {
            requestAnimationFrame(update);
        } else {
            element.textContent = end.toLocaleString();
        }
    }
    
    requestAnimationFrame(update);
}

// First Login Welcome
@if($isFirstLogin)
    Swal.fire({
        title: 'Welcome to Archive Repository!',
        html: `
            <div style="text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🗄️</div>
                <p style="font-size: 1.1rem; color: #555; margin-bottom: 1rem;">
                    Hello <strong>{{ auth()->user()->name }}</strong>,
                </p>
                <p style="font-size: 1rem; color: #666; line-height: 1.6;">
                    Welcome to Lukefield Finance's secure Banking Archive Repository System. 
                    This platform provides secure access to historical banking records with 
                    full audit trail and CBN compliance.
                </p>
                <div class="alert alert-info mt-3" style="font-size: 0.9rem;">
                    <i class="las la-shield-alt me-1"></i>
                    All activities are logged for security and compliance.
                </div>
            </div>
        `,
        icon: 'success',
        iconColor: '#3b82f6',
        confirmButtonText: 'Get Started',
        confirmButtonColor: '#3b82f6',
        showClass: {
            popup: 'animate__animated animate__fadeInDown'
        },
        allowOutsideClick: false
    });
@endif
</script>
@endpushDa