@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">New Workflow Template</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Define a reusable approval workflow
            </div>
        </div>
        <a class="kt-btn kt-btn-outline" href="{{ route('workflow-templates.index') }}">
            <i class="ki-filled ki-arrow-left"></i>
            Back
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Template Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('workflow-templates.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div>
                            <label class="kt-form-label block mb-2" for="name">
                                Template Name <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                   class="kt-input w-full" placeholder="e.g. Standard Advance Approval"
                                   aria-invalid="@error('name') true @else false @enderror" />
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="type">
                                Type <span class="text-destructive">*</span>
                            </label>
                            <select id="type" name="type" class="kt-select w-full"
                                    aria-invalid="@error('type') true @else false @enderror">
                                <option value="">Select type</option>
                                <option value="advance" {{ old('type') === 'advance' ? 'selected' : '' }}>Advance</option>
                                <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Expense</option>
                                <option value="retirement" {{ old('type') === 'retirement' ? 'selected' : '' }}>Retirement</option>
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                Advance = payment advance requests &bull; Expense = out-of-pocket reimbursements &bull; Retirement = retiring an advance
                            </div>
                            @error('type')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            Create Template
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('workflow-templates.index') }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
