{{-- resources/views/backend/layouts/session-timeout.blade.php --}}
{{-- Warns before the inactivity sign-out and lets the user stay signed in.
     The server enforces the timeout regardless (EnforceIdleTimeout). --}}
@auth
    <div class="modal fade" id="idleTimeoutModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        aria-labelledby="idleTimeoutTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="idleTimeoutTitle"><i class="fas fa-clock me-2"></i>Are you still there?</h5>
                </div>
                <div class="modal-body">
                    For security, you will be signed out in <strong id="idleTimeoutCountdown">2:00</strong>
                    because there has been no activity.
                </div>
                <div class="modal-footer">
                    <form method="POST" action="{{ route('logout') }}" class="m-0" id="idleTimeoutLogoutForm">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary">Sign out now</button>
                    </form>
                    <button type="button" class="btn btn-primary" id="idleTimeoutStay">Stay signed in</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var timeoutMs = {{ (int) config('security.idle_timeout', 15) * 60 * 1000 }};
            var warningMs = {{ (int) config('security.idle_warning_seconds', 120) * 1000 }};
            var pingUrl = @json(route('session.ping'));
            var loginUrl = @json(route('login'));
            var csrf = document.querySelector('meta[name="csrf-token"]').content;

            var lastServerContact = Date.now();
            var modal = null;
            var tick = null;

            function modalInstance() {
                if (!modal && window.bootstrap) {
                    modal = new bootstrap.Modal(document.getElementById('idleTimeoutModal'));
                }
                return modal;
            }

            function format(ms) {
                var total = Math.max(0, Math.ceil(ms / 1000));
                return Math.floor(total / 60) + ':' + String(total % 60).padStart(2, '0');
            }

            function check() {
                var remaining = timeoutMs - (Date.now() - lastServerContact);

                if (remaining <= 0) {
                    clearInterval(tick);
                    // The server has already ended the session; reloading shows the sign-in page.
                    window.location.href = loginUrl;
                    return;
                }

                if (remaining <= warningMs) {
                    document.getElementById('idleTimeoutCountdown').textContent = format(remaining);
                    var instance = modalInstance();
                    if (instance && !document.getElementById('idleTimeoutModal').classList.contains('show')) {
                        instance.show();
                    }
                }
            }

            document.getElementById('idleTimeoutStay').addEventListener('click', function () {
                fetch(pingUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    credentials: 'same-origin'
                }).then(function (response) {
                    if (!response.ok) {
                        window.location.href = loginUrl;
                        return;
                    }
                    lastServerContact = Date.now();
                    var instance = modalInstance();
                    if (instance) { instance.hide(); }
                }).catch(function () {
                    window.location.href = loginUrl;
                });
            });

            // Requests made by the page itself (searches, statements) also reset the server timer.
            var originalFetch = window.fetch;
            window.fetch = function () {
                lastServerContact = Date.now();
                return originalFetch.apply(this, arguments);
            };
            if (window.jQuery) {
                jQuery(document).ajaxSend(function () { lastServerContact = Date.now(); });
            }

            tick = setInterval(check, 1000);
        })();
    </script>
@endauth
