@extends('tenant.layouts.base')

@php
    use App\Enums\Tenant\PermissionKey;
    use App\Enums\Tenant\PaymentMethod;

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
        'advance' => 'kt-badge-primary',
        'expense' => 'kt-badge-warning',
    ];
@endphp

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-medium leading-none text-mono">
                    Request #{{ $paymentRequest->id }}
                </h1>
                <span class="kt-badge kt-badge-sm {{ $typeColors[$paymentRequest->type] ?? 'kt-badge-outline' }}">
                    {{ ucfirst($paymentRequest->type) }}
                </span>
                <span class="kt-badge kt-badge-sm {{ $statusColors[$paymentRequest->status] ?? 'kt-badge-outline' }}">
                    {{ ucwords(str_replace('_', ' ', $paymentRequest->status)) }}
                </span>
            </div>
            <div class="text-sm text-secondary-foreground">
                Created {{ $paymentRequest->created_at->format('M d, Y \a\t H:i') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('payment-requests.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('payment_requests.show.back') }}
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
                        <h3 class="kt-card-title">{{ __('payment_requests.show.request_details') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('payment_requests.show.staff_member') }}</dt>
                                <dd class="text-sm font-medium text-mono">{{ $paymentRequest->staff->full_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.columns.branch') }}</dt>
                                <dd class="text-sm text-foreground">{{ $paymentRequest->branch->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('payment_requests.show.currency') }}</dt>
                                <dd class="text-sm text-foreground">
                                    {{ $paymentRequest->currency->short_name ?? '—' }}
                                    @if($paymentRequest->currency)
                                        — {{ $paymentRequest->currency->name }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('payment_requests.show.total_amount') }}</dt>
                                <dd class="text-lg font-semibold text-mono">
                                    {{ $paymentRequest->currency->symbol ?? '' }}
                                    {{ number_format((float) $paymentRequest->total_amount, 2) }}
                                </dd>
                            </div>
                            @if($paymentRequest->submitted_at)
                                <div>
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('payment_requests.show.submitted') }}</dt>
                                    <dd class="text-sm text-foreground">{{ $paymentRequest->submitted_at->format('M d, Y H:i') }}</dd>
                                </div>
                            @endif
                            @if($paymentRequest->approved_at)
                                <div>
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('payment_requests.show.approved') }}</dt>
                                    <dd class="text-sm text-foreground">{{ $paymentRequest->approved_at->format('M d, Y H:i') }}</dd>
                                </div>
                            @endif
                            @if($paymentRequest->notes)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('payment_requests.show.notes') }}</dt>
                                    <dd class="text-sm text-foreground whitespace-pre-line">{{ $paymentRequest->notes }}</dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </div>

                {{-- Line Items --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('payment_requests.show.line_items') }}</h3>
                        <span class="kt-badge kt-badge-sm kt-badge-outline">
                            {{ $paymentRequest->items->count() }} {{ Str::plural('item', $paymentRequest->items->count()) }}
                        </span>
                    </div>
                    <div class="kt-card-table">
                        <div class="kt-scrollable-x-auto border-b border-border">
                            <table class="kt-table kt-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.description') }}</span></span></th>
                                        @if($paymentRequest->isExpense())
                                            <th><span class="kt-table-col"><span class="kt-table-col-label">{{ __('payment_requests.fields.cost_code') }}</span></span></th>
                                            <th><span class="kt-table-col"><span class="kt-table-col-label">{{ __('payment_requests.show.receipt') }}</span></span></th>
                                        @endif
                                        <th class="w-[160px] text-end"><span class="kt-table-col justify-end"><span class="kt-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentRequest->items as $item)
                                        <tr>
                                            <td><span class="text-sm text-foreground">{{ $item->description }}</span></td>
                                            @if($paymentRequest->isExpense())
                                                <td>
                                                    <span class="text-sm text-mono">
                                                        {{ $item->costCode->code ?? '—' }}
                                                        @if($item->costCode)
                                                            <span class="text-secondary-foreground font-normal">— {{ $item->costCode->name }}</span>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td><span class="text-sm text-secondary-foreground">{{ $item->receipt_number ?? '—' }}</span></td>
                                            @endif
                                            <td class="text-end">
                                                <span class="text-sm font-medium text-mono">
                                                    {{ $paymentRequest->currency->symbol ?? '' }}
                                                    {{ number_format((float) $item->amount, 2) }}
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
                                                {{ $paymentRequest->currency->symbol ?? '' }}
                                                {{ number_format((float) $paymentRequest->total_amount, 2) }}
                                            </span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Timeline: Activity + Comments --}}
                @php
                    $activityLogs = $paymentRequest->activities->sortByDesc('created_at');
                    $comments = $paymentRequest->comments->load('user');

                    $timelineItems = $activityLogs->map(fn($log) => [
                        'type' => 'activity',
                        'at'   => $log->created_at,
                        'item' => $log,
                    ])->merge($comments->map(fn($c) => [
                        'type' => 'comment',
                        'at'   => $c->created_at,
                        'item' => $c,
                    ]))->sortByDesc('at')->values();

                    $eventLabels = [
                        'request.created'    => __('payment_requests.timeline.created_draft'),
                        'request.submitted'  => __('payment_requests.timeline.submitted'),
                        'request.approved'   => __('payment_requests.timeline.fully_approved'),
                        'request.cancelled'  => __('payment_requests.timeline.cancelled'),
                        'request.resubmitted'=> __('payment_requests.timeline.resubmitted'),
                        'request.updated'    => __('payment_requests.timeline.updated'),
                        'stage.approved'     => __('payment_requests.timeline.stage_approved'),
                        'stage.rejected'     => __('payment_requests.timeline.stage_rejected'),
                        'stage.sent_back'    => __('payment_requests.timeline.sent_back'),
                        'request.disbursed'  => __('payment_requests.timeline.disbursed'),
                    ];
                @endphp

                @if($timelineItems->isNotEmpty() || true)
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
                                            @if($log->getProperty('old_status') && $log->getProperty('new_status'))
                                                <span class="text-xs text-secondary-foreground">
                                                    {{ ucwords(str_replace('_', ' ', $log->getProperty('old_status'))) }} → {{ ucwords(str_replace('_', ' ', $log->getProperty('new_status'))) }}
                                                </span>
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
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-sm font-medium text-mono">{{ $comment->user->name ?? 'Unknown' }}</span>
                                                @if(auth()->id() === $comment->user_id)
                                                    <form method="POST" action="{{ route('payment-requests.comments.destroy', [$paymentRequest, $comment]) }}"
                                                          onsubmit="return confirm('Delete this comment?')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-xs text-destructive hover:underline">Delete</button>
                                                    </form>
                                                @endif
                                            </div>
                                            <p class="text-sm text-foreground whitespace-pre-line">{{ $comment->body }}</p>
                                            <span class="text-xs text-secondary-foreground">{{ $comment->created_at->format('M d, Y H:i') }}</span>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <p class="text-sm text-secondary-foreground text-center py-4">{{ __('payment_requests.timeline.no_activity') }}</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- Add Comment --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('common.add_comment') }}</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <form method="POST" action="{{ route('payment-requests.comments.store', $paymentRequest) }}">
                            @csrf
                            <div class="flex flex-col gap-3">
                                <textarea name="body" rows="3"
                                          class="kt-textarea w-full"
                                          placeholder="{{ __('common.leave_comment') }}"
                                          aria-invalid="@error('body') true @else false @enderror">{{ old('body') }}</textarea>
                                @error('body')
                                    <p class="text-sm text-destructive">{{ $message }}</p>
                                @enderror
                                <div>
                                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline">
                                        <i class="ki-filled ki-message-text"></i>
                                        {{ __('common.post_comment') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            {{-- Sidebar: Actions + Workflow Status --}}
            <div class="flex flex-col gap-5 lg:gap-7.5">

                {{-- Actions --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('payment_requests.show.actions') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-3">
                        @if($paymentRequest->isDraft())
                            <form method="POST" action="{{ route('payment-requests.submit', $paymentRequest) }}">
                                @csrf
                                <button type="submit" class="kt-btn kt-btn-primary w-full">
                                    <i class="ki-filled ki-send"></i>
                                    {{ __('payment_requests.buttons.submit') }}
                                </button>
                            </form>
                            @can(PermissionKey::DeletePaymentRequest->value)
                                <button type="button"
                                        class="kt-btn kt-btn-danger kt-btn-outline w-full"
                                        onclick="if(confirm('{{ __('payment_requests.confirm_delete_draft') }}')) { document.getElementById('delete-form').submit(); }">
                                    <i class="ki-filled ki-trash"></i>
                                    {{ __('payment_requests.buttons.delete_draft') }}
                                </button>
                                <form id="delete-form" method="POST" action="{{ route('payment-requests.destroy', $paymentRequest) }}" class="hidden">
                                    @csrf @method('DELETE')
                                </form>
                            @endcan
                        @elseif($paymentRequest->status === 'in_workflow')
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
                        @elseif($paymentRequest->status === 'approved')
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-success/10 text-success text-sm mb-2">
                                <i class="ki-filled ki-check-circle"></i>
                                {{ __('payment_requests.status.approved_awaiting') }}
                            </div>
                            @can(PermissionKey::DisburseRequests->value)
                                <form method="POST" action="{{ route('disbursements.store', $paymentRequest) }}" class="flex flex-col gap-3">
                                    @csrf
                                    <div>
                                        <label class="kt-form-label block mb-1.5 text-sm" for="disbursement_method">{{ __('payment_requests.show.payment_method') }} <span class="text-destructive">*</span></label>
                                        <select id="disbursement_method" name="disbursement_method"
                                                class="kt-select w-full"
                                                aria-invalid="@error('disbursement_method') true @else false @enderror">
                                            <option value="">— Select method —</option>
                                            @foreach(PaymentMethod::cases() as $method)
                                                <option value="{{ $method->value }}" @selected(old('disbursement_method') === $method->value)>
                                                    {{ $method->label() }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('disbursement_method')
                                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="kt-form-label block mb-1.5 text-sm" for="disbursement_reference">Reference <span class="text-secondary-foreground text-xs font-normal">(optional)</span></label>
                                        <input id="disbursement_reference" name="disbursement_reference" type="text"
                                               class="kt-input w-full"
                                               placeholder="{{ __('payment_requests.show.transaction_ref') }}"
                                               value="{{ old('disbursement_reference') }}"
                                               aria-invalid="@error('disbursement_reference') true @else false @enderror">
                                        @error('disbursement_reference')
                                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="submit" class="kt-btn kt-btn-success w-full"
                                            onclick="return confirm('{{ __('payment_requests.confirm_disburse') }}')">
                                        <i class="ki-filled ki-dollar"></i>
                                        {{ __('payment_requests.buttons.disburse') }}
                                    </button>
                                </form>
                            @endcan
                        @elseif($paymentRequest->status === 'sent_back')
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-warning/10 text-warning text-sm mb-2">
                                <i class="ki-filled ki-information-2"></i>
                                {{ __('payment_requests.show.sent_back_notice') }}
                            </div>
                            @if($isOwner)
                                <a href="{{ route('payment-requests.edit', $paymentRequest) }}"
                                   class="kt-btn kt-btn-outline w-full">
                                    <i class="ki-filled ki-pencil"></i>
                                    {{ __('payment_requests.buttons.edit_request') }}
                                </a>
                                <form method="POST" action="{{ route('payment-requests.resubmit', $paymentRequest) }}">
                                    @csrf
                                    <button type="submit" class="kt-btn kt-btn-primary w-full">
                                        <i class="ki-filled ki-send"></i>
                                        {{ __('payment_requests.buttons.resubmit') }}
                                    </button>
                                </form>
                            @endif
                        @elseif($paymentRequest->status === 'disbursed')
                            @can(PermissionKey::CreateRetirementRequest->value)
                                @if($isOwner && ! $paymentRequest->retirementRequest)
                                    <a href="{{ route('retirement-requests.create', $paymentRequest) }}"
                                       class="kt-btn kt-btn-primary w-full">
                                        <i class="ki-filled ki-file-up"></i>
                                        {{ __('payment_requests.buttons.retire') }}
                                    </a>
                                @elseif($paymentRequest->retirementRequest)
                                    <a href="{{ route('retirement-requests.show', $paymentRequest->retirementRequest) }}"
                                       class="kt-btn kt-btn-outline w-full">
                                        <i class="ki-filled ki-eye"></i>
                                        {{ __('payment_requests.buttons.view_retirement') }}
                                    </a>
                                @endif
                            @endcan
                            <div class="flex flex-col gap-2 p-3 rounded-lg bg-info/10 text-info text-sm">
                                <div class="flex items-center gap-2">
                                    <i class="ki-filled ki-dollar"></i>
                                    {{ __('payment_requests.show.disbursed_on') }} {{ $paymentRequest->disbursed_at?->format('M d, Y') }}
                                </div>
                                @if($paymentRequest->disbursement_method)
                                    <span class="text-xs">{{ __('payment_requests.show.method_label') }} {{ $paymentRequest->disbursement_method->label() }}</span>
                                @endif
                                @if($paymentRequest->disbursement_reference)
                                    <span class="text-xs">{{ __('payment_requests.show.ref_label') }} {{ $paymentRequest->disbursement_reference }}</span>
                                @endif
                            </div>
                        @elseif($paymentRequest->status === 'cancelled')
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-destructive/10 text-destructive text-sm">
                                <i class="ki-filled ki-cross-circle"></i>
                                {{ __('payment_requests.timeline.cancelled') }}
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Workflow Status --}}
                @if($paymentRequest->activeWorkflowInstance)
                    @php $instance = $paymentRequest->activeWorkflowInstance; @endphp
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">{{ __('payment_requests.show.approval_progress') }}</h3>
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
                                        @elseif($instanceStage->status === 'skipped')
                                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-muted-foreground">
                                                <i class="ki-filled ki-minus text-xs"></i>
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
                                            @if($instanceStage->stage->roles->isNotEmpty())
                                                · {{ $instanceStage->stage->roles->pluck('name')->join(', ') }}
                                            @endif
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
