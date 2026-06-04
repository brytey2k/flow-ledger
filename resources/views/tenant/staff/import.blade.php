@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('staff.import_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('staff.import_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('staff.import.template') }}">
                <i class="ki-filled ki-file-down"></i>
                {{ __('staff.buttons.download_template') }}
            </a>
            <a class="kt-btn kt-btn-light" href="{{ route('staff.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('staff.back') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('staff.sample_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <p class="text-sm text-secondary-foreground">
                    {{ __('staff.import_notes') }}
                </p>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('staff.import_card') }}</h3>
            </div>
            <div class="kt-card-content">
                @if (session('success'))
                    <div class="mb-6 rounded-lg border border-success/30 bg-success/5 p-4 text-sm text-success">
                        {{ session('success') }}
                    </div>
                @endif

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
                            <label class="kt-form-label block mb-2" for="file">
                                {{ __('staff.fields.file') }} <span class="text-destructive">*</span>
                            </label>
                            <input
                                id="file"
                                name="file"
                                type="file"
                                accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                class="kt-input w-full"
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
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-upload"></i>
                            {{ __('staff.buttons.import') }}
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('staff.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
