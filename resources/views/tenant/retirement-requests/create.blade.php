@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('retirements.create.title', ['id' => $paymentRequest->id]) }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('retirements.create.subtitle', ['name' => $paymentRequest->staff->full_name]) }}
            </div>
        </div>
        <a class="kt-btn kt-btn-outline" href="{{ route('payment-requests.show', $paymentRequest) }}">
            <i class="ki-filled ki-arrow-left"></i>
            {{ __('retirements.create.back') }}
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <form method="POST" action="{{ route('retirement-requests.store', $paymentRequest) }}" id="retirement-form">
        @csrf

        <div class="flex flex-col gap-5 lg:gap-7.5">

            {{-- Advance Summary --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">{{ __('retirements.fields.advance_summary') }}</h3>
                </div>
                <div class="kt-card-content p-5">
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <div>
                            <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.columns.staff') }}</dt>
                            <dd class="text-sm font-medium text-mono">{{ $paymentRequest->staff->full_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.columns.branch') }}</dt>
                            <dd class="text-sm text-foreground">{{ $paymentRequest->branch->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('retirements.fields.advance_amount') }}</dt>
                            <dd class="text-lg font-semibold text-mono">
                                {{ $paymentRequest->currency->symbol ?? '' }} {{ number_format((float) $paymentRequest->total_amount, 2) }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Expenditure Items (full width) --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">{{ __('retirements.fields.expenditure_items') }}</h3>
                </div>
                <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
                    @error('items')
                        <p class="mb-3 text-sm text-destructive">{{ $message }}</p>
                    @enderror

                    <div id="items-container" class="flex flex-col gap-4">
                        @php $oldItems = old('items', [[]]); @endphp
                        @foreach($oldItems as $index => $oldItem)
                            <div class="item-row flex flex-col lg:flex-row lg:items-start gap-3 p-4 rounded-lg border border-border">
                                <div class="flex-1 min-w-0">
                                    <label class="kt-form-label block mb-1.5 text-sm">{{ __('common.columns.description') }} <span class="text-destructive">*</span></label>
                                    <input type="text" name="items[{{ $index }}][description]"
                                           class="kt-input w-full"
                                           value="{{ $oldItem['description'] ?? '' }}"
                                           placeholder="{{ __('retirements.fields.what_purchased') }}"
                                           aria-invalid="@error('items.{{ $index }}.description') true @else false @enderror">
                                    @error("items.{$index}.description")
                                        <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex-1 min-w-0">
                                    <label class="kt-form-label block mb-1.5 text-sm">{{ __('retirements.fields.cost_code') }} <span class="text-destructive">*</span></label>
                                    <select name="items[{{ $index }}][cost_code_id]" class="kt-select w-full"
                                            aria-invalid="@error('items.{{ $index }}.cost_code_id') true @else false @enderror">
                                        <option value="">Select…</option>
                                        @foreach($costCodes as $code)
                                            <option value="{{ $code->id }}"
                                                {{ isset($oldItem['cost_code_id']) && $oldItem['cost_code_id'] == $code->id ? 'selected' : '' }}>
                                                {{ $code->code }} — {{ $code->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error("items.{$index}.cost_code_id")
                                        <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="lg:w-36 shrink-0">
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
                                <div class="lg:w-36 shrink-0">
                                    <label class="kt-form-label block mb-1.5 text-sm">{{ __('retirements.fields.receipt_no') }}</label>
                                    <input type="text" name="items[{{ $index }}][receipt_number]"
                                           class="kt-input w-full"
                                           value="{{ $oldItem['receipt_number'] ?? '' }}"
                                           placeholder="{{ __('common.optional') }}">
                                </div>
                                <div class="flex items-end justify-end shrink-0">
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
                        {{ __('common.add_item') }}
                    </button>
                </div>
            </div>

            {{-- Notes + Summary row --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

                {{-- Notes --}}
                <div class="lg:col-span-2 kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('common.notes') }}</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <textarea name="notes" rows="3"
                                  class="kt-textarea w-full"
                                  placeholder="{{ __('retirements.fields.notes_placeholder') }}"
                                  aria-invalid="@error('notes') true @else false @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Summary + Submit --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('retirements.fields.summary') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">{{ __('retirements.fields.advance_amount') }}</span>
                            <span class="font-medium text-mono">
                                {{ $paymentRequest->currency->symbol ?? '' }} {{ number_format((float) $paymentRequest->total_amount, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">{{ __('retirements.fields.total_expended') }}</span>
                            <span class="font-medium text-mono" id="total-expended">
                                {{ $paymentRequest->currency->symbol ?? '' }} 0.00
                            </span>
                        </div>
                        <hr class="border-border">
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">{{ __('retirements.fields.difference') }}</span>
                            <span class="font-semibold text-mono" id="difference-display">—</span>
                        </div>
                        <div class="text-xs text-secondary-foreground" id="difference-label"></div>

                        <button type="submit" class="kt-btn kt-btn-primary w-full mt-2">
                            <i class="ki-filled ki-check"></i>
                            {{ __('retirements.buttons.save_draft') }}
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </form>
</div>

<template id="item-template">
    <div class="item-row flex flex-col lg:flex-row lg:items-start gap-3 p-4 rounded-lg border border-border">
        <div class="flex-1 min-w-0">
            <label class="kt-form-label block mb-1.5 text-sm">{{ __('common.columns.description') }} <span class="text-destructive">*</span></label>
            <input type="text" name="items[__INDEX__][description]" class="kt-input w-full" placeholder="{{ __('retirements.fields.what_purchased') }}">
        </div>
        <div class="flex-1 min-w-0">
            <label class="kt-form-label block mb-1.5 text-sm">{{ __('retirements.fields.cost_code') }} <span class="text-destructive">*</span></label>
            <select name="items[__INDEX__][cost_code_id]" class="kt-select w-full">
                <option value="">Select…</option>
                @foreach($costCodes as $code)
                    <option value="{{ $code->id }}">{{ $code->code }} — {{ $code->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:w-36 shrink-0">
            <label class="kt-form-label block mb-1.5 text-sm">Amount <span class="text-destructive">*</span></label>
            <input type="number" name="items[__INDEX__][amount]" class="kt-input w-full item-amount" step="0.01" min="0.01" placeholder="0.00">
        </div>
        <div class="lg:w-36 shrink-0">
            <label class="kt-form-label block mb-1.5 text-sm">{{ __('retirements.fields.receipt_no') }}</label>
            <input type="text" name="items[__INDEX__][receipt_number]" class="kt-input w-full" placeholder="{{ __('common.optional') }}">
        </div>
        <div class="flex items-end justify-end shrink-0">
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
            labelEl.textContent = '{{ __('retirements.difference.nil') }}';
            diffEl.className = 'font-semibold text-mono text-success';
        } else if (diff > 0) {
            diffEl.textContent = symbol + ' ' + diff.toFixed(2);
            labelEl.textContent = '{{ __('retirements.difference.pay_to_staff') }}';
            diffEl.className = 'font-semibold text-mono text-warning';
        } else {
            diffEl.textContent = symbol + ' ' + Math.abs(diff).toFixed(2);
            labelEl.textContent = '{{ __('retirements.difference.refund') }}';
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
