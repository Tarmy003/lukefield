{{-- resources/views/backend/account/password.blade.php --}}
@extends('backend.layouts.master')

@section('content')
    <div class="page-content">
        <div class="container-xxl">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <h4 class="mb-0">Password &amp; security</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item active">Password &amp; security</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
            @endif

            @if ($reason)
                <div class="alert alert-warning d-flex align-items-start">
                    <i class="fas fa-key fs-4 me-3 mt-1"></i>
                    <div>
                        <strong>Set a new password to continue.</strong><br>
                        @switch($reason)
                            @case('expired')
                                Your password is more than {{ config('security.password.expiry_days', 90) }} days old.
                                @break
                            @case('never_changed')
                                Your account still has the password it was set up with.
                                @break
                            @default
                                The IT Unit requires you to choose a new password.
                        @endswitch
                        The rest of the archive opens once it is changed.
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-key me-2"></i>Change password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('account.password.update') }}" autocomplete="off">
                                @csrf
                                @method('PUT')
                                <input type="email" name="username" value="{{ $user->email }}" autocomplete="username" hidden readonly>

                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current password</label>
                                    <div class="input-group">
                                        <input type="password" id="current_password" name="current_password"
                                            class="form-control @error('current_password') is-invalid @enderror"
                                            required autocomplete="current-password" autofocus>
                                        <span class="input-group-text toggle-visibility" data-target="current_password" role="button" title="Show or hide">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                        @error('current_password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">New password</label>
                                    <div class="input-group">
                                        <input type="password" id="password" name="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            required autocomplete="new-password">
                                        <span class="input-group-text toggle-visibility" data-target="password" role="button" title="Show or hide">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                        @error('password')
                                            <div class="invalid-feedback">{!! implode('<br>', array_map('e', $errors->get('password'))) !!}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm new password</label>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-control" required autocomplete="new-password">
                                </div>

                                <div class="bg-light rounded p-3 mb-3">
                                    <p class="fw-semibold small mb-2">Your new password must have:</p>
                                    <ul class="small mb-0 ps-3">
                                        @foreach ($policy as $line)
                                            <li>{{ $line }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <p class="text-muted small">
                                    Changing your password signs you out on every other computer and browser,
                                    and we email you to confirm the change.
                                </p>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Change password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-shield-alt me-2"></i>Your account</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-6 text-muted fw-normal">Email</dt>
                                <dd class="col-6">{{ $user->email }}</dd>

                                <dt class="col-6 text-muted fw-normal">Roles</dt>
                                <dd class="col-6">{{ $user->getRoleNames()->implode(', ') ?: 'None' }}</dd>

                                <dt class="col-6 text-muted fw-normal">Password last changed</dt>
                                <dd class="col-6">
                                    {{ $user->password_changed_at ? $user->password_changed_at->timezone('Africa/Lagos')->format('j M Y') : 'Not yet in the archive' }}
                                </dd>

                                <dt class="col-6 text-muted fw-normal">Password expires</dt>
                                <dd class="col-6">
                                    @if ($expiresAt)
                                        {{ $expiresAt->timezone('Africa/Lagos')->format('j M Y') }}
                                        @if ($daysLeft !== null && $daysLeft >= 0)
                                            <span class="badge {{ $daysLeft <= config('security.password.warn_days', 14) ? 'bg-warning-subtle text-warning' : 'bg-success-subtle text-success' }}">
                                                {{ $daysLeft }} day(s)
                                            </span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger">Expired</span>
                                        @endif
                                    @else
                                        <span class="text-muted">On first change</span>
                                    @endif
                                </dd>

                                <dt class="col-6 text-muted fw-normal">Sign-in code by email</dt>
                                <dd class="col-6"><span class="badge bg-success-subtle text-success">Always on</span></dd>

                                <dt class="col-6 text-muted fw-normal">Inactivity sign-out</dt>
                                <dd class="col-6">After {{ config('security.idle_timeout', 15) }} minutes</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><i class="fas fa-history me-2"></i>Your recent sign-ins</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 small">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3">When</th>
                                            <th>IP address</th>
                                            <th>Browser</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentLogins as $login)
                                            <tr>
                                                <td class="ps-3">{{ optional($login->logged_in_at)->timezone('Africa/Lagos')?->format('j M Y, H:i') }}</td>
                                                <td>{{ $login->ip_address }}</td>
                                                <td>{{ trim(($login->browser ?? '').' on '.($login->platform ?? ''), ' on') ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-3">No sign-ins recorded yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer small text-muted">
                            See a sign-in you don't recognise? Change your password now and tell the IT Unit.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('.toggle-visibility').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.target);
                var icon = button.querySelector('i');
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                icon.classList.toggle('fa-eye', !show);
                icon.classList.toggle('fa-eye-slash', show);
            });
        });
    </script>
@endpush
