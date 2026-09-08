@extends('tenant.layouts.base')

@php
    $req = $instanceStage->instance->workflowable;
    $retiredPaymentRequest = $req instanceof \App\Models\Tenant\RetirementRequest ? $req->paymentRequest : null;

    $statusColors = [
        'draft'       => 'sgh-badge-outline',
        'in_workflow' => 'sgh-badge-primary',
        'approved'    => 'sgh-badge-success',
        'disbursed'   => 'sgh-badge-info',
        'retired'     => 'sgh-badge-neutral',
        'sent_back'   => 'sgh-badge-warning',
        'cancelled'   => 'sgh-badge-danger',
    ];
    $typeColors = [
        \App\Enums\Tenant\PaymentRequestType::Advance->value => 'sgh-badge-primary',
        \App\Enums\Tenant\PaymentRequestType::Expense->value => 'sgh-badge-warning',
    ];
    $stageStatusIcons = [
        'approved'  => ['icon' => 'tabler-check-filled',         'class' => 'bg-success/20 text-success'],
        'active'    => ['icon' => 'tabler-clock-filled',         'class' => 'bg-primary/20 text-primary'],
        'blocked'   => ['icon' => 'tabler-alert-triangle',       'class' => 'bg-warning/20 text-warning'],
        'rejected'  => ['icon' => 'tabler-x-filled',             'class' => 'bg-destructive/20 text-destructive'],
        'sent_back' => ['icon' => 'tabler-arrow-left',           'class' => 'bg-warning/20 text-warning'],
        'skipped'   => ['icon' => 'tabler-minus',                'class' => 'bg-muted text-muted-foreground'],
        'cancelled' => ['icon' => 'tabler-circle-x-filled',      'class' => 'bg-muted text-muted-foreground'],
    ];
