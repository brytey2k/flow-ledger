@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.edit_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $workflowTemplate->name }}
            </div>
        </div>
        <a class="sgh-btn sgh-btn-outline" href="{{ route('workflow-templates.show', $workflowTemplate) }}">
            <x-tabler-arrow-left />
            {{ __('workflows.back') }}
        </a>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('workflows.details_card') }}</h3>
            </div>
            <div class="sgh-card-content">
                <form method="POST" action="{{ route('workflow-templates.update', $workflowTemplate) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div>
                            <label class="sgh-form-label block mb-2" for="name">
                                {{ __('workflows.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $workflowTemplate->name) }}"
                                   class="sgh-input w-full"
                                   aria-invalid="@error('name') true @else false @enderror" />
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="sgh-form-label block mb-2">
                                {{ __('workflows.fields.type') }}
                            </label>
                            <div>
                                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                                    @if($workflowTemplate->type === 'advance') {{ __('workflows.fields.type_advance') }}
                                    @elseif($workflowTemplate->type === 'expense') {{ __('workflows.fields.type_expense') }}
                                    @elseif($workflowTemplate->type === 'retirement') {{ __('workflows.fields.type_retirement') }}
                                    @else {{ ucfirst($workflowTemplate->type) }}
                                    @endif
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="sgh-form-label block mb-2">
                                {{ __('workflows.fields.branch') }}
                            </label>
                            <div>
                                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                                    {{ $workflowTemplate->branch->name ?? __('workflows.fields.branch_master') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="text-xs text-muted-foreground">
                        {{ __('workflows.identity_locked_hint') }}
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="sgh-btn sgh-btn-primary">
                                <x-tabler-check-filled />
                                {{ __('workflows.buttons.update') }}
                            </button>
                            <a class="sgh-btn sgh-btn-light" href="{{ route('workflow-templates.show', $workflowTemplate) }}">{{ __('common.cancel') }}</a>
                        </div>
                        @can(App\Enums\Tenant\PermissionKey::DeleteWorkflowTemplate->value)
                            <button type="button" class="sgh-btn sgh-btn-danger"
                                    onclick="if(confirm('{{ __('workflows.confirm_delete') }}')) { document.getElementById('delete-form').submit(); }">
                                <x-tabler-trash-filled />
                                {{ __('workflows.buttons.delete') }}
                            </button>
                        @endcan
                    </div>
                </form>

                @can(App\Enums\Tenant\PermissionKey::DeleteWorkflowTemplate->value)
                    <form id="delete-form" action="{{ route('workflow-templates.destroy', $workflowTemplate) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
