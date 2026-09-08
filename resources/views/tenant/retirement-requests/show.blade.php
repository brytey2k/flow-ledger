@extends('tenant.layouts.base')

@php
    use App\Enums\Tenant\PermissionKey;

    $statusColors = [
        'draft'       => 'sgh-badge-outline',
        'in_workflow' => 'sgh-badge-primary',
        'approved'    => 'sgh-badge-success',
        'settled'     => 'sgh-badge-info',
        'sent_back'   => 'sgh-badge-warning',
        'cancelled'   => 'sgh-badge-danger',
    ];
    $diffTypeInfo = [
        'pay_to_staff'      => ['label' => __('retirements.status.pay_to_staff'),   'class' => 'bg-warning/10 text-warning'],
        'refund_to_company' => ['label' => __('retirements.status.refund_company'), 'class' => 'bg-destructive/10 text-destructive'],
        'nil'               => ['label' => __('retirements.status.nil'),             'class' => 'bg-success/10 text-success'],
    ];
    $eventLabels = [
        'retirement.created'    => __('retirements.timeline.created_draft'),
        'retirement.submitted'  => __('retirements.timeline.submitted'),
        'retirement.approved'   => __('retirements.timeline.fully_approved'),
        'retirement.cancelled'  => __('retirements.timeline.cancelled'),
        'retirement.settled'    => __('retirements.timeline.settled'),
        'retirement.resubmitted'=> __('retirements.timeline.resubmitted'),
        'retirement.updated'    => __('retirements.timeline.updated'),
        'stage.approved'        => __('retirements.timeline.stage_approved'),
        'stage.rejected'        => __('retirements.timeline.stage_rejected'),
        'stage.sent_back'       => __('retirements.timeline.sent_back'),
    ];
    $pr = $retirementRequest->paymentRequest;
    $diffInfo = $diffTypeInfo[$retirementRequest->difference_type] ?? ['label' => '—', 'class' => 'bg-muted text-muted-foreground'];
