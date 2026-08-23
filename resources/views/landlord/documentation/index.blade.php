@extends('landlord.layouts.app')

@section('title', 'Documentation')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Documentation</h1>
            <p class="text-sm text-secondary-foreground">Landlord and tenant administration documentation will be available here soon.</p>
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card">
            <div class="sgh-card-content py-10">
                <div class="flex flex-col items-center justify-center gap-4 text-center">
                    <x-tabler-device-tablet-question class="text-5xl text-muted-foreground" />
                    <p class="text-sm text-secondary-foreground max-w-lg">
                        Documentation pages for landlord operations are being prepared. Check back soon for account, tenancy, and support guides.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
