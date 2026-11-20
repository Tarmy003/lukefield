{{-- resources/views/auth/partials/alerts.blade.php --}}
@if (session('success'))
    <div class="alert alert-success mt-2" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    </div>
@endif

@if (session('status'))
    <div class="alert alert-success mt-2" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('status') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger mt-2" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
    </div>
@endif
