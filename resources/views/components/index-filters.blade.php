@props([
    'action',
    'resetUrl',
    'filters' => [],
    'searchPlaceholder' => null,
])

<form method="GET" action="{{ $action }}" class="sgh-card mb-5 p-4 lg:p-5">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="flex flex-col gap-1 lg:col-span-2">
            <label for="q" class="sgh-form-label">{{ __('common.search') }}</label>
            <div class="relative">
                <x-tabler-search class="pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-muted-foreground" />
                <input
                    id="q"
                    name="q"
                    type="search"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="{{ $searchPlaceholder ?? __('common.search_placeholder') }}"
                    class="sgh-input w-full ps-10"
                >
            </div>
        </div>

        {{ $slot }}
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2">
        <button type="submit" class="sgh-btn sgh-btn-primary">
            <x-tabler-adjustments-horizontal />
            {{ __('common.apply_filters') }}
        </button>
        <a href="{{ $resetUrl }}" class="sgh-btn sgh-btn-light">{{ __('common.clear_filters') }}</a>
    </div>
</form>
