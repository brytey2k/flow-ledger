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
<div class="shrink-0" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px" data-kt-dropdown-offset-rtl="-20px, 10px"
    data-kt-dropdown-placement="bottom-end" data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
    <button type="button" class="fl-btn fl-btn-outline fl-btn-sm gap-1.5" data-kt-dropdown-toggle="true"
        aria-label="{{ __('navigation.language') }}">
        <img alt="" class="inline-block size-4 rounded-full" src="{{ asset('assets/media/flags/'.$currentLocaleFlag) }}" />
        <span class="hidden sm:inline">{{ $currentLocale['native_label'] ?? $currentLocaleCode }}</span>
        <x-tabler-chevron-down-filled class="text-2xs" />
    </button>
    <div class="kt-dropdown-menu w-[180px]" data-kt-dropdown-menu="true">
        <ul class="kt-dropdown-menu-sub">
            @foreach ($supportedLocales as $code => $locale)
                <li class="{{ $currentLocaleCode === $code ? 'active' : '' }}">
                    <button type="submit" form="header-locale-form" name="locale" value="{{ $code }}"
                        class="kt-dropdown-menu-link w-full text-start">
                        <span class="flex items-center gap-2">
                            <img alt="" class="inline-block size-4 rounded-full"
                                src="{{ asset('assets/media/flags/'.($localeFlags[$code] ?? 'united-nations.svg')) }}" />
                            <span class="kt-menu-title">{{ $locale['native_label'] ?? $code }}</span>
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
