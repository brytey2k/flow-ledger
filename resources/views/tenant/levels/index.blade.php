@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Levels Management</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Manage organisational levels and hierarchy
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateLevel->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('levels.create') }}">
                <i class="ki-filled ki-plus"></i>
                Add New Level
            </a>
        @endcan
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">All Levels</h3>
                <div class="flex items-center gap-2">
                    <span class="kt-badge kt-badge-sm kt-badge-outline">
                        {{ $levels->count() }} {{ Str::plural('Level', $levels->count()) }}
                    </span>
                </div>
            </div>

            @if($levels->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-layers text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No levels found</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Get started by creating your first level</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateLevel->value)
                            <a href="{{ route('levels.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                Add Level
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Position</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Level Name</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Branches</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Created</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Actions</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($levels as $level)
                                    <tr>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-primary">{{ $level->position }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium leading-none text-mono">{{ $level->name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $level->branches_count ?? $level->branches()->count() }}</span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm text-foreground">{{ $level->created_at->format('M d, Y') }}</span>
                                                <span class="text-2sm text-secondary-foreground">{{ $level->created_at->format('h:i A') }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @can(App\Enums\Tenant\PermissionKey::AccessLevels->value)
                                                    <a href="{{ route('levels.edit', $level) }}"
                                                       class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-primary"
                                                       title="Edit">
                                                        <i class="ki-filled ki-notepad-edit text-lg"></i>
                                                    </a>
                                                    <form action="{{ route('levels.destroy', $level) }}" method="POST"
                                                          onsubmit="return confirm('Delete this level?')" class="inline">
                                                        @csrf @method('DELETE')
                                                        <button type="submit"
                                                                class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-danger"
                                                                title="Delete">
                                                            <i class="ki-filled ki-trash text-lg"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
