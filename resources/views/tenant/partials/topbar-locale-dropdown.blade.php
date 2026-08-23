<!-- Locale -->
@php
    $localeFlags = [
        'en' => 'united-states.svg',
        'fr' => 'france.svg',
    ];
    $supportedLocales = config('locales.supported', []);
    $currentLocaleCode = app()->getLocale();
    $currentLocale = $supportedLocales[$currentLocaleCode] ?? reset($supportedLocales);
    $currentLocaleFlag = $localeFlags[$currentLocaleCode] ?? 'united-nations.svg';
@endphp
<form id="header-locale-form" method="POST" action="{{ route('locale.update') }}" class="hidden">
    @csrf
</form>
<div class="relative shrink-0" x-data="dropdown">
    <button type="button" class="sgh-btn sgh-btn-outline sgh-btn-sm gap-1.5" @click="toggle" :aria-expanded="open"
        aria-label="{{ __('navigation.language') }}">
        <img alt="" class="inline-block size-4 rounded-full" src="{{ asset('assets/media/flags/'.$currentLocaleFlag) }}" />
        <span class="hidden sm:inline">{{ $currentLocale['native_label'] ?? $currentLocaleCode }}</span>
        <x-tabler-chevron-down-filled class="text-2xs" />
    </button>
    <div x-show="open" x-cloak @click.outside="close()" class="sgh-dropdown-panel w-[180px]">
        <ul>
            @foreach ($supportedLocales as $code => $locale)
                <li>
                    <button type="submit" form="header-locale-form" name="locale" value="{{ $code }}"
                        class="sgh-dropdown-item {{ $currentLocaleCode === $code ? 'bg-accent' : '' }}">
                        <span class="flex items-center gap-2">
                            <img alt="" class="inline-block size-4 rounded-full"
                                src="{{ asset('assets/media/flags/'.($localeFlags[$code] ?? 'united-nations.svg')) }}" />
                            <span>{{ $locale['native_label'] ?? $code }}</span>
                        </span>
                        @if ($currentLocaleCode === $code)
                            <x-tabler-circle-check-filled class="ms-auto text-base text-green-500" />
                        @endif
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</div>
<!-- End of Locale -->
