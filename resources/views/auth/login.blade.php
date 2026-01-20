{{-- resources/views/auth/login.blade.php --}}
<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="dark" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Login - Lukefield Finance Archive</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Secure Archive Repository System" name="description" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="{{ asset('backend/logo.jpg') }}">
    <link href="{{ asset('backend/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
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
                                        <a href="/" class="logo logo-admin">
                                            <img src="{{ asset('backend/lukefield-logo-removebg-preview.png') }}" 
                                                 height="50" alt="logo" class="auth-logo">
                                        </a>
                                        <h4 class="mt-3 mb-1 fw-semibold text-black fs-18">Secure Archive Login</h4>   
                                        <p class="text-muted fw-medium mb-0">Enter your credentials to access the system</p>  
                                    </div>
                                </div>
                                <div class="card-body pt-0">     

                                    @if (session('success'))
                                        <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
                                            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    @if (session('error'))
                                        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
                                            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('login.post') }}" class="my-4">
                                        @csrf

                                        <div class="form-group mb-3">
                                            <label class="form-label" for="email">
                                                <i class="fas fa-envelope me-1"></i>Email Address
                                            </label>
                                            <input type="email" name="email" id="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                value="{{ old('email') }}" required autofocus
                                                placeholder="Enter your email address">

                                            @error('email')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="form-label" for="password">
                                                <i class="fas fa-lock me-1"></i>Password
                                            </label>
                                            <input type="password" name="password" id="password"
                                                class="form-control @error('password') is-invalid @enderror"
                                                required placeholder="Enter your password">

                                            @error('password')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="form-group row mt-3">
                                            <div class="col-sm-6">
                                                <div class="form-check form-switch form-switch-success">
                                                    <input class="form-check-input" type="checkbox" 
                                                           id="remember" name="remember">
                                                    <label class="form-check-label" for="remember">Remember me</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-0 row">
                                            <div class="col-12">
                                                <div class="d-grid mt-3">
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="fas fa-shield-alt me-2"></i>Proceed to Verification
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="alert alert-info mb-2" role="alert">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Security Notice:</strong> A verification code will be sent to your email after credential validation.
                                    </div>

                                    <div class="text-center mb-2">
                                        <p class="text-muted">
                                            <i class="fas fa-lock me-1"></i>
                                            Protected by 2-Factor Authentication
                                        </p>
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