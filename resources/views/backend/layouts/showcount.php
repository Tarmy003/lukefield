@auth
    @php $unreadCount = auth()->user()->unreadNotifications->count(); @endphp
    @if($unreadCount > 0)
        <span class="badge bg-danger">{{ $unreadCount }}</span>
    @endif
@endauth