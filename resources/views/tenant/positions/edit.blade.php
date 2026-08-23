@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('positions.edit_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('positions.edit_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="fl-btn fl-btn-outline" href="{{ route('positions.index') }}">
                <x-tabler-arrow-left />
                {{ __('positions.back') }}
            </a>
        </div>
    </div>
</div>

<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="fl-card">
            <div class="fl-card-header">
                <h3 class="fl-card-title">{{ __('positions.details_card') }}</h3>
            </div>
            <div class="fl-card-content">
                <form method="POST" action="{{ route('positions.update', $position) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1">
                            <label class="fl-form-label block mb-2" for="name">
                                {{ __('positions.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $position->name) }}"
                                   class="fl-input w-full" placeholder="e.g. Senior Accountant" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('positions.fields.name_hint') }}
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="fl-btn fl-btn-primary">
                                <x-tabler-check-filled />
                                {{ __('positions.buttons.update') }}
                            </button>
                            <a class="fl-btn fl-btn-light" href="{{ route('positions.index') }}">{{ __('common.cancel') }}</a>
                        </div>
                        @can(App\Enums\Tenant\PermissionKey::DeletePosition->value)
                            <button type="button" class="fl-btn fl-btn-danger"
                                    onclick="if(confirm('{{ __('positions.confirm_delete') }}')) { document.getElementById('delete-position-form').submit(); }">
                                <x-tabler-trash-filled />
                                {{ __('positions.buttons.delete') }}
                            </button>
                        @endcan
                    </div>
                </form>

                @can(App\Enums\Tenant\PermissionKey::DeletePosition->value)
                    <form id="delete-position-form" action="{{ route('positions.destroy', $position) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
