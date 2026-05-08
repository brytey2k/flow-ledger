@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Retire Advance #{{ $paymentRequest->id }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Record actual expenditure for {{ $paymentRequest->staff->full_name ?? '—' }}
            </div>
        </div>
        <a class="kt-btn kt-btn-outline" href="{{ route('payment-requests.show', $paymentRequest) }}">
            <i class="ki-filled ki-arrow-left"></i>
            Back to Advance
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <form method="POST" action="{{ route('retirement-requests.store', $paymentRequest) }}" id="retirement-form">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

            {{-- Line Items --}}
            <div class="lg:col-span-2 flex flex-col gap-5 lg:gap-7.5">

                {{-- Advance Summary --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Advance Summary</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Staff</dt>
                                <dd class="text-sm font-medium text-mono">{{ $paymentRequest->staff->full_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Branch</dt>
                                <dd class="text-sm text-foreground">{{ $paymentRequest->branch->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">Advance Amount</dt>
                                <dd class="text-lg font-semibold text-mono">
                                    {{ $paymentRequest->currency->symbol ?? '' }} {{ number_format((float) $paymentRequest->total_amount, 2) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Expenditure Items --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Expenditure Items</h3>
                    </div>
                    <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
                        @error('items')
                            <p class="mb-3 text-sm text-destructive">{{ $message }}</p>
                        @enderror

                        <div id="items-container" class="flex flex-col gap-4">
                            @php $oldItems = old('items', [[]]); @endphp
                            @foreach($oldItems as $index => $oldItem)
                                <div class="item-row grid grid-cols-1 sm:grid-cols-12 gap-3 p-4 rounded-lg border border-border relative">
                                    <div class="sm:col-span-4">
                                        <label class="kt-form-label block mb-1.5 text-sm">Description <span class="text-destructive">*</span></label>
                                        <input type="text" name="items[{{ $index }}][description]"
                                               class="kt-input w-full"
                                               value="{{ $oldItem['description'] ?? '' }}"
                                               placeholder="What was purchased"
                                               aria-invalid="@error('items.{{ $index }}.description') true @else false @enderror">
                                        @error("items.{$index}.description")
                                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="kt-form-label block mb-1.5 text-sm">Account Code <span class="text-destructive">*</span></label>
                                        <select name="items[{{ $index }}][account_code_id]" class="kt-select w-full"
                                                aria-invalid="@error('items.{{ $index }}.account_code_id') true @else false @enderror">
                                            <option value="">Select…</option>
                                            @foreach($accountCodes as $code)
                                                <option value="{{ $code->id }}"
                                                    {{ isset($oldItem['account_code_id']) && $oldItem['account_code_id'] == $code->id ? 'selected' : '' }}>
                                                    {{ $code->code }} — {{ $code->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("items.{$index}.account_code_id")
                                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="kt-form-label block mb-1.5 text-sm">Amount <span class="text-destructive">*</span></label>
                                        <input type="number" name="items[{{ $index }}][amount]"
                                               class="kt-input w-full item-amount"
                                               value="{{ $oldItem['amount'] ?? '' }}"
                                               step="0.01" min="0.01"
                                               placeholder="0.00"
                                               aria-invalid="@error('items.{{ $index }}.amount') true @else false @enderror">
                                        @error("items.{$index}.amount")
                                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="kt-form-label block mb-1.5 text-sm">Receipt No.</label>
                                        <input type="text" name="items[{{ $index }}][receipt_number]"
                                               class="kt-input w-full"
                                               value="{{ $oldItem['receipt_number'] ?? '' }}"
                                               placeholder="Optional">
                                    </div>
                                    <div class="sm:col-span-1 flex items-end justify-end">
                                        <button type="button" class="remove-item kt-btn kt-btn-sm kt-btn-icon kt-btn-danger kt-btn-outline"
                                                title="Remove item">
                                            <i class="ki-filled ki-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" id="add-item"
                                class="mt-4 kt-btn kt-btn-sm kt-btn-outline">
                            <i class="ki-filled ki-plus"></i>
                            Add Item
                        </button>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Notes</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <textarea name="notes" rows="3"
                                  class="kt-textarea w-full"
                                  placeholder="Optional notes…"
                                  aria-invalid="@error('notes') true @else false @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

            </div>

            {{-- Sidebar --}}
            <div class="flex flex-col gap-5 lg:gap-7.5">
                <div class="kt-card sticky top-5">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">Summary</h3>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">Advance Amount</span>
                            <span class="font-medium text-mono">
                                {{ $paymentRequest->currency->symbol ?? '' }} {{ number_format((float) $paymentRequest->total_amount, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">Total Expended</span>
                            <span class="font-medium text-mono" id="total-expended">
                                {{ $paymentRequest->currency->symbol ?? '' }} 0.00
                            </span>
                        </div>
                        <hr class="border-border">
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">Difference</span>
                            <span class="font-semibold text-mono" id="difference-display">—</span>
                        </div>
                        <div class="text-xs text-secondary-foreground" id="difference-label"></div>

                        <button type="submit" class="kt-btn kt-btn-primary w-full mt-2">
                            <i class="ki-filled ki-check"></i>
                            Save as Draft
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<template id="item-template">
    <div class="item-row grid grid-cols-1 sm:grid-cols-12 gap-3 p-4 rounded-lg border border-border relative">
        <div class="sm:col-span-4">
            <label class="kt-form-label block mb-1.5 text-sm">Description <span class="text-destructive">*</span></label>
            <input type="text" name="items[__INDEX__][description]" class="kt-input w-full" placeholder="What was purchased">
        </div>
        <div class="sm:col-span-3">
            <label class="kt-form-label block mb-1.5 text-sm">Account Code <span class="text-destructive">*</span></label>
            <select name="items[__INDEX__][account_code_id]" class="kt-select w-full">
                <option value="">Select…</option>
                @foreach($accountCodes as $code)
                    <option value="{{ $code->id }}">{{ $code->code }} — {{ $code->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="kt-form-label block mb-1.5 text-sm">Amount <span class="text-destructive">*</span></label>
            <input type="number" name="items[__INDEX__][amount]" class="kt-input w-full item-amount" step="0.01" min="0.01" placeholder="0.00">
        </div>
        <div class="sm:col-span-2">
            <label class="kt-form-label block mb-1.5 text-sm">Receipt No.</label>
            <input type="text" name="items[__INDEX__][receipt_number]" class="kt-input w-full" placeholder="Optional">
        </div>
        <div class="sm:col-span-1 flex items-end justify-end">
            <button type="button" class="remove-item kt-btn kt-btn-sm kt-btn-icon kt-btn-danger kt-btn-outline" title="Remove item">
                <i class="ki-filled ki-trash"></i>
            </button>
        </div>
    </div>
</template>

<script>
(function () {
    const advanceAmount = {{ (float) $paymentRequest->total_amount }};
    const symbol = '{{ $paymentRequest->currency->symbol ?? '' }}';
    let itemIndex = {{ count(old('items', [[]])) }};

    function updateTotals() {
        let total = 0;
        document.querySelectorAll('.item-amount').forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        const diff = total - advanceAmount;

        document.getElementById('total-expended').textContent = symbol + ' ' + total.toFixed(2);

        const diffEl = document.getElementById('difference-display');
        const labelEl = document.getElementById('difference-label');

        if (Math.abs(diff) < 0.005) {
            diffEl.textContent = symbol + ' 0.00';
            labelEl.textContent = 'No difference — fully retired.';
            diffEl.className = 'font-semibold text-mono text-success';
        } else if (diff > 0) {
            diffEl.textContent = symbol + ' ' + diff.toFixed(2);
            labelEl.textContent = 'Staff spent more — company owes the difference.';
            diffEl.className = 'font-semibold text-mono text-warning';
        } else {
            diffEl.textContent = symbol + ' ' + Math.abs(diff).toFixed(2);
            labelEl.textContent = 'Staff spent less — refund required.';
            diffEl.className = 'font-semibold text-mono text-destructive';
        }
    }

    document.getElementById('add-item').addEventListener('click', function () {
        const template = document.getElementById('item-template').innerHTML.replace(/__INDEX__/g, itemIndex++);
        const container = document.getElementById('items-container');
        const div = document.createElement('div');
        div.innerHTML = template;
        const row = div.firstElementChild;
        container.appendChild(row);
        row.querySelector('.remove-item').addEventListener('click', () => { row.remove(); updateTotals(); });
        row.querySelector('.item-amount').addEventListener('input', updateTotals);
    });

    document.getElementById('items-container').addEventListener('click', function (e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.item-row').remove();
            updateTotals();
        }
    });

    document.getElementById('items-container').addEventListener('input', function (e) {
        if (e.target.classList.contains('item-amount')) updateTotals();
    });

    updateTotals();
})();
</script>
@endsection
