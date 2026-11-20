{{-- resources/views/auth/forgot-password.blade.php --}}
<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="dark" data-bs-theme="light">
<head>
    <title>Forgot password - Lukefield Finance Archive</title>
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
                                        <h4 class="mt-3 mb-1 fw-semibold text-black fs-18">Forgot your password?</h4>
                                        <p class="text-muted fw-medium mb-0">We'll email you a link to set a new one</p>
                                    </div>
                                </div>
                                <div class="card-body pt-0">

                                    @include('auth.partials.alerts')

                                    <form method="POST" action="{{ route('password.email') }}" class="my-4">
                                        @csrf

                                        <div class="form-group mb-3">
                                            <label class="form-label" for="email">Your work email address</label>
                                            <input type="email" name="email" id="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" required autofocus autocomplete="username"
                                                placeholder="you@lukefieldfinance.com">
                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i>Email me a reset link
                                            </button>
                                        </div>
                                    </form>

                                    <p class="text-muted small">
                                        The link works once and expires after {{ config('security.links.reset_minutes', 60) }} minutes.
                                        If you don't receive it, check your spam folder, then contact the IT Unit.
                                    </p>

                                    <div class="text-center mb-2">
                                        <a href="{{ route('login') }}" class="small"><i class="fas fa-arrow-left me-1"></i>Back to sign in</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
