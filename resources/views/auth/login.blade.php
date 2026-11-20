{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="dark" data-bs-theme="light">
<head>
    <title>Sign in - Lukefield Finance Archive</title>
    @include('auth.partials.head')
</head>

<body>
    <div class="container-xxl">
        <div class="row vh-100 d-flex justify-content-center">
            <div class="col-12 align-self-center">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4 mx-auto">
                            <div class="card">
                                <div class="card-body p-0 bg-white auth-header-box rounded-top">
                                    <div class="text-center p-3">
                                        <a href="{{ url('/') }}" class="logo logo-admin">
                                            <img src="{{ asset('backend/lukefield-logo-removebg-preview.png') }}"
                                                 height="50" alt="Lukefield Finance" class="auth-logo">
                                        </a>
                                        <h4 class="mt-3 mb-1 fw-semibold text-black fs-18">Archive Repository sign in</h4>
                                        <p class="text-muted fw-medium mb-0">Authorised Lukefield staff only</p>
                                    </div>
                                </div>
                                <div class="card-body pt-0">

                                    @include('auth.partials.alerts')

                                    <form method="POST" action="{{ route('login.post') }}" class="my-4" autocomplete="on">
                                        @csrf

                                        <div class="form-group mb-3">
                                            <label class="form-label" for="email">Email address</label>
                                            <input type="email" name="email" id="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" required autofocus autocomplete="username"
                                                placeholder="you@lukefieldfinance.com">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mb-2">
                                            <label class="form-label" for="password">Password</label>
                                            <div class="input-group">
                                                <input type="password" name="password" id="password"
                                                    class="form-control @error('password') is-invalid @enderror"
                                                    required autocomplete="current-password" placeholder="Your password">
                                                <span class="input-group-text toggle-visibility" data-target="password" title="Show or hide password">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                                @error('password')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>

                                        <div class="text-end mb-3">
                                            <a href="{{ route('password.request') }}" class="small">Forgot your password?</a>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-shield-alt me-2"></i>Continue
                                            </button>
                                        </div>
                                    </form>

                                    <div class="alert alert-info mb-2 small" role="alert">
                                        <i class="fas fa-envelope me-2"></i>
                                        After your password, a 6-digit code is emailed to you. Sessions end after
                                        {{ config('security.idle_timeout', 15) }} minutes without activity.
                                    </div>

                                    <p class="text-muted text-center small mb-2">
                                        All sign-ins and activity are recorded.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('auth.partials.toggle-script')
</body>
</html>
