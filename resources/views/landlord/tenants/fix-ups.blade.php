@extends('landlord.layouts.base')

@section('content')
@include('landlord.tenants.partials.admin-nav')
<div class="sgh-container-fixed">
    <div class="flex flex-col gap-2 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Fix-ups</h1>
        <p class="text-sm text-secondary-foreground">Targeted repairs for a single record. Every run is recorded in the audit log.</p>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        @foreach($fixUps as $fixUp)
            <div class="sgh-card">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title">{{ $fixUp->label() }}</h3>
                    <p class="sgh-card-subtitle">{{ $fixUp->description() }}</p>
                </div>
                <div class="sgh-card-content">
                    <form method="POST" action="{{ route('landlord.tenants.fix-ups.run', [$tenant, $fixUp->value]) }}" class="flex flex-wrap items-end gap-4">
                        @csrf
                        @if($fixUp === \App\Enums\TenantFixUp::ForceCancelRequest)
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium" for="kind_{{ $fixUp->value }}">Request type</label>
                                <select id="kind_{{ $fixUp->value }}" name="kind" class="sgh-select w-48" required>
                                    <option value="payment">Payment request</option>
                                    <option value="retirement">Retirement request</option>
                                </select>
                            </div>
                        @endif
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium" for="record_{{ $fixUp->value }}">
                                {{ match($fixUp) {
                                    \App\Enums\TenantFixUp::ReopenRetirement => 'Retirement ID',
                                    \App\Enums\TenantFixUp::ResendRetirementReminders => 'Payment request ID (optional)',
                                    default => 'Request ID',
                                } }}
                            </label>
                            <input id="record_{{ $fixUp->value }}" name="record_id" type="number" min="1" class="sgh-input w-48" @required($fixUp !== \App\Enums\TenantFixUp::ResendRetirementReminders)>
                        </div>
                        <button type="submit" class="sgh-btn sgh-btn-primary">Run</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
