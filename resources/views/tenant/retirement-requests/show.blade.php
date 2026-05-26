@extends('tenant.layouts.base')

@php
    use App\Enums\Tenant\PermissionKey;

    $statusColors = [
        'draft'       => 'kt-badge-outline',
        'in_workflow' => 'kt-badge-primary',
        'approved'    => 'kt-badge-success',
        'settled'     => 'kt-badge-info',
        'sent_back'   => 'kt-badge-warning',
        'cancelled'   => 'kt-badge-danger',
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
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-medium leading-none text-mono">Retirement #{{ $retirementRequest->id }}</h1>
                <span class="kt-badge kt-badge-sm {{ $statusColors[$retirementRequest->status] ?? 'kt-badge-outline' }}">
                    {{ ucwords(str_replace('_', ' ', $retirementRequest->status)) }}
                </span>
            </div>
            <div class="text-sm text-secondary-foreground">
                {{ __('retirements.show.for_advance') }} <a href="{{ route('payment-requests.show', $pr) }}" class="text-primary hover:underline">#{{ $pr->id }}</a>
                &mdash; {{ $pr->staff->full_name ?? '—' }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('retirement-requests.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('retirements.show.back') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

            {{-- Main content --}}
            <div class="lg:col-span-2 flex flex-col gap-5 lg:gap-7.5">

                {{-- Summary --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('retirements.show.summary_card') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
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
                                    <dd class="text-sm text-foreground">{{ $retirementRequest->submitted_at->format('M d, Y H:i') }}</dd>
                                </div>
                            @endif
                            @if($retirementRequest->approved_at)
                                <div>
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('payment_requests.show.approved') }}</dt>
                                    <dd class="text-sm text-foreground">{{ $retirementRequest->approved_at->format('M d, Y H:i') }}</dd>
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
                    </div>
                </div>

                {{-- Expenditure Items --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('retirements.fields.expenditure_items') }}</h3>
                        <span class="kt-badge kt-badge-sm kt-badge-outline">
                            {{ $retirementRequest->items->count() }} {{ Str::plural('item', $retirementRequest->items->count()) }}
                        </span>
                    </div>
                    <div class="kt-card-table">
                        <div class="kt-scrollable-x-auto border-b border-border">
                            <table class="kt-table kt-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.description') }}</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">{{ __('retirements.fields.cost_code') }}</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">{{ __('payment_requests.show.receipt') }}</span></span></th>
                                        <th class="w-[140px] text-end"><span class="kt-table-col justify-end"><span class="kt-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
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
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('retirements.show.attachments_card') }}</h3>
                        <span class="kt-badge kt-badge-sm kt-badge-outline">
                            {{ $retirementRequest->attachments->count() }} {{ Str::plural('file', $retirementRequest->attachments->count()) }}
                        </span>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-4">
                        @forelse($retirementRequest->attachments as $attachment)
                            <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-border">
                                <div class="flex items-center gap-3 min-w-0">
                                    <i class="ki-filled ki-file text-muted-foreground shrink-0"></i>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-mono truncate">{{ $attachment->original_name }}</p>
                                        <p class="text-xs text-secondary-foreground">{{ $attachment->formattedSize() }} &bull; {{ $attachment->created_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('attachments.download', $attachment) }}"
                                       class="kt-btn kt-btn-sm kt-btn-outline">
                                        <i class="ki-filled ki-cloud-download"></i>
                                    </a>
                                    @if(optional($attachment->attachable->paymentRequest->staff)->user_id === auth()->id())
                                        <form method="POST" action="{{ route('attachments.destroy', $attachment) }}" onsubmit="return confirm('{{ __('retirements.show.confirm_delete_attachment') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline text-destructive hover:bg-destructive/10">
                                                <i class="ki-filled ki-trash"></i>
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
                                        <i class="ki-filled ki-cloud-add text-2xl text-muted-foreground"></i>
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

                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('payment_requests.show.timeline') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-4">
                        @forelse($timelineItems as $entry)
                            @if($entry['type'] === 'activity')
                                @php $log = $entry['item']; @endphp
                                <div class="flex gap-3">
                                    <div class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                        <i class="ki-filled ki-information text-sm"></i>
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
                                        <span class="text-xs text-secondary-foreground">{{ $log->created_at->format('M d, Y H:i') }}</span>
                                    </div>
                                </div>
                            @else
                                @php $comment = $entry['item']; @endphp
                                <div class="flex gap-3">
                                    <div class="shrink-0 flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 text-primary">
                                        <i class="ki-filled ki-message-text text-sm"></i>
                                    </div>
                                    <div class="flex flex-col gap-0.5 flex-1">
                                        <span class="text-sm font-medium text-mono">{{ $comment->user->name ?? 'Unknown' }}</span>
                                        <p class="text-sm text-foreground whitespace-pre-line">{{ $comment->body }}</p>
                                        <span class="text-xs text-secondary-foreground">{{ $comment->created_at->format('M d, Y H:i') }}</span>
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
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('payment_requests.show.actions') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-3">
                        @if($retirementRequest->isDraft())
                            @if($isOwner)
                                <a href="{{ route('retirement-requests.edit', $retirementRequest) }}"
                                   class="kt-btn kt-btn-outline w-full">
                                    <i class="ki-filled ki-pencil"></i>
                                    {{ __('retirements.buttons.edit_request') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('retirement-requests.submit', $retirementRequest) }}">
                                @csrf
                                <button type="submit" class="kt-btn kt-btn-primary w-full">
                                    <i class="ki-filled ki-send"></i>
                                    {{ __('retirements.buttons.submit') }}
                                </button>
                            </form>
                            @if($isOwner)
                                <form method="POST" action="{{ route('retirement-requests.cancel', $retirementRequest) }}">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-danger kt-btn-outline w-full">
                                        <i class="ki-filled ki-close"></i>
                                        {{ __('payment_requests.buttons.cancel_request') }}
                                    </button>
                                </form>
                            @endif
                        @elseif($retirementRequest->status === 'in_workflow')
                            @if($canActOnActiveStage && $activeInstanceStage)
                                <a href="{{ route('approvals.show', $activeInstanceStage) }}"
                                   class="kt-btn kt-btn-primary w-full">
                                    <i class="ki-filled ki-check-circle"></i>
                                    {{ __('payment_requests.buttons.review_and_approve') }}
                                </a>
                            @else
                                <div class="flex items-center gap-2 p-3 rounded-lg bg-primary/10 text-primary text-sm">
                                    <i class="ki-filled ki-time"></i>
                                    {{ __('payment_requests.status.awaiting_approval') }}
                                </div>
                            @endif
                            @if($isOwner)
                                <form method="POST" action="{{ route('retirement-requests.cancel', $retirementRequest) }}">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-danger kt-btn-outline w-full">
                                        <i class="ki-filled ki-close"></i>
                                        {{ __('payment_requests.buttons.cancel_request') }}
                                    </button>
                                </form>
                            @endif
                        @elseif($retirementRequest->status === 'approved')
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-success/10 text-success text-sm mb-2">
                                <i class="ki-filled ki-check-circle"></i>
                                {{ __('retirements.status.fully_approved') }}
                            </div>
                            @can(App\Enums\Tenant\PermissionKey::SettleRetirements->value)
                                @if($retirementRequest->difference_type !== 'nil')
                                    <form method="POST" action="{{ route('retirement-requests.settle', $retirementRequest) }}" class="flex flex-col gap-2">
                                        @csrf
                                        <div>
                                            <label class="kt-form-label text-xs block mb-1">{{ __('retirements.show.settlement_notes') }} <span class="text-muted-foreground">(optional)</span></label>
                                            <textarea name="settlement_notes" rows="2" class="kt-textarea w-full text-sm" placeholder="e.g. Cheque #1234 issued..."></textarea>
                                        </div>
                                        <button type="submit" class="kt-btn kt-btn-primary w-full">
                                            <i class="ki-filled ki-check-circle"></i>
                                            {{ __('retirements.buttons.settle') }}
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('retirement-requests.settle', $retirementRequest) }}">
                                        @csrf
                                        <button type="submit" class="kt-btn kt-btn-primary w-full">
                                            <i class="ki-filled ki-check-circle"></i>
                                            {{ __('retirements.buttons.settle') }}
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        @elseif($retirementRequest->isSentBack())
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-warning/10 text-warning text-sm mb-2">
                                <i class="ki-filled ki-information-2"></i>
                                {{ __('retirements.show.sent_back_notice') }}
                            </div>
                            @if($isOwner)
                                <a href="{{ route('retirement-requests.edit', $retirementRequest) }}"
                                   class="kt-btn kt-btn-outline w-full">
                                    <i class="ki-filled ki-pencil"></i>
                                    {{ __('retirements.buttons.edit_request') }}
                                </a>
                                <form method="POST" action="{{ route('retirement-requests.resubmit', $retirementRequest) }}">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-primary w-full">
                                        <i class="ki-filled ki-send"></i>
                                        {{ __('retirements.buttons.resubmit') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('retirement-requests.cancel', $retirementRequest) }}">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-danger kt-btn-outline w-full">
                                        <i class="ki-filled ki-close"></i>
                                        {{ __('payment_requests.buttons.cancel_request') }}
                                    </button>
                                </form>
                            @endif
                        @elseif($retirementRequest->status === 'settled')
                            <div class="flex flex-col gap-1 p-3 rounded-lg bg-info/10 text-info text-sm">
                                <div class="flex items-center gap-2">
                                    <i class="ki-filled ki-check-circle"></i>
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
                                <i class="ki-filled ki-cross-circle"></i>
                                {{ __('retirements.status.cancelled') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Workflow Progress --}}
                @if($retirementRequest->activeWorkflowInstance)
                    @php $instance = $retirementRequest->activeWorkflowInstance; @endphp
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">{{ __('approvals.show.approval_progress') }}</h3>
                        </div>
                        <div class="kt-card-content p-5 flex flex-col gap-3">
                            @foreach($instance->instanceStages->sortBy('stage.display_order') as $instanceStage)
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 shrink-0">
                                        @if($instanceStage->status === 'approved')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-success/20 text-success">
                                                <i class="ki-filled ki-check text-xs"></i>
                                            </span>
                                        @elseif($instanceStage->status === 'active')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary/20 text-primary">
                                                <i class="ki-filled ki-time text-xs"></i>
                                            </span>
                                        @elseif($instanceStage->status === 'rejected')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-destructive/20 text-destructive">
                                                <i class="ki-filled ki-cross text-xs"></i>
                                            </span>
                                        @elseif($instanceStage->status === 'sent_back')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-warning/20 text-warning">
                                                <i class="ki-filled ki-arrow-left text-xs"></i>
                                            </span>
                                        @else
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full border-2 border-border bg-background text-muted-foreground">
                                                <i class="ki-filled ki-dots-circle text-xs"></i>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm font-medium text-mono">{{ $instanceStage->stage->name }}</span>
                                        <span class="text-xs text-secondary-foreground capitalize">
                                            {{ str_replace('_', ' ', $instanceStage->status) }}
                                        </span>
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
@endsection
