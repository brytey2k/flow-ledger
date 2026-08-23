@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cost_codes.import_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('cost_codes.import_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('cost-codes.import.template') }}">
                <x-tabler-file-arrow-right />
                {{ __('cost_codes.buttons.download_sample') }}
            </a>
            <a class="sgh-btn sgh-btn-light" href="{{ route('cost-codes.index') }}">
                <x-tabler-arrow-left />
                {{ __('cost_codes.back') }}
            </a>
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('cost_codes.sample_card') }}</h3>
            </div>
            <div class="sgh-card-content">
                <p class="text-sm text-secondary-foreground">
                    {{ __('cost_codes.import_notes') }}
                </p>
            </div>
        </div>

        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('cost_codes.import_card') }}</h3>
            </div>
            <div class="sgh-card-content">
                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-destructive/20 bg-destructive/5 p-4">
                        <h4 class="mb-2 text-sm font-medium text-destructive">{{ __('common.fix_errors') }}</h4>
                        <ul class="list-disc space-y-1 ps-5 text-sm text-destructive">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('cost-codes.import.store') }}" enctype="multipart/form-data" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 gap-5">
                        <div class="col-span-1">
                            <label class="sgh-form-label block mb-2" for="file">
                                {{ __('cost_codes.fields.file') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="file" name="file" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                   class="sgh-input w-full" required
                                   aria-invalid="@error('file') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('cost_codes.fields.file_hint') }}
                            </div>
                            @error('file')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="sgh-btn sgh-btn-primary">
                            <x-tabler-upload />
                            {{ __('cost_codes.buttons.import') }}
                        </button>
                        <a class="sgh-btn sgh-btn-light" href="{{ route('cost-codes.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
