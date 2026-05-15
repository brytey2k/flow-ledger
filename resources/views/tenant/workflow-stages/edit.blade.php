@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.stages.edit_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $workflowTemplate->name }} &rsaquo; {{ $workflowStage->name }}
            </div>
        </div>
        <a class="kt-btn kt-btn-outline" href="{{ route('workflow-templates.show', $workflowTemplate) }}">
            <i class="ki-filled ki-arrow-left"></i>
            {{ __('workflows.stages.back') }}
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('workflows.stages.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('workflow-templates.stages.update', [$workflowTemplate, $workflowStage]) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div>
                            <label class="kt-form-label block mb-2" for="name">
                                {{ __('workflows.stages.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $workflowStage->name) }}"
                                   class="kt-input w-full"
                                   aria-invalid="@error('name') true @else false @enderror" />
                            @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="display_order">
                                {{ __('workflows.stages.fields.display_order') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="display_order" name="display_order" type="number" min="1"
                                   value="{{ old('display_order', $workflowStage->display_order) }}"
                                   class="kt-input w-full" />
                            <div class="mt-1 text-xs text-muted-foreground">{{ __('workflows.stages.fields.order_hint') }}</div>
                            @error('display_order') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="skip_below_amount">
                                {{ __('workflows.stages.fields.skip_below') }}
                            </label>
                            <input id="skip_below_amount" name="skip_below_amount" type="number" step="0.01" min="0"
                                   value="{{ old('skip_below_amount', $workflowStage->skip_below_amount) }}"
                                   class="kt-input w-full" placeholder="{{ __('workflows.stages.fields.skip_below_hint_short') }}" />
                            @error('skip_below_amount') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="parallel_group_id">
                                {{ __('workflows.stages.fields.parallel_group') }}
                            </label>
                            <select id="parallel_group_id" name="parallel_group_id" class="kt-select w-full">
                                <option value="">{{ __('workflows.stages.fields.none_sequential') }}</option>
                                @foreach($parallelGroups as $group)
                                    <option value="{{ $group->id }}"
                                            {{ old('parallel_group_id', $workflowStage->parallel_group_id) == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }} ({{ $group->require_all ? __('workflows.stages.fields.all_must_approve') : __('workflows.stages.fields.any_approves') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('parallel_group_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="scope_to_department" value="0" />
                            <input type="checkbox" id="scope_to_department" name="scope_to_department" value="1"
                                   class="kt-checkbox"
                                   {{ old('scope_to_department', $workflowStage->scope_to_department) ? 'checked' : '' }} />
                            <span class="kt-form-label mb-0">{{ __('workflows.stages.fields.scope_to_department') }}</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="scope_to_branch" value="0" />
                            <input type="checkbox" id="scope_to_branch" name="scope_to_branch" value="1"
                                   class="kt-checkbox"
                                   {{ old('scope_to_branch', $workflowStage->scope_to_branch) ? 'checked' : '' }} />
                            <span class="kt-form-label mb-0">{{ __('workflows.stages.fields.scope_to_branch') }}</span>
                        </label>
                    </div>

                    <div>
                        <label class="kt-form-label block mb-2">
                            {{ __('workflows.stages.fields.roles_label') }} <span class="text-destructive">*</span>
                        </label>
                        @error('role_ids') <p class="mb-2 text-sm text-destructive">{{ $message }}</p> @enderror
                        @php $assignedRoleIds = $workflowStage->roles->pluck('id')->toArray(); @endphp
                        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                            @foreach($roles as $role)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                                           {{ in_array($role->id, old('role_ids', $assignedRoleIds)) ? 'checked' : '' }}
                                           class="kt-checkbox" />
                                    <span class="text-sm text-foreground">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                {{ __('workflows.stages.buttons.save') }}
                            </button>
                            <a class="kt-btn kt-btn-light" href="{{ route('workflow-templates.show', $workflowTemplate) }}">{{ __('common.cancel') }}</a>
                        </div>
                        <button type="button" class="kt-btn kt-btn-danger"
                                onclick="if(confirm('{{ __('workflows.stages.confirm_delete') }}')) { document.getElementById('delete-stage-form').submit(); }">
                            <i class="ki-filled ki-trash"></i>
                            {{ __('workflows.stages.buttons.delete') }}
                        </button>
                    </div>
                </form>

                <form id="delete-stage-form" action="{{ route('workflow-templates.stages.destroy', [$workflowTemplate, $workflowStage]) }}" method="POST" class="hidden">
                    @csrf @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
