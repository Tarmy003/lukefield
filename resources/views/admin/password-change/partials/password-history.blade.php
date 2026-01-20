{{-- resources/views/admin/password-reset/partials/password-history.blade.php --}}

<div class="row mb-3">
    <div class="col-12">
        <div class="card bg-light">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('backend/default-avatar.jpg') }}" 
                         alt="{{ $user->name }}" class="avatar-sm rounded-circle me-3">
                    <div>
                        <h6 class="mb-1">{{ $user->preferred_name ?: ($user->first_name . ' ' . $user->last_name) ?: $user->name }}</h6>
                        <p class="text-muted mb-0">{{ $user->email }}</p>
                        <small class="text-muted">Employee ID: {{ $user->employee ?: 'Not Set' }}</small>
                    </div>
                    <div class="ms-auto">
                        @if($user->password_changed_at)
                            <div class="text-end">
                                <div class="fw-medium">Last Changed</div>
                                <div class="small text-muted">{{ $user->password_changed_at->format('M d, Y H:i') }}</div>
                                <div class="small text-muted">{{ $user->password_changed_at->diffForHumans() }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($history->count() > 0)
    <div class="row">
        <div class="col-12">
            <h6 class="mb-3">Password Reset History</h6>
            
            <div class="timeline">
                @foreach($history as $index => $reset)
                    <div class="timeline-item mb-4">
                        <div class="timeline-marker">
                            <div class="timeline-marker-icon bg-{{ 
                                $reset['method'] === 'temporary' ? 'info' : 
                                ($reset['method'] === 'auto' || str_contains($reset['method'], 'bulk_auto') ? 'primary' : 
                                ($reset['method'] === 'manual' ? 'warning' : 'secondary')) 
                            }}">
                                <i class="fas fa-{{ 
                                    $reset['method'] === 'temporary' ? 'clock' : 
                                    ($reset['method'] === 'auto' || str_contains($reset['method'], 'bulk_auto') ? 'magic' : 
                                    ($reset['method'] === 'manual' ? 'edit' : 'key')) 
                                }} text-white"></i>
                            </div>
                        </div>
                        
                        <div class="timeline-content">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <span class="badge bg-{{ 
                                                    $reset['method'] === 'temporary' ? 'info' : 
                                                    ($reset['method'] === 'auto' || str_contains($reset['method'], 'bulk_auto') ? 'primary' : 
                                                    ($reset['method'] === 'manual' ? 'warning' : 'secondary')) 
                                                }}-subtle text-{{ 
                                                    $reset['method'] === 'temporary' ? 'info' : 
                                                    ($reset['method'] === 'auto' || str_contains($reset['method'], 'bulk_auto') ? 'primary' : 
                                                    ($reset['method'] === 'manual' ? 'warning' : 'secondary')) 
                                                }} me-2">
                                                    {{ ucfirst(str_replace(['bulk_', '_'], ['Bulk ', ' '], $reset['method'])) }} Reset
                                                </span>
                                                
                                                @if($index === 0)
                                                    <span class="badge bg-success-subtle text-success">Latest</span>
                                                @endif
                                            </h6>
                                            
                                            <div class="small text-muted">
                                                <i class="fas fa-calendar-alt me-1"></i>
                                                {{ \Carbon\Carbon::parse($reset['date'])->format('F d, Y \a\t H:i') }}
                                                <span class="ms-2">
                                                    ({{ \Carbon\Carbon::parse($reset['date'])->diffForHumans() }})
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end">
                                            <div class="small text-muted">
                                                {{ \Carbon\Carbon::parse($reset['date'])->format('D') }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            @if(isset($reset['reset_by']))
                                                <div class="small mb-1">
                                                    <strong>Reset by:</strong> {{ $reset['reset_by'] }}
                                                </div>
                                            @endif
                                            
                                            @if(isset($reset['reason']))
                                                <div class="small mb-1">
                                                    <strong>Reason:</strong> {{ $reset['reason'] }}
                                                </div>
                                            @endif
                                            
                                            @if(isset($reset['browser']))
                                                <div class="small mb-1">
                                                    <strong>Browser:</strong> {{ $reset['browser'] }}
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <div class="col-md-6">
                                            @if(isset($reset['ip']))
                                                <div class="small mb-1">
                                                    <strong>IP Address:</strong> {{ $reset['ip'] }}
                                                </div>
                                            @endif
                                            
                                            @if(isset($reset['platform']))
                                                <div class="small mb-1">
                                                    <strong>Platform:</strong> {{ $reset['platform'] }}
                                                </div>
                                            @endif
                                            
                                            @if(isset($reset['admin_id']))
                                                <div class="small mb-1">
                                                    <strong>Admin ID:</strong> {{ $reset['admin_id'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @if(str_contains($reset['method'], 'temporary'))
                                        <div class="mt-2">
                                            <div class="alert alert-info alert-sm py-2">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <small>This was a temporary password that required change on first login.</small>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if(str_contains($reset['method'], 'bulk'))
                                        <div class="mt-2">
                                            <div class="alert alert-warning alert-sm py-2">
                                                <i class="fas fa-users me-1"></i>
                                                <small>This was part of a bulk password reset operation.</small>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Password Security Insights -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-chart-line me-2"></i>Password Security Insights
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="fs-4 fw-bold text-primary">{{ $history->count() }}</div>
                                <div class="small text-muted">Total Resets</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="fs-4 fw-bold text-info">{{ $history->where('method', 'temporary')->count() }}</div>
                                <div class="small text-muted">Temporary Passwords</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="fs-4 fw-bold text-warning">{{ $history->filter(function($item) { return str_contains($item['method'], 'bulk'); })->count() }}</div>
                                <div class="small text-muted">Bulk Resets</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                @php
                                    $lastReset = $history->first();
                                    $daysSinceLastReset = $lastReset ? \Carbon\Carbon::parse($lastReset['date'])->diffInDays(now()) : 0;
                                @endphp
                                <div class="fs-4 fw-bold text-{{ $daysSinceLastReset > 90 ? 'success' : ($daysSinceLastReset > 30 ? 'warning' : 'danger') }}">{{ $daysSinceLastReset }}</div>
                                <div class="small text-muted">Days Since Last Reset</div>
                            </div>
                        </div>
                    </div>
                    
                    @if($daysSinceLastReset > 180)
                        <div class="alert alert-success mt-3">
                            <i class="fas fa-shield-alt me-2"></i>
                            <strong>Good Security Practice:</strong> This user hasn't required a password reset in over 6 months, indicating good password security habits.
                        </div>
                    @elseif($history->count() > 5 && $daysSinceLastReset < 30)
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Frequent Resets:</strong> This user has had multiple recent password resets. Consider providing additional security training.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
@else
    <div class="row">
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-history fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No Password Reset History</h5>
                <p class="text-muted">This user has not had their password reset by an administrator.</p>
                
                @if($user->password_changed_at)
                    <div class="mt-3">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Last Password Change:</strong> {{ $user->password_changed_at->format('F d, Y \a\t H:i') }}
                            <br>
                            <small class="text-muted">({{ $user->password_changed_at->diffForHumans() }})</small>
                        </div>
                    </div>
                @else
                    <div class="mt-3">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>No Password Change Record:</strong> This user may be using their initial password.
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif

<!-- Current Password Status -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-secondary">
            <div class="card-header bg-secondary text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-shield-alt me-2"></i>Current Password Status
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Account Status:</strong>
                            @if($user->is_active)
                                <span class="badge bg-success ms-1">Active</span>
                            @else
                                <span class="badge bg-danger ms-1">Inactive</span>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <strong>Login Access:</strong>
                            @if($user->can_login)
                                <span class="badge bg-success ms-1">Enabled</span>
                            @else
                                <span class="badge bg-warning ms-1">Disabled</span>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <strong>Account Lock Status:</strong>
                            @if($user->locked_until && $user->locked_until->gt(now()))
                                <span class="badge bg-danger ms-1">Locked until {{ $user->locked_until->format('M d, H:i') }}</span>
                            @else
                                <span class="badge bg-success ms-1">Not Locked</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <strong>Password Change Required:</strong>
                            @if($user->password_change_required)
                                <span class="badge bg-warning ms-1">Yes</span>
                            @else
                                <span class="badge bg-success ms-1">No</span>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <strong>Temporary Password:</strong>
                            @if($user->is_temporary_password ?? false)
                                <span class="badge bg-info ms-1">Yes</span>
                                @if($user->password_expires_at)
                                    <div class="small text-muted mt-1">Expires: {{ $user->password_expires_at->format('M d, Y H:i') }}</div>
                                @endif
                            @else
                                <span class="badge bg-success ms-1">No</span>
                            @endif
                        </div>
                        
                        <div class="mb-3">
                            <strong>Failed Login Attempts:</strong>
                            @if($user->password_attempts > 0)
                                <span class="badge bg-warning ms-1">{{ $user->password_attempts }}</span>
                            @else
                                <span class="badge bg-success ms-1">0</span>
                            @endif
                        </div>
                    </div>
                </div>
                
                @if($user->two_factor_enabled)
                    <div class="alert alert-info">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Two-Factor Authentication:</strong> Enabled for enhanced security
                    </div>
                @endif
                
                @if($user->last_login_at)
                    <div class="mt-3 p-3 bg-light rounded">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Last Login:</strong><br>
                                <span class="text-muted">{{ $user->last_login_at->format('M d, Y H:i') }}</span><br>
                                <small class="text-muted">({{ $user->last_login_at->diffForHumans() }})</small>
                            </div>
                            <div class="col-md-4">
                                @if($user->last_login_ip)
                                    <strong>Last IP:</strong><br>
                                    <span class="text-muted">{{ $user->last_login_ip }}</span>
                                @endif
                            </div>
                            <div class="col-md-4">
                                @if($user->total_logins)
                                    <strong>Total Logins:</strong><br>
                                    <span class="text-muted">{{ number_format($user->total_logins) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Never Logged In:</strong> This user has never successfully logged into the system.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -15px;
    top: 0;
}

.timeline-marker-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-content {
    margin-left: 20px;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    object-fit: cover;
}

.alert-sm {
    padding: 0.375rem 0.75rem;
    margin-bottom: 0.5rem;
}

.alert-sm .fas {
    font-size: 0.875em;
}
</style>