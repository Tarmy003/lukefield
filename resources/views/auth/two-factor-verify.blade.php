{{-- resources/views/auth/two-factor-verify.blade.php --}}
<!DOCTYPE html>
<html lang="en" dir="ltr" data-startbar="dark" data-bs-theme="light">
<head>
    <meta charset="utf-8" />
    <title>Two-Factor Verification - Lukefield Finance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="{{ asset('backend/logo.jpg') }}">
    <link href="{{ asset('backend/assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    
    <style>
        .code-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 10px;
            font-weight: bold;
        }
        .timer {
            font-size: 18px;
            font-weight: bold;
            color: #dc3545;
        }
        .timer.warning {
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
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
                                        <h4 class="mt-3 mb-1 fw-semibold text-black fs-18">
                                            <i class="fas fa-shield-alt text-primary me-2"></i>Two-Factor Authentication
                                        </h4>   
                                        <p class="text-muted fw-medium mb-0">Enter the 6-digit code sent to your email</p>  
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

                                    <div class="alert alert-info mb-3" role="alert">
                                        <i class="fas fa-envelope me-2"></i>
                                        Code sent to: <strong>{{ $email }}</strong>
                                    </div>

                                    <form method="POST" action="{{ route('2fa.verify') }}" class="my-4" id="verifyForm">
                                        @csrf

                                        <div class="form-group mb-3">
                                            <label class="form-label" for="code">
                                                <i class="fas fa-key me-1"></i>Verification Code
                                            </label>
                                            <input type="text" name="code" id="code"
                                                class="form-control code-input @error('code') is-invalid @enderror"
                                                maxlength="6" required autofocus
                                                placeholder="000000"
                                                pattern="[0-9]{6}">

                                            @error('code')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="text-center mb-3">
                                            <p class="mb-2">Code expires in:</p>
                                            <div class="timer" id="countdown">02:00</div>
                                        </div>

                                        <div class="form-group mb-0 row">
                                            <div class="col-12">
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-primary" id="verifyBtn">
                                                        <i class="fas fa-check me-2"></i>Verify & Login
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="text-center">
                                        <form method="POST" action="{{ route('2fa.resend') }}" id="resendForm">
                                            @csrf
                                            <p class="text-muted mb-2">Didn't receive the code?</p>
                                            <button type="submit" class="btn btn-outline-secondary btn-sm" id="resendBtn">
                                                <i class="fas fa-redo me-1"></i>Resend Code
                                            </button>
                                        </form>
                                    </div>

                                    <hr class="my-3">

                                    <div class="text-center">
                                        <a href="{{ route('login') }}" class="text-muted">
                                            <i class="fas fa-arrow-left me-1"></i>Back to Login
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>                                        
    </div>

    <script>
        // Countdown timer
        let timeLeft = 120; // 2 minutes
        const countdownEl = document.getElementById('countdown');
        const verifyBtn = document.getElementById('verifyBtn');
        const resendBtn = document.getElementById('resendBtn');
        const verifyForm = document.getElementById('verifyForm');

        const timer = setInterval(() => {
            timeLeft--;
            
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            
            countdownEl.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            // Add warning class when less than 30 seconds
            if (timeLeft <= 30) {
                countdownEl.classList.add('warning');
            }
            
            // Disable form when expired
            if (timeLeft <= 0) {
                clearInterval(timer);
                countdownEl.textContent = 'EXPIRED';
                verifyBtn.disabled = true;
                verifyBtn.innerHTML = '<i class="fas fa-times me-2"></i>Code Expired';
                document.getElementById('code').disabled = true;
                
                alert('Verification code has expired. Please request a new code.');
            }
        }, 1000);

        // Auto-submit on 6 digits
        document.getElementById('code').addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            // Auto-submit when 6 digits entered
            if (this.value.length === 6) {
                setTimeout(() => {
                    verifyForm.submit();
                }, 500);
            }
        });

        // Resend button cooldown
        resendBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sending...';
            
            setTimeout(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-redo me-1"></i>Resend Code';
            }, 60000); // 1 minute cooldown
        });
    </script>
</body>
</html>