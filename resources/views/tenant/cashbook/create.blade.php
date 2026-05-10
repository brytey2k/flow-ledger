@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Add Receipt — {{ $branch->name }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Record cash received from the bank or an external source
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('cashbook.index', $branch) }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Cashbook
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Receipt Details</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('cashbook.store', $branch) }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        <!-- Amount -->
                        <div>
                            <label class="kt-form-label block mb-2" for="amount">
                                Amount ({{ $cashbook->currency->symbol }}) <span class="text-destructive">*</span>
                            </label>
                            <input id="amount" name="amount" type="number" step="0.01" min="0.01"
                                   value="{{ old('amount') }}"
                                   class="kt-input w-full"
                                   placeholder="0.00"
                                   required
                                   aria-invalid="@error('amount') true @else false @enderror" />
                            @error('amount')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Entry Date -->
                        <div>
                            <label class="kt-form-label block mb-2" for="entry_date">
                                Date <span class="text-destructive">*</span>
                            </label>
                            <input id="entry_date" name="entry_date" type="date"
                                   value="{{ old('entry_date', date('Y-m-d')) }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="kt-input w-full"
                                   required
                                   aria-invalid="@error('entry_date') true @else false @enderror" />
                            @error('entry_date')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Reference -->
                        <div>
                            <label class="kt-form-label block mb-2" for="reference">
                                Reference Number <span class="text-muted-foreground text-xs">(optional)</span>
                            </label>
                            <input id="reference" name="reference" type="text"
                                   value="{{ old('reference') }}"
                                   class="kt-input w-full"
                                   placeholder="e.g. Bank transfer reference, cheque number"
                                   maxlength="100"
                                   aria-invalid="@error('reference') true @else false @enderror" />
                            @error('reference')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="kt-form-label block mb-2" for="notes">
                            Notes <span class="text-muted-foreground text-xs">(optional)</span>
                        </label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="kt-input w-full"
                                  placeholder="Additional details about this receipt"
                                  maxlength="5000"
                                  aria-invalid="@error('notes') true @else false @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check"></i>
                            Save Receipt
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('cashbook.index', $branch) }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
