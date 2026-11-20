{{-- resources/views/auth/reset-password.blade.php --}}
<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="dark" data-bs-theme="light">
<head>
    <title>{{ ($valid && $isWelcome) ? 'Set up your account' : 'Reset password' }} - Lukefield Finance Archive</title>
    @include('auth.partials.head')
</head>

<body>
    <div class="container-xxl">
        <div class="row vh-100 d-flex justify-content-center">
            <div class="col-12 align-self-center">
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-5 mx-auto">
                            <div class="card">
                                <div class="card-body p-0 bg-white auth-header-box rounded-top">
                                    <div class="text-center p-3">
                                        <a href="{{ url('/') }}" class="logo logo-admin">
                                            <img src="{{ asset('backend/lukefield-logo-removebg-preview.png') }}"
                                                 height="50" alt="Lukefield Finance" class="auth-logo">
                                        </a>
                                        @if ($valid)
                                            <h4 class="mt-3 mb-1 fw-semibold text-black fs-18">
                                                {{ $isWelcome ? 'Set up your account' : 'Choose a new password' }}
                                            </h4>
                                            <p class="text-muted fw-medium mb-0">{{ $email }}</p>
                                        @else
                                            <h4 class="mt-3 mb-1 fw-semibold text-black fs-18">This link no longer works</h4>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body pt-0">

                                    @if (! $valid)
                                        <div class="alert alert-warning mt-3">
                                            The link has expired, has already been used, or a newer link was sent after it.
                                        </div>
                                        <div class="d-grid gap-2 mb-2">
                                            <a href="{{ route('password.request') }}" class="btn btn-primary">Request a new link</a>
                                            <a href="{{ route('login') }}" class="btn btn-outline-secondary">Back to sign in</a>
                                        </div>
                                    @else
                                        <form method="POST" action="{{ route('password.store') }}" class="my-4">
                                            @csrf
                                            <input type="hidden" name="token" value="{{ $token }}">
                                            {{-- Lets password managers save the new password against the right account. --}}
                                            <input type="email" name="username" value="{{ $email }}" autocomplete="username" hidden readonly>

                                            <div class="form-group mb-3">
                                                <label class="form-label" for="password">New password</label>
                                                <div class="input-group">
                                                    <input type="password" name="password" id="password"
                                                        class="form-control @error('password') is-invalid @enderror"
                                                        required autofocus autocomplete="new-password">
                                                    <span class="input-group-text toggle-visibility" data-target="password" title="Show or hide password">
                                                        <i class="fas fa-eye"></i>
                                                    </span>
                                                    @error('password')
                                                        <div class="invalid-feedback">{!! implode('<br>', array_map('e', $errors->get('password'))) !!}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label class="form-label" for="password_confirmation">Confirm new password</label>
                                                <input type="password" name="password_confirmation" id="password_confirmation"
                                                    class="form-control" required autocomplete="new-password">
                                            </div>

                                            <div class="bg-light rounded p-3 mb-3">
                                                <p class="fw-semibold small mb-2">Your password must have:</p>
                                                <ul class="policy-list">
                                                    @foreach ($policy as $line)
                                                        <li>{{ $line }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            <div class="d-grid">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-check me-2"></i>{{ $isWelcome ? 'Set my password' : 'Reset my password' }}
                                                </button>
                                            </div>
                                        </form>

                                        <p class="text-muted small text-center mb-2">
                                            This link expires {{ $expiresAt->timezone('Africa/Lagos')->format('j M Y, g:i a') }}.
                                        </p>
                                    @endif
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
