@extends('docs.layout')

@section('title', 'Video center')
@section('description', 'Short narrated videos of Flow Ledger: the full walkthrough, each of its parts, and an introduction.')

@php
    $length = static fn (array $video): string => sprintf('%d:%02d', intdiv($video['seconds'], 60), $video['seconds'] % 60);
@endphp

@section('content')
    <main class="mx-auto max-w-[1180px] px-4 py-10 lg:px-8" id="docs-content" tabindex="-1">
        <nav class="text-muted-foreground text-sm" aria-label="Breadcrumb">
            <a class="hover:text-foreground" href="{{ route('landlord.docs') }}">User guide</a>
            <span class="mx-1.5" aria-hidden="true">/</span>
            <span class="text-foreground">Video center</span>
        </nav>

        <header class="mt-5 max-w-2xl">
            <p class="text-primary font-mono text-[11px] font-medium uppercase tracking-[0.2em]">Video center</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Watch it done</h1>
            <p class="text-muted-foreground mt-2">Short narrated videos of Flow Ledger, from setting up approvals to reading the reports.</p>
        </header>

        @if ($walkthrough === null && $parts->isEmpty() && $promos->isEmpty())
            <p class="text-muted-foreground mt-10 text-sm">
                No videos yet. <a class="sgh-link" href="{{ route('landlord.docs') }}">Read the guide</a> in the meantime.
            </p>
        @endif

        @if ($walkthrough !== null)
            <section
                class="sgh-card mt-10 grid scroll-mt-24 items-center gap-6 p-5 md:grid-cols-[2fr_3fr] md:p-6"
                id="video-{{ $walkthrough['key'] }}"
                aria-labelledby="walkthrough-heading"
            >
                <div>
                    <p class="sgh-kpi-label">Full walkthrough · {{ $length($walkthrough) }}</p>
                    <h2 class="mt-2 text-xl font-semibold tracking-tight" id="walkthrough-heading">{{ $walkthrough['title'] }}</h2>
                    <p class="text-muted-foreground mt-2 text-sm leading-relaxed">{{ $walkthrough['description'] }}</p>
                    @if ($parts->isNotEmpty())
                        <p class="text-muted-foreground mt-3 text-sm">Short on time? Each part is below on its own.</p>
                    @endif
                </div>
                @include('docs.partials.video-player', ['video' => $walkthrough])
            </section>
        @endif

        @foreach ([
            ['parts', 'The walkthrough, part by part', $parts],
            ['promos', 'Introducing Flow Ledger', $promos],
        ] as [$group, $heading, $videos])
            @if ($videos->isNotEmpty())
                <section class="mt-12" aria-labelledby="{{ $group }}-heading">
                    <h2 class="text-lg font-semibold" id="{{ $group }}-heading">{{ $heading }}</h2>
                    <div class="mt-4 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($videos->values() as $index => $video)
                            <article class="sgh-card scroll-mt-24 overflow-hidden" id="video-{{ $video['key'] }}" aria-labelledby="video-{{ $video['key'] }}-title">
                                @include('docs.partials.video-player', ['video' => $video, 'flush' => true])
                                <div class="flex flex-col gap-1.5 p-5">
                                    <span class="sgh-kpi-label">
                                        {{ $group === 'parts' ? 'Part ' . ($index + 1) : 'Overview' }} · {{ $length($video) }}
                                    </span>
                                    <h3 class="font-semibold" id="video-{{ $video['key'] }}-title">{{ $video['title'] }}</h3>
                                    <p class="text-muted-foreground text-sm">{{ $video['description'] }}</p>
                                    @php($related = collect($video['chapters'])->filter(fn (string $slug): bool => $chapters->has($slug)))
                                    @if ($related->isNotEmpty())
                                        <p class="text-muted-foreground mt-2 text-sm">
                                            In the guide:
                                            @foreach ($related as $slug)
                                                <a class="sgh-link" href="{{ route('landlord.docs', $slug) }}">{{ $chapters[$slug]['title'] }}</a>@if (! $loop->last), @endif
                                            @endforeach
                                        </p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </main>
@endsection
