@extends('docs.layout')

@section('title', 'User Guide')
@section('description', 'The Flow Ledger user guide for payment requests, approvals, disbursements, retirements, cash and reports.')

@section('content')
    <main class="mx-auto max-w-[1180px] px-4 py-10 lg:px-8" id="docs-content" tabindex="-1">
        <header class="max-w-2xl">
            <p class="text-primary font-mono text-[11px] font-medium uppercase tracking-[0.2em]">User guide</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Flow Ledger user guide</h1>
            <p class="text-muted-foreground mt-2">Learn a task, check your results, and find help when you need it.</p>
            <div class="mt-6">@include('docs.search', ['query' => $query])</div>
        </header>

        @if ($query === '')
            @if ($walkthrough !== null)
                <section class="sgh-card mt-10 grid items-center gap-6 p-5 md:grid-cols-[2fr_3fr] md:p-6" aria-labelledby="walkthrough-heading">
                    <div>
                        <p class="sgh-kpi-label">
                            Video walkthrough · {{ sprintf('%d:%02d', intdiv($walkthrough['seconds'], 60), $walkthrough['seconds'] % 60) }}
                        </p>
                        <h2 class="mt-2 text-xl font-semibold tracking-tight" id="walkthrough-heading">See Flow Ledger in action</h2>
                        <p class="text-muted-foreground mt-2 text-sm leading-relaxed">{{ $walkthrough['description'] }}</p>
                        <a class="sgh-link mt-4 inline-flex items-center gap-1 text-sm" href="{{ route('landlord.docs.videos') }}">
                            Browse all {{ $videoCount }} videos in the video center
                            <x-tabler-arrow-right class="size-3.5" aria-hidden="true" />
                        </a>
                    </div>
                    @include('docs.partials.video-player', ['video' => $walkthrough])
                </section>
            @endif

            <section class="mt-10" aria-labelledby="start-with-a-task">
                <h2 class="text-lg font-semibold" id="start-with-a-task">Start with a task</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    New here?
                    <a class="sgh-link" href="{{ route('landlord.docs', 'getting-started') }}#signing-in">Start with signing in</a>,
                    or follow an advance from start to finish:
                </p>
                <ol class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ([
                        ['payment-requests', 'creating-a-request', 'Request an advance or an expense', 'Add the line items, attach receipts and submit it for approval.'],
                        ['approval-workflows', 'approving-a-request', 'Approve a request', 'Review a request waiting on you, then approve, send it back or reject it.'],
                        ['retirement-requests', 'creating-a-retirement', 'Retire an advance', 'Record what was actually spent and settle the difference.'],
                    ] as [$slug, $anchor, $title, $summary])
                        <li>
                            <a
                                class="sgh-card hover:border-primary/60 hover:bg-accent/40 flex h-full flex-col gap-2 p-5 transition-colors"
                                href="{{ route('landlord.docs', $slug) }}#{{ $anchor }}"
                            >
                                <span class="sgh-kpi-label">Step {{ $loop->iteration }}</span>
                                <span class="font-semibold">{{ $title }}</span>
                                <span class="text-muted-foreground text-sm">{{ $summary }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        <section class="mt-10" aria-labelledby="chapters-heading">
            @if ($query === '')
                <h2 class="text-lg font-semibold" id="chapters-heading">Browse all chapters</h2>
            @else
                <h2 class="text-lg font-semibold" id="chapters-heading">Search results for “{{ $query }}”</h2>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ $chapters->count() }} {{ $chapters->count() === 1 ? 'chapter' : 'chapters' }} found.
                    <a class="sgh-link" href="{{ route('landlord.docs') }}">Browse all chapters</a>
                </p>
            @endif

            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($chapters as $slug => $chapter)
                    <a
                        class="sgh-card hover:border-primary/60 hover:bg-accent/40 flex flex-col gap-1.5 p-5 transition-colors"
                        href="{{ route('landlord.docs', $slug) }}"
                    >
                        <span class="sgh-kpi-label">{{ $query === '' ? 'Chapter ' . $loop->iteration : 'Guide' }}</span>
                        <span class="font-semibold">{{ $chapter['title'] }}</span>
                        <span class="text-muted-foreground text-sm">{{ $chapter['description'] }}</span>
                    </a>
                @empty
                    <p class="text-muted-foreground text-sm sm:col-span-2 lg:col-span-3">
                        No chapters match. Try a shorter phrase such as “advance”, “approval” or “cash”.
                    </p>
                @endforelse
            </div>
        </section>
    </main>
@endsection