@endphp

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-medium leading-none text-mono">Retirement #{{ $retirementRequest->id }}</h1>
                <span class="sgh-badge sgh-badge-sm {{ $statusColors[$retirementRequest->status] ?? 'sgh-badge-outline' }}">
                    {{ ucwords(str_replace('_', ' ', $retirementRequest->status)) }}
                </span>
            </div>
            <div class="text-sm text-secondary-foreground">
                {{ __('retirements.show.for_advance') }} <a href="{{ route('payment-requests.show', $pr) }}" class="text-primary hover:underline">#{{ $pr->id }}</a>
                &mdash; {{ $pr->staff->full_name ?? '—' }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('retirement-requests.index') }}">
                <x-tabler-arrow-left />
                {{ __('retirements.show.back') }}
            </a>
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

            {{-- Main content --}}
            <div class="lg:col-span-2 flex flex-col gap-5 lg:gap-7.5">

                {{-- Summary --}}
                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('retirements.show.summary_card') }}</h3>
                    </div>
                    <div class="sgh-card-content p-5 lg:p-7.5 lg:pt-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('approvals.show.staff_member') }}</dt>
                                <dd class="text-sm font-medium text-mono">{{ $pr->staff->full_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.columns.branch') }}</dt>
                                <dd class="text-sm text-foreground">{{ $pr->branch->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('retirements.fields.advance_amount') }}</dt>
                                <dd class="text-sm font-medium text-mono">
                                    {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $pr->total_amount, 2) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('retirements.fields.total_expended') }}</dt>
                                <dd class="text-lg font-semibold text-mono">
                                    {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $retirementRequest->total_amount_expended, 2) }}
                                </dd>
                            </div>
                            @if($retirementRequest->submitted_at)
                                <div>
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.columns.submitted') }}</dt>
                                    <dd class="text-sm text-foreground">{{ $retirementRequest->submitted_at->format('M d, Y g:i A') }}</dd>
                                </div>
                            @endif
                            @if($retirementRequest->approved_at)
                                <div>
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('payment_requests.show.approved') }}</dt>
                                    <dd class="text-sm text-foreground">{{ $retirementRequest->approved_at->format('M d, Y g:i A') }}</dd>
                                </div>
                            @endif
                            @if($retirementRequest->notes)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.notes') }}</dt>
                                    <dd class="text-sm text-foreground whitespace-pre-line">{{ $retirementRequest->notes }}</dd>
                                </div>
                            @endif
                        </dl>

                        {{-- Difference Banner --}}
                        <div class="mt-5 flex items-center gap-3 p-4 rounded-lg {{ $diffInfo['class'] }}">
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ $diffInfo['label'] }}</div>
                                @if($retirementRequest->difference_type !== 'nil')
                                    <div class="text-lg font-semibold font-mono">
                                        {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $retirementRequest->difference_amount, 2) }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($retirementRequest->no_money_spent)
                            <div class="mt-4 sgh-alert sgh-alert-warning">
                                <span class="sgh-alert-icon"><x-tabler-info-square-rounded-filled class="text-xl" /></span>
                                <div class="sgh-alert-content">
                                    <div class="sgh-alert-title">{{ __('retirements.show.no_spend_notice') }}</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Expenditure Items --}}
                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('retirements.fields.expenditure_items') }}</h3>
                        <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                            {{ $retirementRequest->items->count() }} {{ Str::plural('item', $retirementRequest->items->count()) }}
                        </span>
                    </div>
                    <div class="sgh-card-table">
                        <div class="sgh-scrollable-x-auto border-b border-border">
                            <table class="sgh-table sgh-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.description') }}</span></span></th>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('retirements.fields.cost_code') }}</span></span></th>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('payment_requests.show.receipt') }}</span></span></th>
                                        <th class="w-[140px] text-end"><span class="sgh-table-col justify-end"><span class="sgh-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($retirementRequest->items as $item)
                                        <tr>
                                            <td><span class="text-sm text-foreground">{{ $item->description }}</span></td>
                                            <td>
                                                <span class="text-sm text-mono">
                                                    {{ $item->costCode->code ?? '—' }}
                                                    @if($item->costCode)
                                                        <span class="text-secondary-foreground font-normal">— {{ $item->costCode->name }}</span>
                                                    @endif
                                                </span>
                                            </td>
                                            <td><span class="text-sm text-secondary-foreground">{{ $item->receipt_number ?? '—' }}</span></td>
                                            <td class="text-end">
                                                <span class="text-sm font-medium text-mono">
                                                    {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $item->amount, 2) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end text-sm font-medium text-secondary-foreground">{{ __('retirements.fields.total_expended') }}</td>
                                        <td class="text-end">
                                            <span class="text-base font-semibold text-mono">
                                                {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $retirementRequest->total_amount_expended, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Attachments --}}
                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('retirements.show.attachments_card') }}</h3>
                        <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                            {{ $retirementRequest->attachments->count() }} {{ Str::plural('file', $retirementRequest->attachments->count()) }}
                        </span>
                    </div>
                    <div class="sgh-card-content p-5 flex flex-col gap-4">
                        @forelse($retirementRequest->attachments as $attachment)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-border">
                                <div class="flex items-center gap-3 min-w-0">
                                    <x-tabler-file-filled class="text-muted-foreground shrink-0" />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-mono truncate">{{ $attachment->original_name }}</p>
                                        <p class="text-xs text-secondary-foreground">{{ $attachment->formattedSize() }} &bull; {{ $attachment->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if($attachment->isPreviewable())
                                        <button
                                            type="button"
                                            data-preview-url="{{ route('attachments.preview', $attachment) }}"
                                            data-preview-name="{{ $attachment->original_name }}"
                                            title="{{ __('common.preview') }}"
                                            aria-label="{{ __('common.preview') }}"
                                            class="sgh-btn sgh-btn-sm sgh-btn-outline"
                                            @click="$store.attachmentPreview.show($el.dataset.previewUrl, $el.dataset.previewName)"
                                        >
                                            <x-tabler-eye-filled />
                                        </button>
                                    @endif
                                    <a href="{{ route('attachments.download', $attachment) }}"
                                       title="{{ __('common.download') }}"
                                       aria-label="{{ __('common.download') }}"
                                       class="sgh-btn sgh-btn-sm sgh-btn-outline">
                                        <x-tabler-cloud-download />
                                    </a>
                                    @if(optional($attachment->attachable->paymentRequest->staff)->user_id === auth()->id())
                                        <form method="POST" action="{{ route('attachments.destroy', $attachment) }}" onsubmit="return confirm('{{ __('retirements.show.confirm_delete_attachment') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-outline text-destructive hover:bg-destructive/10">
                                                <x-tabler-trash-filled />
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-secondary-foreground text-center py-2">{{ __('retirements.show.no_attachments') }}</p>
                        @endforelse

                        @can(App\Enums\Tenant\PermissionKey::CreateRetirementRequest->value)
                            @if(!in_array($retirementRequest->status, ['settled', 'cancelled']))
                                <form method="POST" action="{{ route('retirement-requests.attachments.store', $retirementRequest) }}" enctype="multipart/form-data" class="mt-2">
                                    @csrf
                                    <label class="flex flex-col items-center justify-center gap-2 p-4 rounded-lg border-2 border-dashed border-border cursor-pointer hover:border-primary/50 hover:bg-muted/30 transition-colors">
                                        <x-tabler-cloud-upload class="text-2xl text-muted-foreground" />
                                        <span class="text-sm font-medium text-foreground">{{ __('common.upload') }}</span>
                                        <span class="text-xs text-secondary-foreground">PDF, JPG, PNG, Word, Excel &mdash; max 10MB</span>
                                        <input type="file" name="file" class="sr-only" onchange="this.closest('form').submit()" />
                                    </label>
                                    @error('file') <p class="mt-1 text-xs text-destructive">{{ $message }}</p> @enderror
                                </form>
                            @endif
                        @endcan
                    </div>
                </div>

                {{-- Timeline --}}
                @php
                    $timelineItems = $retirementRequest->activities->sortByDesc('created_at')->map(fn($log) => [
                        'type' => 'activity',
                        'at'   => $log->created_at,
                        'item' => $log,
                    ])->merge($retirementRequest->comments->map(fn($c) => [
                        'type' => 'comment',
                        'at'   => $c->created_at,
                        'item' => $c,
                    ]))->sortByDesc('at')->values();
                @endphp

                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('payment_requests.show.timeline') }}</h3>
                    </div>
                    <div class="sgh-card-content p-5 flex flex-col gap-4">
                        @forelse($timelineItems as $entry)
                            @if($entry['type'] === 'activity')
                                @php $log = $entry['item']; @endphp
                                <div class="flex gap-3">
                                    <div class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                        <x-tabler-info-circle-filled class="text-sm" />
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm font-medium text-mono">
                                            {{ $eventLabels[$log->event] ?? ucwords(str_replace(['.', '_'], ' ', $log->event)) }}
                                        </span>
                                        @if($log->causer)
                                            <span class="text-xs text-secondary-foreground">by {{ $log->causer->name }}</span>
                                        @endif
                                        @if($log->getProperty('comment'))
                                            <span class="text-sm text-foreground italic">"{{ $log->getProperty('comment') }}"</span>
                                        @endif
                                        <span class="text-xs text-secondary-foreground">{{ $log->created_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                </div>
                            @else
                                @php $comment = $entry['item']; @endphp
                                <div class="flex gap-3">
                                    <div class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <x-tabler-message-filled class="text-sm" />
                                    </div>
                                    <div class="flex flex-col gap-0.5 flex-1">
                                        <span class="text-sm font-medium text-mono">{{ $comment->user->name ?? 'Unknown' }}</span>
                                        <p class="text-sm text-foreground whitespace-pre-line">{{ $comment->body }}</p>
                                        <span class="text-xs text-secondary-foreground">{{ $comment->created_at->format('M d, Y g:i A') }}</span>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-sm text-secondary-foreground text-center py-4">{{ __('retirements.timeline.no_activity') }}</p>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="flex flex-col gap-5 lg:gap-7.5">

                {{-- Actions --}}
                <div class="sgh-card">
                    <div class="sgh-card-header">
                        <h3 class="sgh-card-title">{{ __('payment_requests.show.actions') }}</h3>
                    </div>
                    <div class="sgh-card-content p-5 flex flex-col gap-3">
                        @if($retirementRequest->isDraft())
                            @if($isOwner)
                                <a href="{{ route('retirement-requests.edit', $retirementRequest) }}"
                                   class="sgh-btn sgh-btn-outline w-full">
                                    <x-tabler-pencil-filled />
                                    {{ __('retirements.buttons.edit_request') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('retirement-requests.submit', $retirementRequest) }}">
                                @csrf
                                <button type="submit" class="sgh-btn sgh-btn-primary w-full">
                                    <x-tabler-send-filled />
                                    {{ __('retirements.buttons.submit') }}
                                </button>
                            </form>
                            @if($isOwner)
                                <form method="POST" action="{{ route('retirement-requests.cancel', $retirementRequest) }}">
                                    @csrf
                                    <button type="submit" class="sgh-btn sgh-btn-danger sgh-btn-outline w-full">
                                        <x-tabler-x-filled />
                                        {{ __('payment_requests.buttons.cancel_request') }}
                                    </button>
                                </form>
                            @endif
                        @elseif($retirementRequest->status === 'in_workflow')
                            @if($canActOnActiveStage && $activeInstanceStage)
                                <a href="{{ route('approvals.show', $activeInstanceStage) }}"
                                   class="sgh-btn sgh-btn-primary w-full">
                                    <x-tabler-circle-check-filled />
                                    {{ __('payment_requests.buttons.review_and_approve') }}
                                </a>
                            @else
                                <div class="flex items-center gap-2 p-3 rounded-lg bg-primary/10 text-primary text-sm">
                                    <x-tabler-clock-filled />
                                    {{ __('payment_requests.status.awaiting_approval') }}
                                </div>
                            @endif
                            @if($isOwner)
                                <form method="POST" action="{{ route('retirement-requests.cancel', $retirementRequest) }}">
                                    @csrf
                                    <button type="submit" class="sgh-btn sgh-btn-danger sgh-btn-outline w-full">
                                        <x-tabler-x-filled />
                                        {{ __('payment_requests.buttons.cancel_request') }}
                                    </button>
                                </form>
                            @endif
                        @elseif($retirementRequest->status === 'approved')
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-success/10 text-success text-sm mb-2">
                                <x-tabler-circle-check-filled />
                                {{ __('retirements.status.fully_approved') }}
                            </div>
                            @can(App\Enums\Tenant\PermissionKey::SettleRetirements->value)
                                @if($retirementRequest->difference_type !== 'nil')
                                    <form method="POST" action="{{ route('retirement-requests.settle', $retirementRequest) }}" class="flex flex-col gap-2">
                                        @csrf
                                        <div>
                                            <label class="sgh-form-label text-xs block mb-1">{{ __('retirements.show.settlement_notes') }} <span class="text-muted-foreground">(optional)</span></label>
                                            <textarea name="settlement_notes" rows="2" class="sgh-textarea w-full text-sm" placeholder="e.g. Cheque #1234 issued..."></textarea>
                                        </div>
                                        <button type="submit" class="sgh-btn sgh-btn-primary w-full">
                                            <x-tabler-circle-check-filled />
                                            {{ __('retirements.buttons.settle') }}
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('retirement-requests.settle', $retirementRequest) }}">
                                        @csrf
                                        <button type="submit" class="sgh-btn sgh-btn-primary w-full">
                                            <x-tabler-circle-check-filled />
                                            {{ __('retirements.buttons.settle') }}
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        @elseif($retirementRequest->isSentBack())
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-warning/10 text-warning text-sm mb-2">
                                <x-tabler-info-square-filled />
                                {{ __('retirements.show.sent_back_notice') }}
                            </div>
                            @if($isOwner)
                                <a href="{{ route('retirement-requests.edit', $retirementRequest) }}"
                                   class="sgh-btn sgh-btn-outline w-full">
                                    <x-tabler-pencil-filled />
                                    {{ __('retirements.buttons.edit_request') }}
                                </a>
                                <form method="POST" action="{{ route('retirement-requests.resubmit', $retirementRequest) }}">
                                    @csrf
                                    <button type="submit" class="sgh-btn sgh-btn-primary w-full">
                                        <x-tabler-send-filled />
                                        {{ __('retirements.buttons.resubmit') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('retirement-requests.cancel', $retirementRequest) }}">
                                    @csrf
                                    <button type="submit" class="sgh-btn sgh-btn-danger sgh-btn-outline w-full">
                                        <x-tabler-x-filled />
                                        {{ __('payment_requests.buttons.cancel_request') }}
                                    </button>
                                </form>
                            @endif
                        @elseif($retirementRequest->status === 'settled')
                            <div class="flex flex-col gap-1 p-3 rounded-lg bg-info/10 text-info text-sm">
                                <div class="flex items-center gap-2">
                                    <x-tabler-circle-check-filled />
                                    {{ __('retirements.status.settled') }}
                                </div>
                                @if($retirementRequest->settled_at)
                                    <span class="text-xs">{{ $retirementRequest->settled_at->format('M d, Y') }}</span>
                                @endif
                                @if($retirementRequest->settlement_notes)
                                    <p class="text-xs italic mt-1">{{ $retirementRequest->settlement_notes }}</p>
                                @endif
                            </div>
                        @elseif($retirementRequest->status === 'cancelled')
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-destructive/10 text-destructive text-sm">
                                <x-tabler-circle-x-filled />
                                {{ __('retirements.status.cancelled') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Workflow Progress --}}
                @if($retirementRequest->activeWorkflowInstance)
                    @php $instance = $retirementRequest->activeWorkflowInstance; @endphp
                    <div class="sgh-card">
                        <div class="sgh-card-header">
                            <h3 class="sgh-card-title">{{ __('approvals.show.approval_progress') }}</h3>
                        </div>
                        <div class="sgh-card-content p-5 flex flex-col gap-3">
                            @foreach($instance->instanceStages->sortBy('stage.display_order') as $instanceStage)
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 shrink-0">
                                        @if($instanceStage->status === 'approved')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-success/20 text-success">
                                                <x-tabler-check-filled class="text-xs" />
                                            </span>
                                        @elseif($instanceStage->status === 'active')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary/20 text-primary">
                                                <x-tabler-clock-filled class="text-xs" />
                                            </span>
                                        @elseif($instanceStage->status === 'blocked')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-warning/20 text-warning">
                                                <x-tabler-alert-triangle class="text-xs" />
                                            </span>
                                        @elseif($instanceStage->status === 'rejected')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-destructive/20 text-destructive">
                                                <x-tabler-x-filled class="text-xs" />
                                            </span>
                                        @elseif($instanceStage->status === 'sent_back')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-warning/20 text-warning">
                                                <x-tabler-arrow-left class="text-xs" />
                                            </span>
                                        @else
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-border bg-background text-muted-foreground">
                                                <x-tabler-dots-circle-horizontal class="text-xs" />
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm font-medium text-mono">{{ $instanceStage->stage->name }}</span>
                                        <span class="text-xs text-secondary-foreground capitalize">
                                            {{ str_replace('_', ' ', $instanceStage->status) }}
                                        </span>
                                        <x-workflow-stage-approver-summary
                                            :instance-stage="$instanceStage"
                                            :eligibility="$activeStageEligibility[$instanceStage->id] ?? null"
                                        />
                                        @if($instanceStage->status === 'blocked')
                                            <x-workflow-stage-recovery :instance-stage="$instanceStage" />
                                        @elseif($instanceStage->recoveryRoles->isNotEmpty())
                                            <x-workflow-stage-recovery :instance-stage="$instanceStage" />
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>

        </div>
    </div>
</div>
<x-attachment-preview-modal />
@endsection
