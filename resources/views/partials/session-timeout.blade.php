@php
    $lifetimeSeconds = max(60, (int) config('session.lifetime') * 60);
    $warnSeconds = min(120, intdiv($lifetimeSeconds, 2));
@endphp
<div id="session-timeout-warning" role="alertdialog" aria-modal="true" aria-labelledby="session-timeout-title" hidden
     style="position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.5);padding:1rem;">
    <div style="max-width:24rem;width:100%;border-radius:.75rem;padding:1.5rem;background:var(--color-background,#fff);color:var(--color-foreground,#111);box-shadow:0 10px 40px rgba(0,0,0,.3);">
        <h2 id="session-timeout-title" style="font-size:1.125rem;font-weight:600;margin:0 0 .5rem;">{{ __('auth.session_expiring_title') }}</h2>
        <p style="margin:0 0 1.25rem;font-size:.9rem;">
            {{ __('auth.session_expiring_body') }}
            <strong id="session-timeout-countdown">{{ $warnSeconds }}</strong>s
        </p>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;">
            <a href="{{ $loginUrl }}" class="sgh-btn sgh-btn-outline">{{ __('auth.sign_out_now') }}</a>
            <button type="button" id="session-timeout-stay" class="sgh-btn sgh-btn-primary">{{ __('auth.stay_signed_in') }}</button>
        </div>
    </div>
</div>
<script>
    (function () {
        const lifetimeMs = {{ $lifetimeSeconds }} * 1000;
        const warnMs = {{ $warnSeconds }} * 1000;
        const keepAliveUrl = @json($keepAliveUrl);
        const loginUrl = @json($loginUrl);
        const dialog = document.getElementById('session-timeout-warning');
        const countdown = document.getElementById('session-timeout-countdown');
        let warnTimer = null;
        let tickTimer = null;

        function schedule() {
            clearTimeout(warnTimer);
            clearInterval(tickTimer);
            dialog.style.display = 'none';
            warnTimer = setTimeout(showWarning, lifetimeMs - warnMs);
        }

        function showWarning() {
            let remaining = Math.round(warnMs / 1000);
            countdown.textContent = remaining;
            dialog.style.display = 'flex';
            tickTimer = setInterval(function () {
                remaining -= 1;
                countdown.textContent = Math.max(remaining, 0);
                if (remaining <= 0) {
                    window.location.href = loginUrl;
                }
            }, 1000);
        }

        document.getElementById('session-timeout-stay').addEventListener('click', function () {
            fetch(keepAliveUrl, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
                .then(function (response) {
                    if (response.ok) {
                        schedule();
                    } else {
                        window.location.href = loginUrl;
                    }
                })
                .catch(function () {
                    window.location.href = loginUrl;
                });
        });

        schedule();
    })();
</script>
