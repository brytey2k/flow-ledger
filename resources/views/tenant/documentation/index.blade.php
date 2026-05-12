@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('documentation.title') }}</h1>
            <p class="text-sm text-secondary-foreground">{{ __('documentation.subtitle') }}</p>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-content py-10">
                <div class="flex flex-col items-center justify-center gap-4 text-center">
                    <i class="ki-filled ki-questionnaire-tablet text-5xl text-muted-foreground"></i>
                    <p class="text-sm text-secondary-foreground max-w-lg">
                        {{ __('documentation.body') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
