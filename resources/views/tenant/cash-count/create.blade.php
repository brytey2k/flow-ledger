@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cash_count.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $branch->name }} · {{ $cashbook->currency->name }} ({{ $cashbook->currency->symbol }})
            </div>
        </div>
        <a href="{{ route('cashbook.index', $branch) }}" class="kt-btn kt-btn-light">
            <i class="ki-filled ki-arrow-left"></i>
            {{ __('common.back') }}
        </a>
    </div>
</div>

<div class="kt-container-fixed" x-data="cashCount()">
    <div class="grid gap-5 lg:gap-7.5">
        <form method="POST" action="{{ route('cash-count.store', $branch) }}" class="flex flex-col gap-5">
            @csrf

            <!-- Balance Summary Card -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">{{ __('cash_count.labels.counted_total') }}</h3>
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col items-end gap-0.5">
                            <span class="text-xs text-secondary-foreground">{{ __('cash_count.labels.balance_at_count') }}</span>
                            <span class="text-sm font-semibold text-mono">
                                {{ $cashbook->currency->symbol }} {{ number_format((float) $cashbook->balance, 2) }}
                            </span>
                        </div>
                        <div class="h-8 w-px bg-border"></div>
                        <div class="flex flex-col items-end gap-0.5">
                            <span class="text-xs text-secondary-foreground">{{ __('cash_count.labels.counted_total') }}</span>
                            <span class="text-sm font-semibold text-mono" x-text="'{{ $cashbook->currency->symbol }} ' + formatTotal()">
                                {{ $cashbook->currency->symbol }} 0.00
                            </span>
                        </div>
                        <div class="h-8 w-px bg-border"></div>
                        <div class="flex flex-col items-end gap-0.5">
                            <span class="text-xs text-secondary-foreground">{{ __('cash_count.labels.difference') }}</span>
                            <span class="text-sm font-semibold"
                                  :class="diffClass()"
                                  x-text="diffText('{{ $cashbook->currency->symbol }}', {{ (float) $cashbook->balance }})">
                                —
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="kt-alert kt-alert-danger">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Denominations Grid -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">{{ __('cash_count.denominations.title') }}</h3>
                    <span class="text-sm text-secondary-foreground">{{ __('cash_count.labels.quantity') }}</span>
                </div>
                <div class="kt-card-content p-5 lg:p-7.5">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($denominations as $index => $denomination)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-border p-3">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-sm font-medium text-mono">{{ $denomination->label }}</span>
                                    <span class="text-xs text-secondary-foreground">
                                        {{ $cashbook->currency->symbol }} {{ number_format((float) $denomination->value, 2) }}
                                    </span>
                                </div>
                                <input type="hidden" name="items[{{ $index }}][denomination_id]" value="{{ $denomination->id }}">
                                <input type="number"
                                       name="items[{{ $index }}][quantity]"
                                       x-model.number="quantities[{{ $index }}]"
                                       @input="updateTotal()"
                                       value="{{ old("items.{$index}.quantity", 0) }}"
                                       min="0"
                                       step="1"
                                       class="kt-input w-24 text-right @error("items.{$index}.quantity") border-danger @enderror"
                                       placeholder="0">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="kt-card">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">{{ __('cash_count.labels.notes') }}</h3>
                </div>
                <div class="kt-card-content p-5 lg:p-7.5">
                    <textarea name="notes" rows="3"
                              class="kt-textarea w-full @error('notes') border-danger @enderror"
                              placeholder="{{ __('cash_count.labels.notes') }}...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-sm text-danger mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <button type="submit" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-check"></i>
                    {{ __('cash_count.buttons.submit') }}
                </button>
                <a href="{{ route('cashbook.index', $branch) }}" class="kt-btn kt-btn-light">{{ __('common.cancel') }}</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function cashCount() {
    return {
        quantities: @json(array_fill(0, $denominations->count(), 0)),
        values: @json($denominations->pluck('value')->map(fn($v) => (float) $v)->values()->toArray()),
        balance: {{ (float) $cashbook->balance }},

        updateTotal() {},

        total() {
            let sum = 0;
            for (let i = 0; i < this.quantities.length; i++) {
                sum += (this.quantities[i] || 0) * this.values[i];
            }
            return Math.round(sum * 100) / 100;
        },

        formatTotal() {
            return this.total().toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        diff() {
            return Math.round((this.total() - this.balance) * 100) / 100;
        },

        diffText(symbol, balance) {
            const d = this.diff();
            if (Math.abs(d) <= 0.01) return '{{ __("cash_count.status.equal") }}';
            const sign = d > 0 ? '+' : '−';
            return sign + symbol + ' ' + Math.abs(d).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },

        diffClass() {
            const d = this.diff();
            if (Math.abs(d) <= 0.01) return 'text-success';
            if (d > 0) return 'text-warning';
            return 'text-danger';
        },
    };
}
</script>
@endpush
@endsection
