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
        'pay_to_staff'      => ['label' => 'Pay to Staff',       'class' => 'bg-warning/10 text-warning'],
        'refund_to_company' => ['label' => 'Refund to Company',  'class' => 'bg-destructive/10 text-destructive'],
        'nil'               => ['label' => 'No Difference',      'class' => 'bg-success/10 text-success'],
    ];
    $eventLabels = [
        'retirement.created'    => 'Created as draft',
        'retirement.submitted'  => 'Submitted for approval',
        'retirement.approved'   => 'Fully approved',
        'retirement.cancelled'  => 'Cancelled',
        'retirement.settled'    => 'Difference settled',
        'retirement.resubmitted'=> 'Resubmitted for approval',
        'stage.approved'        => 'Stage approved',
        'stage.rejected'        => 'Stage rejected',
        'stage.sent_back'       => 'Sent back for revision',
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
                For Advance <a href="{{ route('payment-requests.show', $pr) }}" class="text-primary hover:underline">#{{ $pr->id }}</a>
                &mdash; {{ $pr->staff->full_name ?? '—' }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('retirement-requests.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Retirements
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        @if(session('success'))
            <div class="kt-alert kt-alert-success">
                <i class="ki-filled ki-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="kt-alert kt-alert-danger">
                <i class="ki-filled ki-information"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

            {{-- Main content --}}
            <div class="lg:col-span-2 flex flex-col gap-5 lg:gap-7.5">

                {{-- Summary --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Retirement Summary</h3>
                    </div>
                    <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Staff Member</dt>
                                <dd class="text-sm font-medium text-mono">{{ $pr->staff->full_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Branch</dt>
                                <dd class="text-sm text-foreground">{{ $pr->branch->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Advance Amount</dt>
                                <dd class="text-sm font-medium text-mono">
                                    {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $pr->total_amount, 2) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Total Expended</dt>
                                <dd class="text-lg font-semibold text-mono">
                                    {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $retirementRequest->total_amount_expended, 2) }}
                                </dd>
                            </div>
                            @if($retirementRequest->submitted_at)
                                <div>
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Submitted</dt>
                                    <dd class="text-sm text-foreground">{{ $retirementRequest->submitted_at->format('M d, Y H:i') }}</dd>
                                </div>
                            @endif
                            @if($retirementRequest->approved_at)
                                <div>
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Approved</dt>
                                    <dd class="text-sm text-foreground">{{ $retirementRequest->approved_at->format('M d, Y H:i') }}</dd>
                                </div>
                            @endif
                            @if($retirementRequest->notes)
                                <div class="sm:col-span-2">
                                    <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Notes</dt>
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
                        <h3 class="kt-card-title">Expenditure Items</h3>
                        <span class="kt-badge kt-badge-sm kt-badge-outline">
                            {{ $retirementRequest->items->count() }} {{ Str::plural('item', $retirementRequest->items->count()) }}
                        </span>
                    </div>
                    <div class="kt-card-table">
                        <div class="kt-scrollable-x-auto border-b border-border">
                            <table class="kt-table kt-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Description</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Account Code</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Receipt</span></span></th>
                                        <th class="w-[140px] text-end"><span class="kt-table-col justify-end"><span class="kt-table-col-label">Amount</span></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($retirementRequest->items as $item)
                                        <tr>
                                            <td><span class="text-sm text-foreground">{{ $item->description }}</span></td>
                                            <td>
                                                <span class="text-sm text-mono">
                                                    {{ $item->accountCode->code ?? '—' }}
                                                    @if($item->accountCode)
                                                        <span class="text-secondary-foreground font-normal">— {{ $item->accountCode->name }}</span>
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
                                        <td colspan="3" class="text-end text-sm font-medium text-secondary-foreground">Total Expended</td>
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
                        <h3 class="kt-card-title">Attachments</h3>
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
                                @can(App\Enums\Tenant\PermissionKey::DeleteAttachment->value)
                                    <form method="POST" action="{{ route('attachments.destroy', $attachment) }}" onsubmit="return confirm('Delete this attachment?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline text-destructive hover:bg-destructive/10">
                                            <i class="ki-filled ki-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        @empty
                            <p class="text-sm text-secondary-foreground text-center py-2">No attachments yet.</p>
                        @endforelse

                        @can(App\Enums\Tenant\PermissionKey::CreateRetirementRequest->value)
                            @if(!in_array($retirementRequest->status, ['settled', 'cancelled']))
                                <form method="POST" action="{{ route('retirement-requests.attachments.store', $retirementRequest) }}" enctype="multipart/form-data" class="mt-2">
                                    @csrf
                                    <div class="flex items-center gap-3">
                                        <input type="file" name="file" class="block text-sm text-secondary-foreground file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-muted file:text-foreground hover:file:bg-muted/80 flex-1" />
                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline shrink-0">
                                            <i class="ki-filled ki-upload"></i>
                                            Upload
                                        </button>
                                    </div>
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
                        <h3 class="kt-card-title">Timeline</h3>
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
                            <p class="text-sm text-secondary-foreground text-center py-4">No activity yet.</p>
                        @endforelse
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="flex flex-col gap-5 lg:gap-7.5">

                {{-- Actions --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Actions</h3>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-3">
                        @if($retirementRequest->isDraft())
                            <form method="POST" action="{{ route('retirement-requests.submit', $retirementRequest) }}">
                                @csrf
                                <button type="submit" class="kt-btn kt-btn-primary w-full">
                                    <i class="ki-filled ki-send"></i>
                                    Submit for Approval
                                </button>
                            </form>
                        @elseif($retirementRequest->status === 'in_workflow')
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-primary/10 text-primary text-sm">
                                <i class="ki-filled ki-time"></i>
                                Awaiting approval
                            </div>
                        @elseif($retirementRequest->status === 'approved')
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-success/10 text-success text-sm mb-2">
                                <i class="ki-filled ki-check-circle"></i>
                                Fully approved
                            </div>
                            @can(App\Enums\Tenant\PermissionKey::SettleRetirements->value)
                                @if($retirementRequest->difference_type !== 'nil')
                                    <form method="POST" action="{{ route('retirement-requests.settle', $retirementRequest) }}" class="flex flex-col gap-2">
                                        @csrf
                                        <div>
                                            <label class="kt-form-label text-xs block mb-1">Settlement Notes <span class="text-muted-foreground">(optional)</span></label>
                                            <textarea name="settlement_notes" rows="2" class="kt-textarea w-full text-sm" placeholder="e.g. Cheque #1234 issued..."></textarea>
                                        </div>
                                        <button type="submit" class="kt-btn kt-btn-primary w-full">
                                            <i class="ki-filled ki-check-circle"></i>
                                            Mark as Settled
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('retirement-requests.settle', $retirementRequest) }}">
                                        @csrf
                                        <button type="submit" class="kt-btn kt-btn-primary w-full">
                                            <i class="ki-filled ki-check-circle"></i>
                                            Mark as Settled
                                        </button>
                                    </form>
                                @endif
                            @endcan
                        @elseif($retirementRequest->isSentBack())
                            <div class="flex items-center gap-2 p-3 rounded-lg bg-warning/10 text-warning text-sm mb-2">
                                <i class="ki-filled ki-information-2"></i>
                                This retirement was sent back for review
                            </div>
                            <form method="POST" action="{{ route('retirement-requests.resubmit', $retirementRequest) }}">
                                @csrf
                                <button type="submit" class="kt-btn kt-btn-primary w-full">
                                    <i class="ki-filled ki-send"></i>
                                    Resubmit for Approval
                                </button>
                            </form>
                        @elseif($retirementRequest->status === 'settled')
                            <div class="flex flex-col gap-1 p-3 rounded-lg bg-info/10 text-info text-sm">
                                <div class="flex items-center gap-2">
                                    <i class="ki-filled ki-check-circle"></i>
                                    Settled
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
                                Cancelled
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Workflow Progress --}}
                @if($retirementRequest->activeWorkflowInstance)
                    @php $instance = $retirementRequest->activeWorkflowInstance; @endphp
                    <div class="kt-card">
                        <div class="kt-card-header">
                            <h3 class="kt-card-title">Approval Progress</h3>
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
