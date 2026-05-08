@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Edit Stage</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $workflowTemplate->name }} &rsaquo; {{ $workflowStage->name }}
            </div>
        </div>
        <a class="kt-btn kt-btn-outline" href="{{ route('workflow-templates.show', $workflowTemplate) }}">
            <i class="ki-filled ki-arrow-left"></i>
            Back
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Stage Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('workflow-templates.stages.update', [$workflowTemplate, $workflowStage]) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div>
                            <label class="kt-form-label block mb-2" for="name">
                                Stage Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $workflowStage->name) }}"
                                   class="kt-input w-full"
                                   aria-invalid="@error('name') true @else false @enderror" />
                            @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="display_order">
                                Display Order <span class="text-destructive">*</span>
                            </label>
                            <input id="display_order" name="display_order" type="number" min="1"
                                   value="{{ old('display_order', $workflowStage->display_order) }}"
                                   class="kt-input w-full" />
                            <div class="mt-1 text-xs text-muted-foreground">Lower numbers run first. Same number = parallel.</div>
                            @error('display_order') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="skip_below_amount">
                                Skip Below Amount
                            </label>
                            <input id="skip_below_amount" name="skip_below_amount" type="number" step="0.01" min="0"
                                   value="{{ old('skip_below_amount', $workflowStage->skip_below_amount) }}"
                                   class="kt-input w-full" placeholder="Leave empty to never skip" />
                            @error('skip_below_amount') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="parallel_group_id">
                                Parallel Group
                            </label>
                            <select id="parallel_group_id" name="parallel_group_id" class="kt-select w-full">
                                <option value="">None (sequential)</option>
                                @foreach($parallelGroups as $group)
                                    <option value="{{ $group->id }}"
                                            {{ old('parallel_group_id', $workflowStage->parallel_group_id) == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }} ({{ $group->require_all ? 'ALL must approve' : 'ANY one approves' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('parallel_group_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="kt-form-label block mb-2">
                            Roles that can approve this stage <span class="text-destructive">*</span>
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
                                Save Changes
                            </button>
                            <a class="kt-btn kt-btn-light" href="{{ route('workflow-templates.show', $workflowTemplate) }}">Cancel</a>
                        </div>
                        <button type="button" class="kt-btn kt-btn-danger"
                                onclick="if(confirm('Delete this stage?')) { document.getElementById('delete-stage-form').submit(); }">
                            <i class="ki-filled ki-trash"></i>
                            Delete Stage
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
