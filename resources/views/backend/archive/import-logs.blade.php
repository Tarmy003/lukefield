@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <!-- Page Header -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Import Transaction Logs</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('archive.index') }}">Archive</a></li>
                                <li class="breadcrumb-item active">Import Logs</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Transaction Import Logs</h4>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-primary" onclick="refreshLogs()">
                                        <i class="fas fa-sync-alt me-1"></i>Refresh
                                    </button>
                                    <button class="btn btn-sm btn-info" onclick="downloadLogs()">
                                        <i class="fas fa-download me-1"></i>Download
                                    </button>
                                    <form action="{{ route('archive.logs.clear') }}" method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to clear all import logs?')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash me-1"></i>Clear Logs
                                        </button>
                                    </form>
                                    <a href="{{ route('archive.import.transactions') }}" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-arrow-left me-1"></i>Back
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            @if($logExists)
                                <!-- Log Statistics -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Log File:</strong> storage/logs/import-transactions.log
                                        </div>
                                    </div>
                                </div>

                                <!-- Auto-refresh toggle -->
                                <div class="row mb-3">
                                    <div class="col-md-12">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="autoRefresh">
                                            <label class="form-check-label" for="autoRefresh">
                                                Auto-refresh every 5 seconds
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Logs Display -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="log-container" style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; max-height: 600px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6;">
                                            <pre style="margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?php
                                                // Highlight different log levels
                                                $highlighted = $logs;
                                                $highlighted = preg_replace('/\[ERROR\]/', '<span style="color: #f44336; font-weight: bold;">[ERROR]</span>', $highlighted);
                                                $highlighted = preg_replace('/\[WARNING\]/', '<span style="color: #ff9800; font-weight: bold;">[WARNING]</span>', $highlighted);
                                                $highlighted = preg_replace('/\[INFO\]/', '<span style="color: #4caf50;">[INFO]</span>', $highlighted);
                                                $highlighted = preg_replace('/(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})/', '<span style="color: #64b5f6;">$1</span>', $highlighted);
                                                $highlighted = preg_replace('/(import_[a-f0-9\.]+)/', '<span style="color: #ffb74d;">$1</span>', $highlighted);
                                                $highlighted = preg_replace('/(Success(?:ful)?:\s*\d+)/', '<span style="color: #81c784; font-weight: bold;">$1</span>', $highlighted);
                                                $highlighted = preg_replace('/(Errors?:\s*\d+)/', '<span style="color: #e57373; font-weight: bold;">$1</span>', $highlighted);
                                                $highlighted = preg_replace('/(Duration:\s*[\d\.]+s)/', '<span style="color: #ba68c8;">$1</span>', $highlighted);
                                                
                                                echo $highlighted;
                                            ?></pre>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Analysis -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Quick Analysis</h6>
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <strong>Total Sessions:</strong>
                                                        {{ substr_count($logs, 'NEW IMPORT SESSION STARTED') }}
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Successful:</strong>
                                                        <span class="text-success">{{ substr_count($logs, 'COMPLETED SUCCESSFULLY') }}</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Failed:</strong>
                                                        <span class="text-danger">{{ substr_count($logs, 'IMPORT SESSION FAILED') }}</span>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <strong>Errors Logged:</strong>
                                                        <span class="text-warning">{{ substr_count($logs, '[ERROR]') }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No import logs found</h5>
                                    <p class="text-muted">Import transaction history to generate logs</p>
                                    <a href="{{ route('archive.import.transactions') }}" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Go to Import
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        let autoRefreshInterval = null;

        function refreshLogs() {
            location.reload();
        }

        function downloadLogs() {
            // Create a download link for the logs
            const logContent = document.querySelector('.log-container pre').textContent;
            const blob = new Blob([logContent], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'import-transactions-' + new Date().toISOString().slice(0,10) + '.log';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        // Auto-refresh functionality
        document.getElementById('autoRefresh')?.addEventListener('change', function() {
            if (this.checked) {
                autoRefreshInterval = setInterval(refreshLogs, 5000); // 5 seconds
            } else {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                }
            }
        });

        // Auto-scroll to bottom
        const logContainer = document.querySelector('.log-container');
        if (logContainer) {
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        });
    </script>

    <style>
        .log-container::-webkit-scrollbar {
            width: 10px;
        }

        .log-container::-webkit-scrollbar-track {
            background: #2d2d2d;
        }

        .log-container::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 5px;
        }

        .log-container::-webkit-scrollbar-thumb:hover {
            background: #777;
        }
    </style>

@endsection