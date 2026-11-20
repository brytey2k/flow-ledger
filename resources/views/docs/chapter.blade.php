@extends('docs.layout')

@section('title', $chapter['title'])
@section('description', $chapter['description'])

@php
    $previous = $chapters->keys()->before($slug);
    $next = $chapters->keys()->after($slug);
    $position = $chapters->keys()->search($slug) + 1;
@endphp

@section('content')
    <div class="mx-auto flex max-w-[1440px] items-start">
        <aside class="border-border sticky top-16 hidden max-h-[calc(100vh-4rem)] w-[280px] shrink-0 overflow-y-auto border-e px-4 py-6 lg:block">
            <nav aria-label="Guide chapters">
                @include('docs.search', ['compact' => true])
                <p class="sgh-nav-heading mt-2">Guide chapters</p>
                <ol class="space-y-0.5">
                    @foreach ($chapters as $key => $item)
                        <li>
                            <a
                                class="sgh-nav-item"
                                href="{{ route('landlord.docs', $key) }}"
                                @if ($key === $slug) aria-current="page" @endif
                            >{{ $item['title'] }}</a>
                        </li>
                    @endforeach
                </ol>
                @if ($guide->videos()->isNotEmpty())
                    <p class="sgh-nav-heading mt-2">Watch</p>
                    <a class="sgh-nav-item" href="{{ route('landlord.docs.videos') }}">
                        <x-tabler-movie class="sgh-nav-icon size-4" aria-hidden="true" /> Video center
                    </a>
                @endif
            </nav>
        </aside>

        <main class="min-w-0 grow px-4 py-8 lg:px-10" id="docs-content" tabindex="-1">
            <div class="mx-auto max-w-[760px]">
                <nav class="text-muted-foreground text-sm" aria-label="Breadcrumb">
                    <a class="hover:text-foreground" href="{{ route('landlord.docs') }}">User guide</a>
                    <span class="mx-1.5" aria-hidden="true">/</span>
                    <span class="text-foreground">{{ $chapter['title'] }}</span>
                </nav>

                <header class="mt-5">
                    <p class="text-primary font-mono text-[11px] font-medium uppercase tracking-[0.2em]">
                        Chapter {{ $position }} of {{ $chapters->count() }}
                    </p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $chapter['title'] }}</h1>
                    <p class="text-muted-foreground mt-2 text-[17px] leading-relaxed">{{ $chapter['description'] }}</p>
                </header>

                <details class="sgh-card mt-6 lg:hidden">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-medium">All guide chapters</summary>
                    <div class="border-border border-t px-2 py-3">
                        <div class="px-2 pb-3">
                            @include('docs.search', ['compact' => true, 'inputId' => 'guide-query-mobile'])
                        </div>
                        <ol class="space-y-0.5">
                            @foreach ($chapters as $key => $item)
                                <li>
                                    <a
                                        class="sgh-nav-item"
                                        href="{{ route('landlord.docs', $key) }}"
                                        @if ($key === $slug) aria-current="page" @endif
                                    >{{ $item['title'] }}</a>
                                </li>
                            @endforeach
                        </ol>
                        @if ($guide->videos()->isNotEmpty())
                            <a class="sgh-nav-item mt-2" href="{{ route('landlord.docs.videos') }}">
                                <x-tabler-movie class="sgh-nav-icon size-4" aria-hidden="true" /> Video center
                            </a>
                        @endif
                    </div>
                </details>

                @if ($sections !== [])
                    <details class="sgh-card mt-3 xl:hidden" open>
                        <summary class="cursor-pointer px-4 py-3 text-sm font-medium">In this chapter</summary>
                        <nav class="border-border border-t px-4 py-3" aria-label="Chapter sections">
                            <ul class="space-y-1.5 text-sm">
                                @foreach ($sections as $id => $label)
                                    <li><a class="sgh-link font-normal" href="#{{ $id }}">{{ $label }}</a></li>
                                @endforeach
                            </ul>
                        </nav>
                    </details>
                @endif

                @foreach ($videos as $video)
                    @php($length = sprintf('%d:%02d', intdiv($video['seconds'], 60), $video['seconds'] % 60))
                    <section class="mt-6" aria-label="Video: {{ $video['title'] }}" x-data="{ watching: false }">
                        <template x-if="watching">
                            <div>
                                @include('docs.partials.video-player', ['video' => $video, 'autoplay' => true])
                                <p class="text-muted-foreground mt-2.5 text-[13px]">
                                    <strong class="text-foreground font-medium">{{ $video['title'] }}</strong> · {{ $length }} ·
                                    <a class="sgh-link" href="{{ route('landlord.docs.videos') }}#video-{{ $video['key'] }}">All videos</a>
                                </p>
                            </div>
                        </template>
                        <button
                            type="button"
                            class="sgh-card hover:border-primary/60 hover:bg-accent/40 group flex w-full items-center gap-4 p-3 text-start transition-colors"
                            x-show="! watching"
                            @click="watching = true"
                        >
                            <span class="bg-muted relative aspect-video w-32 shrink-0 overflow-hidden rounded-lg sm:w-40">
                                @if (($poster = $guide->posterUrl($video['key'])) !== null)
                                    <img class="size-full object-cover" src="{{ $poster }}" alt="" loading="lazy" width="1280" height="720" />
                                @endif
                                <span class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                                    <span class="bg-black/70 group-hover:bg-primary flex size-9 items-center justify-center rounded-full text-white transition-colors">
                                        <x-tabler-player-play-filled class="size-4" />
                                    </span>
                                </span>
                            </span>
                            <span class="flex min-w-0 flex-col gap-1">
                                <span class="sgh-kpi-label">
                                    {{ $video['kind'] === 'walkthrough' ? 'Watch the walkthrough' : 'Watch the video' }} · {{ $length }}
                                </span>
                                <span class="font-semibold">{{ $video['title'] }}</span>
                                <span class="text-muted-foreground text-sm">{{ $video['description'] }}</span>
                            </span>
                        </button>
                    </section>
                @endforeach

                <article class="docs-prose mt-8" x-data="docsScreenshots" @click="open($event)">
                    {!! $html !!}

                    <template x-teleport="body">
                        <div
                            class="fixed inset-0 z-50 flex flex-col bg-black/80 p-4 backdrop-blur-sm sm:p-8"
                            x-show="current !== null"
                            x-transition.opacity
                            x-cloak
                            role="dialog"
                            aria-modal="true"
                            :aria-label="images[current]?.alt"
                            @keydown.escape.window="close()"
                            @keydown.arrow-left.window="step(-1)"
                            @keydown.arrow-right.window="step(1)"
                            @click.self="close()"
                        >
                            <div class="flex items-center justify-between gap-4 text-sm text-white/80">
                                <span x-text="images.length > 1 ? `${current + 1} of ${images.length}` : ''"></span>
                                <button
                                    type="button"
                                    class="rounded-[var(--radius)] p-2 hover:bg-white/10"
                                    @click="close()"
                                    aria-label="Close"
                                    x-ref="close"
                                >
                                    <x-tabler-x class="size-5" aria-hidden="true" />
                                </button>
                            </div>
                            <div class="flex min-h-0 grow items-center justify-center gap-2" @click.self="close()">
                                <button
                                    type="button"
                                    class="shrink-0 rounded-full p-2 text-white/80 hover:bg-white/10 disabled:invisible"
                                    @click="step(-1)"
                                    :disabled="current === 0"
                                    x-show="images.length > 1"
                                    aria-label="Previous screenshot"
                                >
                                    <x-tabler-chevron-left class="size-6" aria-hidden="true" />
                                </button>
                                <img
                                    class="max-h-full min-w-0 rounded-lg bg-white object-contain shadow-2xl"
                                    :src="images[current]?.src"
                                    :alt="images[current]?.alt"
                                />
                                <button
                                    type="button"
                                    class="shrink-0 rounded-full p-2 text-white/80 hover:bg-white/10 disabled:invisible"
                                    @click="step(1)"
                                    :disabled="current === images.length - 1"
                                    x-show="images.length > 1"
                                    aria-label="Next screenshot"
                                >
                                    <x-tabler-chevron-right class="size-6" aria-hidden="true" />
                                </button>
                            </div>
                            <p class="mx-auto mt-3 max-w-3xl text-center text-sm text-white/80" x-text="images[current]?.caption"></p>
                        </div>
                    </template>
                </article>

                <nav class="mt-14 grid gap-4 sm:grid-cols-2" aria-label="Chapter navigation">
                    @if ($previous !== null)
                        <a
                            class="sgh-card hover:border-primary/60 hover:bg-accent/40 flex flex-col gap-1 p-4 transition-colors"
                            href="{{ route('landlord.docs', $previous) }}"
                        >
                            <span class="sgh-kpi-label flex items-center gap-1">
                                <x-tabler-arrow-left class="size-3" aria-hidden="true" /> Previous
                            </span>
                            <span class="font-medium">{{ $chapters[$previous]['title'] }}</span>
                        </a>
                    @endif
                    @if ($next !== null)
                        <a
                            class="sgh-card hover:border-primary/60 hover:bg-accent/40 flex flex-col items-end gap-1 p-4 text-end transition-colors sm:col-start-2"
                            href="{{ route('landlord.docs', $next) }}"
                        >
                            <span class="sgh-kpi-label flex items-center gap-1">
                                Next <x-tabler-arrow-right class="size-3" aria-hidden="true" />
                            </span>
                            <span class="font-medium">{{ $chapters[$next]['title'] }}</span>
                        </a>
                    @endif
                </nav>
            </div>
        </main>

        @if ($sections !== [])
            <aside class="sticky top-16 hidden max-h-[calc(100vh-4rem)] w-[240px] shrink-0 overflow-y-auto py-8 pe-6 xl:block">
                <nav aria-label="On this page">
                    <p class="sgh-kpi-label">In this chapter</p>
                    <ul class="border-border mt-3 space-y-2 border-s text-sm">
                        @foreach ($sections as $id => $label)
                            <li>
                                <a
                                    class="text-muted-foreground hover:text-foreground hover:border-primary -ms-px block border-s border-transparent ps-3"
                                    href="#{{ $id }}"
                                >{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        @endif
    </div>
@endsection
