<form class="w-full" action="{{ route('landlord.docs') }}" method="GET" role="search">
    <label class="sr-only" for="{{ $inputId ?? 'guide-query' }}">Search the user guide</label>
    <div class="flex gap-2">
        <div class="relative grow">
            <x-tabler-search
                class="text-muted-foreground pointer-events-none absolute start-3 top-1/2 size-4 -translate-y-1/2"
                aria-hidden="true"
            />
            <input
                class="sgh-input ps-9"
                id="{{ $inputId ?? 'guide-query' }}"
                name="q"
                type="search"
                maxlength="100"
                value="{{ $query ?? '' }}"
                placeholder="{{ ($compact ?? false) ? 'Search the guide' : 'Search the guide — try advance, retirement or approval' }}"
            />
        </div>
        @if ($compact ?? false)
            <button class="sgh-btn sgh-btn-primary sgh-btn-icon" type="submit" aria-label="Search">
                <x-tabler-search class="size-4" aria-hidden="true" />
            </button>
        @else
            <button class="sgh-btn sgh-btn-primary" type="submit">Search</button>
        @endif
    </div>
</form>
