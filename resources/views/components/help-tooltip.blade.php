{{--
    Reusable inline help tooltip component ("i" icon with hover popup).

    Props:
      $text        — tooltip body text
      $placement   — kt tooltip placement (default: "top")
--}}
@props([
    'text' => '',
    'placement' => 'top',
])

<span class="inline-flex align-middle" data-kt-tooltip="true" data-kt-tooltip-placement="{{ $placement }}" style="cursor: help">
    <x-tabler-info-square-filled class="text-sm leading-none text-muted-foreground" />
    <span class="kt-tooltip hidden max-w-64 text-left" data-kt-tooltip-content style="white-space: normal">
        {{ $text }}
    </span>
</span>
