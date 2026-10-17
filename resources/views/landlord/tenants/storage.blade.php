@extends('landlord.layouts.base')

@section('content')
@include('landlord.tenants.partials.admin-nav')
<div class="sgh-container-fixed">
    <div class="flex flex-col gap-2 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Storage</h1>
        <p class="text-sm text-secondary-foreground">Attachment usage, files on disk that nothing references, and attachment records whose file is gone.</p>
    </div>
</div>

@php
    $format = fn (int $bytes) => $bytes >= 1048576 ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024, 1) . ' KB';
@endphp
<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card">
            <div class="sgh-card-header"><h3 class="sgh-card-title">Usage</h3></div>
            <div class="sgh-card-content grid gap-2 text-sm">
                @forelse($report['usage'] as $row)
                    <div class="flex justify-between"><span>{{ $row['type'] }} attachments</span><span class="font-medium">{{ number_format($row['files']) }} files &middot; {{ $format($row['bytes']) }}</span></div>
                @empty
                    <span class="text-muted-foreground">No attachments.</span>
                @endforelse
            </div>
        </div>

        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Orphaned files ({{ count($report['orphaned_files']) }}, {{ $format($report['orphaned_bytes']) }})</h3>
                <p class="sgh-card-subtitle">Files on disk with no attachment record. Deleting them is permanent.</p>
            </div>
            <div class="sgh-card-content grid gap-4">
                @foreach(array_slice($report['orphaned_files'], 0, 50) as $path)
                    <span class="break-all text-2sm">{{ $path }}</span>
                @endforeach
                @if(count($report['orphaned_files']) > 50)
                    <span class="text-2sm text-muted-foreground">…and {{ count($report['orphaned_files']) - 50 }} more.</span>
                @endif
                @if(count($report['orphaned_files']) > 0)
                    <form method="POST" action="{{ route('landlord.tenants.storage.cleanup', $tenant) }}">
                        @csrf
                        <button class="sgh-btn sgh-btn-danger" type="submit">Delete orphaned files</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Missing files ({{ count($report['missing_files']) }})</h3>
                <p class="sgh-card-subtitle">Attachment records whose file is not on disk. These need a manual decision, so nothing is changed automatically.</p>
            </div>
            <div class="sgh-card-content grid gap-2">
                @foreach(array_slice($report['missing_files'], 0, 50) as $path)
                    <span class="break-all text-2sm">{{ $path }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
