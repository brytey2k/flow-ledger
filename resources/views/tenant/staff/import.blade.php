@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('staff.import_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('staff.import_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="fl-btn fl-btn-outline" href="{{ route('staff.import.template') }}">
                <x-tabler-file-arrow-right />
                {{ __('staff.buttons.download_template') }}
            </a>
            <a class="fl-btn fl-btn-light" href="{{ route('staff.index') }}">
                <x-tabler-arrow-left />
                {{ __('staff.back') }}
            </a>
        </div>
    </div>
</div>

<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="fl-card">
            <div class="fl-card-header">
                <h3 class="fl-card-title">{{ __('staff.sample_card') }}</h3>
            </div>
            <div class="fl-card-content">
                <p class="text-sm text-secondary-foreground">
                    {{ __('staff.import_notes') }}
                </p>
            </div>
        </div>

        <div class="fl-card">
            <div class="fl-card-header">
                <h3 class="fl-card-title">{{ __('staff.import_card') }}</h3>
            </div>
            <div class="fl-card-content">
                @php($importErrors = session('import_errors', []))
                @if (! empty($importErrors) && is_array($importErrors))
                    <details class="mb-6 rounded-lg border border-warning/30 bg-warning/5 p-4">
                        <summary class="cursor-pointer text-sm font-medium text-warning">
                            {{ __('staff.import_errors.title', ['count' => count($importErrors)]) }}
                        </summary>
                        <ul class="mt-3 list-disc space-y-1 ps-5 text-sm text-warning">
                            @foreach ($importErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </details>
                @endif

                <form method="POST" action="{{ route('staff.import.store') }}" enctype="multipart/form-data" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 gap-5">
                        <div class="col-span-1">
                            <label class="fl-form-label block mb-2" for="file">
                                {{ __('staff.fields.file') }} <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="file"
                                name="file"
                                type="file"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                class="fl-input w-full"
                                required
                                aria-invalid="@error('file') true @else false @enderror"
                            />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('staff.fields.file_hint') }}
                            </div>
                            @error('file')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="fl-btn fl-btn-primary">
                            <x-tabler-upload />
                            {{ __('staff.buttons.import') }}
                        </button>
                        <a class="fl-btn fl-btn-light" href="{{ route('staff.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
