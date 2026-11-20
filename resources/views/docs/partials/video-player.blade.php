{{--
    A YouTube video behind our own poster. Nothing is loaded from YouTube until the visitor presses play, so a page with
    several videos stays fast and shares no visit with Google until one is watched. The embed uses YouTube's
    privacy-enhanced domain.

    @param array{key: string, title: string, youtube: string, seconds: int} $video
    @param \App\Support\UserGuide $guide
    @param bool $autoplay  Start at once, for a player that only appears after the visitor asked to watch.
    @param bool $flush  Drop the player's own border and rounding, for a player at the top of a card.
--}}
@php
    $length = sprintf('%d:%02d', intdiv($video['seconds'], 60), $video['seconds'] % 60);
    $poster = $guide->posterUrl($video['key']);
    $embed = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($video['youtube']) . '?autoplay=1&rel=0&playsinline=1';
@endphp
<div @class(['docs-video-player', 'rounded-none border-0' => $flush ?? false]) x-data="{ playing: {{ ($autoplay ?? false) ? 'true' : 'false' }} }">
    <template x-if="playing">
        <iframe
            src="{{ $embed }}"
            title="{{ $video['title'] }}"
            allow="autoplay; encrypted-media; picture-in-picture; fullscreen"
            allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"
        ></iframe>
    </template>
    <button
        type="button"
        class="absolute inset-0 size-full cursor-pointer"
        x-show="! playing"
        @click="playing = true"
        aria-label="Play video: {{ $video['title'] }} ({{ $length }})"
    >
        @if ($poster !== null)
            <img src="{{ $poster }}" alt="" loading="lazy" width="1280" height="720" />
        @else
            <span class="text-muted-foreground absolute inset-0 flex items-center justify-center p-6 text-center font-semibold">{{ $video['title'] }}</span>
        @endif
        <span class="docs-video-play" aria-hidden="true">
            <x-tabler-player-play-filled class="size-4" /> Play <span class="opacity-75">{{ $length }}</span>
        </span>
    </button>
</div>
