{{-- resources/views/backend/layouts/password-expiry-notice.blade.php --}}
@auth
    @php
        $passwordDaysLeft = app(\App\Services\PasswordService::class)->daysUntilExpiry(auth()->user());
        $passwordWarnDays = (int) config('security.password.warn_days', 14);
    @endphp

    @if ($passwordDaysLeft !== null && $passwordDaysLeft >= 0 && $passwordDaysLeft <= $passwordWarnDays && ! request()->routeIs('account.password.*'))
        <div class="container-xxl pt-3">
            <div class="alert alert-warning d-flex align-items-center justify-content-between mb-0" role="alert">
                <span>
                    <i class="fas fa-key me-2"></i>
                    Your password expires in <strong>{{ $passwordDaysLeft }} day(s)</strong>. After that you must change it before you can continue.
                </span>
                <a href="{{ route('account.password.edit') }}" class="btn btn-sm btn-warning ms-3">Change it now</a>
            </div>
        </div>
    @endif
@endauth
