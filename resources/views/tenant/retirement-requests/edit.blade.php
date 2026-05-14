@extends('tenant.layouts.base')

@section('content')
@php
    $pr = $retirementRequest->paymentRequest;
@endphp
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ __('retirements.edit.title', ['id' => $retirementRequest->id]) }}
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('retirements.edit.subtitle') }}
            </div>
        </div>
        <a class="kt-btn kt-btn-outline" href="{{ route('retirement-requests.show', $retirementRequest) }}">
            <i class="ki-filled ki-arrow-left"></i>
            {{ __('retirements.edit.back') }}
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <form method="POST" action="{{ route('retirement-requests.update', $retirementRequest) }}" id="retirement-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-7.5">

            <div class="lg:col-span-2 flex flex-col gap-5 lg:gap-7.5">

                {{-- Advance Summary (read-only) --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('retirements.fields.advance_summary') }}</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.columns.staff') }}</dt>
                                <dd class="text-sm font-medium text-mono">{{ $pr->staff->full_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('common.columns.branch') }}</dt>
                                <dd class="text-sm text-foreground">{{ $pr->branch->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-secondary-foreground uppercase mb-1">{{ __('retirements.fields.advance_amount') }}</dt>
                                <dd class="text-lg font-semibold text-mono">
                                    {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $pr->total_amount, 2) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                {{-- Expenditure Items --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('retirements.fields.expenditure_items') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
                        @error('items')
                            <p class="mb-3 text-sm text-destructive">{{ $message }}</p>
                        @enderror

                        @php
                            $editItems = old('items') ?? $retirementRequest->items->map(fn($item) => [
                                'description' => $item->description,
                                'amount' => $item->amount,
                                'account_code_id' => $item->account_code_id,
                                'receipt_number' => $item->receipt_number,
                            ])->all();
                        @endphp

                        <div id="items-container" class="flex flex-col gap-4">
                            @foreach($editItems as $index => $editItem)
                                <div class="item-row grid grid-cols-1 sm:grid-cols-12 gap-3 p-4 rounded-lg border border-border relative">
                                    <div class="sm:col-span-4">
                                        <label class="kt-form-label block mb-1.5 text-sm">{{ __('common.columns.description') }} <span class="text-destructive">*</span></label>
                                        <input type="text" name="items[{{ $index }}][description]"
                                               class="kt-input w-full"
                                               value="{{ $editItem['description'] ?? '' }}"
                                               placeholder="{{ __('retirements.fields.what_purchased') }}"
                                               aria-invalid="@error('items.{{ $index }}.description') true @else false @enderror">
                                        @error("items.{$index}.description")
                                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-3">
                                        <label class="kt-form-label block mb-1.5 text-sm">{{ __('retirements.fields.account_code') }} <span class="text-destructive">*</span></label>
                                        <select name="items[{{ $index }}][account_code_id]" class="kt-select w-full"
                                                aria-invalid="@error('items.{{ $index }}.account_code_id') true @else false @enderror">
                                            <option value="">Select…</option>
                                            @foreach($accountCodes as $code)
                                                <option value="{{ $code->id }}"
                                                    {{ isset($editItem['account_code_id']) && $editItem['account_code_id'] == $code->id ? 'selected' : '' }}>
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
                                               value="{{ $editItem['amount'] ?? '' }}"
                                               step="0.01" min="0.01"
                                               placeholder="0.00"
                                               aria-invalid="@error('items.{{ $index }}.amount') true @else false @enderror">
                                        @error("items.{$index}.amount")
                                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="kt-form-label block mb-1.5 text-sm">{{ __('retirements.fields.receipt_no') }}</label>
                                        <input type="text" name="items[{{ $index }}][receipt_number]"
                                               class="kt-input w-full"
                                               value="{{ $editItem['receipt_number'] ?? '' }}"
                                               placeholder="{{ __('common.optional') }}">
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
                            {{ __('common.add_item') }}
                        </button>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="kt-card">
                    <div class="kt-card-header">
                        <h3 class="kt-card-title">{{ __('common.notes') }}</h3>
                    </div>
                    <div class="kt-card-content p-5">
                        <textarea name="notes" rows="3"
                                  class="kt-textarea w-full"
                                  placeholder="{{ __('retirements.fields.notes_placeholder') }}"
                                  aria-invalid="@error('notes') true @else false @enderror">{{ old('notes', $retirementRequest->notes) }}</textarea>
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
                        <h3 class="kt-card-title">{{ __('retirements.fields.summary') }}</h3>
                    </div>
                    <div class="kt-card-content p-5 flex flex-col gap-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">{{ __('retirements.fields.advance_amount') }}</span>
                            <span class="font-medium text-mono">
                                {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $pr->total_amount, 2) }}
                            </span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">{{ __('retirements.fields.total_expended') }}</span>
                            <span class="font-medium text-mono" id="total-expended">
                                {{ $pr->currency->symbol ?? '' }} 0.00
                            </span>
                        </div>
                        <hr class="border-border">
                        <div class="flex justify-between text-sm">
                            <span class="text-secondary-foreground">{{ __('retirements.fields.difference') }}</span>
                            <span class="font-semibold text-mono" id="difference-display">—</span>
                        </div>
                        <div class="text-xs text-secondary-foreground" id="difference-label"></div>

                        <button type="submit" class="kt-btn kt-btn-primary w-full mt-2">
                            <i class="ki-filled ki-floppy-disk"></i>
                            {{ __('retirements.buttons.save_changes') }}
                        </button>
                        <a class="kt-btn kt-btn-light w-full" href="{{ route('retirement-requests.show', $retirementRequest) }}">
                            {{ __('common.cancel') }}
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<template id="item-template">
    <div class="item-row grid grid-cols-1 sm:grid-cols-12 gap-3 p-4 rounded-lg border border-border relative">
        <div class="sm:col-span-4">
            <label class="kt-form-label block mb-1.5 text-sm">{{ __('common.columns.description') }} <span class="text-destructive">*</span></label>
            <input type="text" name="items[__INDEX__][description]" class="kt-input w-full" placeholder="{{ __('retirements.fields.what_purchased') }}">
        </div>
        <div class="sm:col-span-3">
            <label class="kt-form-label block mb-1.5 text-sm">{{ __('retirements.fields.account_code') }} <span class="text-destructive">*</span></label>
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
            <label class="kt-form-label block mb-1.5 text-sm">{{ __('retirements.fields.receipt_no') }}</label>
            <input type="text" name="items[__INDEX__][receipt_number]" class="kt-input w-full" placeholder="{{ __('common.optional') }}">
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
    const advanceAmount = {{ (float) $pr->total_amount }};
    const symbol = '{{ $pr->currency->symbol ?? '' }}';
    let itemIndex = {{ count($editItems) }};

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
