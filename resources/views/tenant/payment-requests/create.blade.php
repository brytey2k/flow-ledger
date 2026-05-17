@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('payment_requests.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('payment_requests.create_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('payment-requests.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('payment_requests.back') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <form method="POST" action="{{ route('payment-requests.store') }}" id="request-form">
        @csrf

        <div class="grid gap-5 lg:gap-7.5">

            {{-- Request Details --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">{{ __('payment_requests.details_card') }}</h3>
                </div>
                <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                        {{-- Type --}}
                        <div>
                            <label class="kt-form-label block mb-2" for="type">
                                {{ __('payment_requests.fields.type') }} <span class="text-destructive">*</span>
                            </label>
                            <select id="type" name="type" class="kt-select w-full"
                                    aria-invalid="@error('type') true @else false @enderror">
                                <option value="">{{ __('payment_requests.fields.select_type') }}</option>
                                <option value="advance" {{ old('type') === 'advance' ? 'selected' : '' }}>
                                    {{ __('payment_requests.fields.type_advance') }}
                                </option>
                                <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>
                                    {{ __('payment_requests.fields.type_expense') }}
                                </option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submitting As (read-only) --}}
                        <div>
                            <label class="kt-form-label block mb-2">{{ __('payment_requests.fields.submitting_as') }}</label>
                            <div class="kt-input w-full bg-muted/40 flex items-center gap-3 px-4 py-3 rounded-md">
                                <i class="ki-filled ki-user text-secondary-foreground"></i>
                                <div>
                                    <span class="font-medium text-mono">{{ $staffProfile->full_name }}</span>
                                    <span class="text-secondary-foreground"> &middot; {{ $staffProfile->department?->name }}</span>
                                    <span class="text-secondary-foreground"> &middot; {{ $staffProfile->branch?->name }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Currency --}}
                        <div>
                            <label class="kt-form-label block mb-2" for="currency_id">
                                {{ __('payment_requests.fields.currency') }} <span class="text-destructive">*</span>
                            </label>
                            <select id="currency_id" name="currency_id" class="kt-select w-full"
                                    aria-invalid="@error('currency_id') true @else false @enderror">
                                <option value="">{{ __('payment_requests.fields.select_currency') }}</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->short_name }} — {{ $currency->name }} ({{ $currency->symbol }})
                                    </option>
                                @endforeach
                            </select>
                            @error('currency_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Notes --}}
                        <div class="lg:col-span-3">
                            <label class="kt-form-label block mb-2" for="notes">{{ __('payment_requests.fields.notes') }}</label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="kt-textarea w-full"
                                      placeholder="Optional: provide context or justification for this request…"
                                      aria-invalid="@error('notes') true @else false @enderror">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Line Items --}}
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">{{ __('payment_requests.fields.line_items') }}</h3>
                    <span class="text-sm text-secondary-foreground">{{ __('payment_requests.fields.line_items_hint') }}</span>
                </div>
                <div class="kt-card-content p-5 lg:p-7.5 lg:pt-4">
                    @error('items')
                        <div class="kt-alert kt-alert-danger mb-4">
                            <i class="ki-filled ki-information"></i>
                            {{ $message }}
                        </div>
                    @enderror

                    {{-- Items container --}}
                    <div id="items-container" class="flex flex-col gap-4">
                        @php
                            $oldItems = old('items', [['description' => '', 'amount' => '']]);
                            $oldType = old('type', '');
                        @endphp
                        @foreach($oldItems as $i => $item)
                            <div class="item-row p-4 rounded-lg border border-border flex flex-col gap-3" id="item-row-{{ $i }}">
                                <div class="flex gap-3 items-start">
                                    <div class="flex-1 min-w-0">
                                        <label class="kt-form-label block mb-1 text-xs text-secondary-foreground">{{ __('common.columns.description') }} *</label>
                                        <input type="text"
                                               name="items[{{ $i }}][description]"
                                               value="{{ $item['description'] ?? '' }}"
                                               class="kt-input w-full"
                                               placeholder="e.g. Transport to Accra"
                                               required
                                               aria-invalid="@error('items.{{ $i }}.description') true @else false @enderror" />
                                        @error("items.{{ $i }}.description")
                                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="w-44 shrink-0">
                                        <label class="kt-form-label block mb-1 text-xs text-secondary-foreground">{{ __('common.columns.amount') }} *</label>
                                        <input type="number"
                                               name="items[{{ $i }}][amount]"
                                               value="{{ $item['amount'] ?? '' }}"
                                               class="kt-input w-full amount-input"
                                               placeholder="0.00"
                                               step="0.01"
                                               min="0.01"
                                               required
                                               oninput="recalcTotal()"
                                               aria-invalid="@error('items.{{ $i }}.amount') true @else false @enderror" />
                                        @error("items.{{ $i }}.amount")
                                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="shrink-0 mt-5">
                                        @if($i > 0)
                                            <button type="button"
                                                    class="kt-btn kt-btn-icon kt-btn-sm kt-btn-danger remove-item-btn"
                                                    onclick="removeItem({{ $i }})"
                                                    title="Remove item">
                                                <i class="ki-filled ki-trash"></i>
                                            </button>
                                        @else
                                            <div class="w-9"></div>
                                        @endif
                                    </div>
                                </div>
                                {{-- Expense-only fields --}}
                                <div class="expense-fields grid grid-cols-1 lg:grid-cols-2 gap-3" style="{{ $oldType !== 'expense' ? 'display:none' : '' }}">
                                    <div>
                                        <label class="kt-form-label block mb-1 text-xs text-secondary-foreground">{{ __('payment_requests.fields.cost_code') }} *</label>
                                        <select name="items[{{ $i }}][cost_code_id]" class="kt-select w-full"
                                                aria-invalid="@error('items.{{ $i }}.cost_code_id') true @else false @enderror">
                                            <option value="">{{ __('payment_requests.fields.select') }}</option>
                                            @foreach($costCodes as $code)
                                                <option value="{{ $code->id }}"
                                                    {{ isset($item['cost_code_id']) && $item['cost_code_id'] == $code->id ? 'selected' : '' }}>
                                                    {{ $code->code }} — {{ $code->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("items.{$i}.cost_code_id")
                                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="kt-form-label block mb-1 text-xs text-secondary-foreground">{{ __('payment_requests.fields.receipt_number') }}</label>
                                        <input type="text"
                                               name="items[{{ $i }}][receipt_number]"
                                               value="{{ $item['receipt_number'] ?? '' }}"
                                               class="kt-input w-full"
                                               placeholder="Optional" />
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Add item + Total --}}
                    <div class="flex items-center justify-between mt-5 pt-4 border-t border-border">
                        <button type="button" class="kt-btn kt-btn-sm kt-btn-outline" onclick="addItem()">
                            <i class="ki-filled ki-plus"></i>
                            {{ __('common.add_item') }}
                        </button>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-secondary-foreground">{{ __('common.total') }}:</span>
                            <span class="text-lg font-semibold text-mono" id="total-display">
                                {{ number_format(collect(old('items', []))->sum('amount'), 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2.5 pb-7.5">
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-floppy-disk"></i>
                    {{ __('payment_requests.buttons.save_draft') }}
                </button>
                <a class="kt-btn kt-btn-light" href="{{ route('payment-requests.index') }}">{{ __('common.cancel') }}</a>
            </div>

        </div>
    </form>
</div>

@push('page_js')
<script>
    let nextIndex = {{ count(old('items', [['description' => '', 'amount' => '']])) }};

    const costCodeOptions = `@foreach($costCodes as $code)<option value="{{ $code->id }}">{{ $code->code }} — {{ addslashes($code->name) }}</option>@endforeach`;

    function isExpense() {
        return document.getElementById('type').value === 'expense';
    }

    function buildExpenseFields(index) {
        return `
            <div class="expense-fields grid grid-cols-1 lg:grid-cols-2 gap-3" style="${isExpense() ? '' : 'display:none'}">
                <div>
                    <label class="kt-form-label block mb-1 text-xs text-secondary-foreground">Cost Code *</label>
                    <select name="items[${index}][cost_code_id]" class="kt-select w-full">
                        <option value="">Select…</option>
                        ${costCodeOptions}
                    </select>
                </div>
                <div>
                    <label class="kt-form-label block mb-1 text-xs text-secondary-foreground">Receipt Number</label>
                    <input type="text" name="items[${index}][receipt_number]" class="kt-input w-full" placeholder="Optional" />
                </div>
            </div>`;
    }

    function addItem() {
        const container = document.getElementById('items-container');
        const div = document.createElement('div');
        div.className = 'item-row p-4 rounded-lg border border-border flex flex-col gap-3';
        div.id = 'item-row-' + nextIndex;
        div.innerHTML = `
            <div class="flex gap-3 items-start">
                <div class="flex-1 min-w-0">
                    <label class="kt-form-label block mb-1 text-xs text-secondary-foreground">Description *</label>
                    <input type="text" name="items[${nextIndex}][description]" class="kt-input w-full" placeholder="e.g. Accommodation" required />
                </div>
                <div class="w-44 shrink-0">
                    <label class="kt-form-label block mb-1 text-xs text-secondary-foreground">Amount *</label>
                    <input type="number" name="items[${nextIndex}][amount]" class="kt-input w-full amount-input" placeholder="0.00" step="0.01" min="0.01" required oninput="recalcTotal()" />
                </div>
                <div class="shrink-0 mt-5">
                    <button type="button" class="kt-btn kt-btn-icon kt-btn-sm kt-btn-danger" onclick="removeItem(${nextIndex})" title="Remove item">
                        <i class="ki-filled ki-trash"></i>
                    </button>
                </div>
            </div>
            ${buildExpenseFields(nextIndex)}
        `;
        container.appendChild(div);
        nextIndex++;
    }

    function removeItem(index) {
        const row = document.getElementById('item-row-' + index);
        if (row) { row.remove(); recalcTotal(); }
    }

    function recalcTotal() {
        let total = 0;
        document.querySelectorAll('.amount-input').forEach(input => { total += parseFloat(input.value) || 0; });
        document.getElementById('total-display').textContent = total.toFixed(2);
    }

    document.getElementById('type').addEventListener('change', function () {
        const show = this.value === 'expense';
        document.querySelectorAll('.expense-fields').forEach(el => {
            el.style.display = show ? '' : 'none';
        });
    });

    recalcTotal();
</script>
@endpush

@endsection