@endphp

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-medium leading-none text-mono">
                    Review Request #{{ $req->id }}
                </h1>
                <span class="sgh-badge sgh-badge-sm {{ $typeColors[$req->type] ?? 'sgh-badge-outline' }}">
                    {{ ucfirst($req->type) }}
                </span>
            </div>
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <span>{{ __('common.columns.stage') }}: <span class="font-medium text-mono">{{ $instanceStage->stage->name }}</span></span>
                @if($instanceStage->instance?->template)
                    <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                        {{ __('approvals.show.workflow_version', ['version' => $instanceStage->instance->template->version]) }}
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('approvals.index') }}">
                <x-tabler-arrow-left />
                {{ __('approvals.show.back') }}
            </a>
            <a class="sgh-btn sgh-btn-outline" href="{{ route('payment-requests.show', $req) }}">
                <x-tabler-eye-filled />
                {{ __('approvals.show.view_request') }}
            </a>
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

            {{-- Main content --}}
            <div class="lg:col-span-2 flex flex-col gap-5 lg:gap-7.5">

                {{-- Request Details --}}
                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('approvals.show.request_details') }}</h3>
                    </div>
                    <div class="sgh-card-content p-5 lg:p-7.5 lg:pt-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('approvals.show.staff_member') }}</dt>
                                <dd class="text-sm font-medium text-mono">{{ $req->staff->full_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.columns.branch') }}</dt>
                                <dd class="text-sm text-foreground">{{ $req->branch->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('approvals.show.currency') }}</dt>
                                <dd class="text-sm text-foreground">{{ $req->currency->short_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('approvals.show.total_amount') }}</dt>
                                <dd class="text-lg font-semibold text-mono">
                                    {{ $req->currency->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}
                                </dd>
                            </div>
                            @if($req->submitted_at)
                                <div>
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('approvals.show.submitted_label') }}</dt>
                                    <dd class="text-sm text-foreground">{{ $req->submitted_at->format('M d, Y g:i A') }}</dd>
                                </div>
                            @endif
                            @if($retiredPaymentRequest)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('approvals.show.retiring_request') }}</dt>
                                    <dd class="text-sm text-foreground">
                                        <a href="{{ route('payment-requests.show', $retiredPaymentRequest) }}" class="text-primary hover:underline font-medium">
                                            Request #{{ $retiredPaymentRequest->id }}
                                        </a>
                                        <span class="text-secondary-foreground ml-1">
                                            — {{ $retiredPaymentRequest->currency->symbol ?? '' }} {{ number_format((float) $retiredPaymentRequest->total_amount, 2) }}
                                        </span>
                                    </dd>
                                </div>
                            @endif
                            @if($req->notes)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('approvals.show.notes') }}</dt>
                                    <dd class="text-sm text-foreground whitespace-pre-line">{{ $req->notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Line Items --}}
                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('approvals.show.line_items') }}</h3>
                        <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                            {{ $req->items->count() }} {{ Str::plural('item', $req->items->count()) }}
                        </span>
                    </div>
                    <div class="sgh-card-table">
                        <div class="sgh-scrollable-x-auto border-b border-border">
                            <table class="sgh-table sgh-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.description') }}</span></span></th>
                                        <th class="w-[180px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('payment_requests.fields.cost_code') }}</span></span></th>
                                        <th class="w-[160px] text-end"><span class="sgh-table-col justify-end"><span class="sgh-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($req->items as $item)
                                        <tr>
                                            <td><span class="text-sm text-foreground">{{ $item->description }}</span></td>
                                            <td>
                                                <span class="text-sm text-mono">{{ $item->costCode->code ?? '—' }}</span>
                                                @if($item->costCode)
                                                    <span class="text-secondary-foreground text-sm font-normal"> — {{ $item->costCode->name }}</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <span class="text-sm font-medium text-mono">
                                                    {{ $req->currency->symbol ?? '' }} {{ number_format((float) $item->amount, 2) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end text-sm font-medium text-secondary-foreground">{{ __('common.total') }}</td>
                                        <td class="text-end">
                                            <span class="text-base font-semibold text-mono">
                                                {{ $req->currency->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Prior Actions --}}
                @if($instanceStage->actions->isNotEmpty())
                    <div class="sgh-card">
                        <div class="sgh-card-header">
                            <h3 class="sgh-card-title">{{ __('approvals.show.action_history') }}</h3>
                        </div>
                        <div class="sgh-card-content p-5 flex flex-col gap-4">
                            @foreach($instanceStage->actions as $action)
                                <div class="flex gap-3">
                                    <div class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full
                                        {{ $action->action === 'approve' ? 'bg-success/20 text-success' : ($action->action === 'reject' ? 'bg-destructive/20 text-destructive' : 'bg-warning/20 text-warning') }}">
                                        <x-dynamic-component :component="$action->action === 'approve' ? 'tabler-check-filled' : ($action->action === 'reject' ? 'tabler-x-filled' : 'tabler-arrow-left')" class="text-sm" />
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm font-medium text-mono">
                                            {{ $action->user->name ?? '—' }}
                                            <span class="text-secondary-foreground font-normal capitalize">{{ str_replace('_', ' ', $action->action) }}</span>
                                        </span>
                                        @if($action->comment)
                                            <span class="text-sm text-foreground italic">"{{ $action->comment }}"</span>
                                        @endif
                                        <span class="text-xs text-secondary-foreground">{{ $action->created_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

            {{-- Sidebar --}}
            <div class="flex flex-col gap-5 lg:gap-7.5">

                {{-- Action Panel --}}
                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('approvals.show.your_decision') }}</h3>
                    </div>
                    <div class="sgh-card-content p-5">
                        @if($instanceStage->isActive())
                            <form method="POST" action="{{ route('approvals.store', $instanceStage) }}" id="approval-form">
                                @csrf

                                {{-- Comment --}}
                                <div class="mb-4">
                                    <label class="sgh-form-label block mb-2" for="comment">
                                        {{ __('approvals.show.comment_label') }}
                                        <span class="text-secondary-foreground font-normal text-xs">{{ __('approvals.show.comment_required') }}</span>
                                    </label>
                                    <textarea id="comment" name="comment" rows="4"
                                              class="sgh-textarea w-full"
                                              placeholder="{{ __('approvals.show.comment_placeholder') }}"
                                              aria-invalid="@error('comment') true @else false @enderror">{{ old('comment') }}</textarea>
                                    @error('comment')
                                        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                                    @enderror
                                    @error('action')
                                        <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Buttons --}}
                                <div class="flex flex-col gap-2">
                                    <button type="submit" name="action" value="approve"
                                            class="sgh-btn sgh-btn-success w-full">
                                        <x-tabler-circle-check-filled />
                                        {{ __('common.approve') }}
                                    </button>
                                    @if($instanceStage->stage->allow_send_back)
                                        <button type="submit" name="action" value="send_back"
                                                class="sgh-btn sgh-btn-warning sgh-btn-outline w-full">
                                            <x-tabler-arrow-left />
                                            {{ __('common.send_back') }}
                                        </button>
                                    @endif
                                    <button type="submit" name="action" value="reject"
                                            onclick="return confirm('{{ __('approvals.show.reject_confirm') }}')"
                                            class="sgh-btn sgh-btn-danger sgh-btn-outline w-full">
                                        <x-tabler-circle-x-filled />
                                        {{ __('common.reject') }}
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="flex flex-col items-center gap-2 py-4 text-center">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                    <x-tabler-check-filled class="text-base" />
                                </span>
                                <p class="text-sm font-medium text-mono">{{ __('approvals.show.already_resolved_heading') }}</p>
                                <p class="text-sm text-secondary-foreground">{{ __('approvals.show.already_resolved_body') }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Workflow Progress --}}
                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('approvals.show.approval_progress') }}</h3>
                    </div>
                    <div class="sgh-card-content p-5 flex flex-col gap-3">
                        @foreach($instanceStage->instance->instanceStages->sortBy('stage.display_order') as $is)
                            @php
                                $icon = $stageStatusIcons[$is->status] ?? ['icon' => 'tabler-dots-circle-horizontal', 'class' => 'border-2 border-border bg-background text-muted-foreground'];
                                $isCurrent = $is->id === $instanceStage->id;
                            @endphp
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 shrink-0">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $icon['class'] }} {{ $isCurrent ? 'ring-2 ring-primary ring-offset-1' : '' }}">
                                        <x-dynamic-component :component="$icon['icon']" class="text-xs" />
                                    </span>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-medium text-mono {{ $isCurrent ? 'text-primary' : '' }}">
                                        {{ $is->stage->name }}
                                        @if($isCurrent) <span class="text-xs font-normal">({{ __('common.current') }})</span> @endif
                                    </span>
                                    <span class="text-xs text-secondary-foreground capitalize">
                                        {{ str_replace('_', ' ', $is->status) }}
                                    </span>
                                    @if($is->status === 'blocked')
                                        <span class="text-xs text-warning">{{ __('workflows.separation.no_independent_approver') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
