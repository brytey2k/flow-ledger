@props(['input' => 'password', 'confirm' => null])

@php
    $listId = 'pw-req-' . $input;
    $requirements = [
        'length' => __('auth.pw_req_length', ['count' => 12]),
        'lower' => __('auth.pw_req_lower'),
        'upper' => __('auth.pw_req_upper'),
        'number' => __('auth.pw_req_number'),
        'symbol' => __('auth.pw_req_symbol'),
    ];
    if ($confirm !== null) {
        $requirements['match'] = __('auth.pw_req_match');
    }
@endphp

<div {{ $attributes->merge(['class' => 'mt-2']) }}>
    <p class="mb-1 text-xs font-medium text-secondary-foreground">{{ __('auth.pw_req_title') }}</p>
    <ul id="{{ $listId }}" class="flex flex-col gap-1" aria-live="polite">
        @foreach ($requirements as $key => $label)
            <li data-req="{{ $key }}" class="flex items-center gap-2 text-xs text-muted-foreground">
                <span data-icon aria-hidden="true" style="display:inline-block;width:1rem;text-align:center;font-weight:700;">○</span>
                <span>{{ $label }}</span>
                <span class="sr-only" data-status>{{ __('auth.pw_req_not_met') }}</span>
            </li>
        @endforeach
    </ul>
</div>

<script>
    (function () {
        const list = document.getElementById(@json($listId));
        const input = document.getElementById(@json($input));
        const confirmId = @json($confirm);
        if (!list || !input) {
            return;
        }
        // The confirmation field sits after this script in the page, so it is resolved lazily.
        const confirmInput = () => (confirmId ? document.getElementById(confirmId) : null);
        const met = @json(__('auth.pw_req_met'));
        const notMet = @json(__('auth.pw_req_not_met'));

        const rules = {
            length: (v) => Array.from(v).length >= 12,
            lower: (v) => /\p{Ll}/u.test(v),
            upper: (v) => /\p{Lu}/u.test(v),
            number: (v) => /\p{N}/u.test(v),
            symbol: (v) => /[\p{P}\p{S}\p{Z}]/u.test(v),
            match: (v) => v.length > 0 && confirmInput() !== null && v === confirmInput().value,
        };

        function render() {
            list.querySelectorAll('li[data-req]').forEach(function (item) {
                const ok = rules[item.dataset.req](input.value);
                item.querySelector('[data-icon]').textContent = input.value === '' ? '○' : (ok ? '✓' : '✕');
                item.classList.toggle('text-success', ok);
                item.classList.toggle('text-destructive', !ok && input.value !== '');
                item.classList.toggle('text-muted-foreground', !ok && input.value === '');
                item.querySelector('[data-status]').textContent = ok ? met : notMet;
            });
        }

        input.addEventListener('input', render);
        document.addEventListener('input', function (event) {
            if (confirmId && event.target && event.target.id === confirmId) {
                render();
            }
        });
        render();
    })();
</script>
