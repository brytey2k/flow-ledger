@extends('tenant.layouts.base')

@php
    $req = $instanceStage->instance->workflowable;
    $retiredPaymentRequest = $req instanceof \App\Models\Tenant\RetirementRequest ? $req->paymentRequest : null;

    $statusColors = [
        'draft'       => 'kt-badge-outline',
        'in_workflow' => 'kt-badge-primary',
        'approved'    => 'kt-badge-success',
        'disbursed'   => 'kt-badge-info',
        'retired'     => 'kt-badge-neutral',
        'sent_back'   => 'kt-badge-warning',
        'cancelled'   => 'kt-badge-danger',
    ];
    $typeColors = [
        \App\Enums\Tenant\PaymentRequestType::Advance->value => 'kt-badge-primary',
        \App\Enums\Tenant\PaymentRequestType::Expense->value => 'kt-badge-warning',
    ];
    $stageStatusIcons = [
        'approved'  => ['icon' => 'ki-check',         'class' => 'bg-success/20 text-success'],
        'active'    => ['icon' => 'ki-time',           'class' => 'bg-primary/20 text-primary'],
        'rejected'  => ['icon' => 'ki-cross',          'class' => 'bg-destructive/20 text-destructive'],
        'sent_back' => ['icon' => 'ki-arrow-left',     'class' => 'bg-warning/20 text-warning'],
        'skipped'   => ['icon' => 'ki-minus',          'class' => 'bg-muted text-muted-foreground'],
        'cancelled' => ['icon' => 'ki-cross-circle',   'class' => 'bg-muted text-muted-foreground'],
    ];
@endphp

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-medium leading-none text-mono">
                    Review Request #{{ $req->id }}
                </h1>
                <span class="kt-badge kt-badge-sm {{ $typeColors[$req->type] ?? 'kt-badge-outline' }}">
                    {{ ucfirst($req->type) }}
                </span>
            </div>
            <div class="text-sm text-secondary-foreground">
                {{ __('common.columns.stage') }}: <span class="font-medium text-mono">{{ $instanceStage->stage->name }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('approvals.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('approvals.show.back') }}
            </a>
            <a class="kt-btn kt-btn-outline" href="{{ route('payment-requests.show', $req) }}">
                <i class="ki-filled ki-eye"></i>
                {{ __('approvals.show.view_request') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

            {{-- Main content --}}
            <div class="lg:col-span-2 flex flex-col gap-5 lg:gap-7.5">

                {{-- Request Details --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('approvals.show.request_details') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
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
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('approvals.show.line_items') }}</h3>
                        <span class="kt-badge kt-badge-sm kt-badge-outline">
                            {{ $req->items->count() }} {{ Str::plural('item', $req->items->count()) }}
                        </span>
                    </div>
                    <div class="kt-card-table">
                        <div class="kt-scrollable-x-auto border-b border-border">
                            <table class="kt-table kt-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.description') }}</span></span></th>
                                        <th class="w-[180px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('payment_requests.fields.cost_code') }}</span></span></th>
                                        <th class="w-[160px] text-end"><span class="kt-table-col justify-end"><span class="kt-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
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
                                        <td class="text-end text-sm font-medium text-secondary-foreground">{{ __('common.total') }}</td>
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
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">{{ __('approvals.show.action_history') }}</h3>
                        </div>
                        <div class="kt-card-content p-5 flex flex-col gap-4">
                            @foreach($instanceStage->actions as $action)
                                <div class="flex gap-3">
                                    <div class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full
                                        {{ $action->action === 'approve' ? 'bg-success/20 text-success' : ($action->action === 'reject' ? 'bg-destructive/20 text-destructive' : 'bg-warning/20 text-warning') }}">
                                        <i class="ki-filled {{ $action->action === 'approve' ? 'ki-check' : ($action->action === 'reject' ? 'ki-cross' : 'ki-arrow-left') }} text-sm"></i>
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
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('approvals.show.your_decision') }}</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <form method="POST" action="{{ route('approvals.store', $instanceStage) }}" id="approval-form">
                            @csrf

                            {{-- Comment --}}
                            <div class="mb-4">
                                <label class="kt-form-label block mb-2" for="comment">
                                    {{ __('approvals.show.comment_label') }}
                                    <span class="text-secondary-foreground font-normal text-xs">{{ __('approvals.show.comment_required') }}</span>
                                </label>
                                <textarea id="comment" name="comment" rows="4"
                                          class="kt-textarea w-full"
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
                                        class="kt-btn kt-btn-success w-full">
                                    <i class="ki-filled ki-check-circle"></i>
                                    {{ __('common.approve') }}
                                </button>
                                <button type="submit" name="action" value="send_back"
                                        class="kt-btn kt-btn-warning kt-btn-outline w-full">
                                    <i class="ki-filled ki-arrow-left"></i>
                                    {{ __('common.send_back') }}
                                </button>
                                <button type="submit" name="action" value="reject"
                                        onclick="return confirm('{{ __('approvals.show.reject_confirm') }}')"
                                        class="kt-btn kt-btn-danger kt-btn-outline w-full">
                                    <i class="ki-filled ki-cross-circle"></i>
                                    {{ __('common.reject') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Workflow Progress --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('approvals.show.approval_progress') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-3">
                        @foreach($instanceStage->instance->instanceStages->sortBy('stage.display_order') as $is)
                            @php
                                $icon = $stageStatusIcons[$is->status] ?? ['icon' => 'ki-dots-circle', 'class' => 'border-2 border-border bg-background text-muted-foreground'];
                                $isCurrent = $is->id === $instanceStage->id;
                            @endphp
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 shrink-0">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full {{ $icon['class'] }} {{ $isCurrent ? 'ring-2 ring-primary ring-offset-1' : '' }}">
                                        <i class="ki-filled {{ $icon['icon'] }} text-xs"></i>
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
