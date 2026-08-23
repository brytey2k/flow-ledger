{{--
    Reusable inline help tooltip component ("i" icon with hover popup).

    Props:
      $text        — tooltip body text
      $placement   — tooltip placement (default: "top"; currently only "top" is styled)
--}}
@props([
    'text' => '',
    'placement' => 'top',
])

<span class="relative inline-flex align-middle" x-data="{ show: false }"
    @mouseenter="show = true" @mouseleave="show = false" @focus="show = true" @blur="show = false"
    style="cursor: help">
    <x-tabler-info-square-filled class="text-sm leading-none text-muted-foreground" tabindex="0" />
    <span x-show="show" x-cloak class="sgh-tooltip-panel max-w-64 text-left" style="white-space: normal">
        {{ $text }}
    </span>
</span>
