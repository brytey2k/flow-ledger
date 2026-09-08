@extends('tenant.layouts.base')

@php
    $subject = $instanceStage->instance->workflowable;
    $backRoute = $subject instanceof \App\Models\Tenant\RetirementRequest
        ? route('retirement-requests.show', $subject)
        : route('payment-requests.show', $subject);
@endphp

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.separation.eligible_approvers_title') }}</h1>
            <p class="text-sm text-secondary-foreground">
                {{ __('workflows.separation.eligible_approvers_subtitle', [
                    'workflow' => $instanceStage->instance->template->name,
                    'stage' => $instanceStage->stage->name,
                ]) }}
            </p>
        </div>
        <a class="sgh-btn sgh-btn-outline" href="{{ $backRoute }}">
            <x-tabler-arrow-left />
            {{ __('workflows.separation.back_to_request') }}
        </a>
    </div>

    <div class="sgh-card">
        <div class="sgh-card-header flex-wrap gap-3">
            <div class="flex flex-col gap-1">
                <h2 class="sgh-card-title">
                    {{ trans_choice('workflows.separation.eligible_approver_count', $eligibleApproverCount, ['count' => $eligibleApproverCount]) }}
                </h2>
                @if($approverRoles->isNotEmpty())
                    <p class="text-xs text-secondary-foreground">{{ $approverRoles->pluck('name')->join(', ') }}</p>
                @endif
            </div>
            <form method="GET" action="{{ route('approvals.eligible-approvers', $instanceStage) }}" class="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                <label class="sr-only" for="approver-search">{{ __('workflows.separation.search_eligible_approvers') }}</label>
                <input
                    id="approver-search"
                    name="q"
                    type="search"
                    maxlength="100"
                    class="sgh-input w-full sm:w-72"
                    placeholder="{{ __('workflows.separation.search_eligible_approvers') }}"
                    value="{{ $search }}"
                />
                <button type="submit" class="sgh-btn sgh-btn-primary">{{ __('workflows.separation.search') }}</button>
                @if($search !== null)
                    <a href="{{ route('approvals.eligible-approvers', $instanceStage) }}" class="sgh-btn sgh-btn-light">
                        {{ __('workflows.separation.clear_search') }}
                    </a>
                @endif
            </form>
        </div>

        @if($eligibleApprovers->isEmpty())
            <div class="p-6 text-sm text-muted-foreground">{{ __('workflows.separation.no_eligible_approvers') }}</div>
        @else
            <div class="sgh-card-table">
                <div class="sgh-scrollable-x-auto border-b border-border">
                    <table class="sgh-table sgh-table-border">
                        <thead>
                            <tr>
                                <th><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.separation.approver_name') }}</span></span></th>
                                <th><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.separation.approver_email') }}</span></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eligibleApprovers as $approver)
                                <tr>
                                    <td class="font-medium text-mono">{{ $approver->name }}</td>
                                    <td class="text-secondary-foreground">{{ $approver->email }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($eligibleApprovers->hasPages())
                    <div class="p-4">{{ $eligibleApprovers->links() }}</div>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
