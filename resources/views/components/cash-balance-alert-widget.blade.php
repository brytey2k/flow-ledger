@props(['branches' => []])

@php
    $branches = collect($branches)->filter(static function ($branch): bool {
        $cashbook = $branch->cashbook ?? null;
        $threshold = $branch->cashBalanceThreshold ?? null;

        if (! $cashbook || ! $threshold) {
            return false;
        }

        $balance = (float) $cashbook->getAttribute('balance');
        $thresholdAmount = (float) $threshold->getAttribute('threshold_amount');

        return $balance < $thresholdAmount;
    })->values();
@endphp

@if($branches->isNotEmpty())
    <div class="kt-card kt-card-grid">
        <div class="kt-card-header">
            <h3 class="kt-card-title">
                <i class="ki-filled ki-warning text-warning mr-2"></i>
                {{ __('cash_balance.alert_widget_title') }}
            </h3>
        </div>
        <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5">
            <div class="space-y-3">
                @foreach($branches as $branch)
                    @php
                        $cashbook = $branch->cashbook;
                        $threshold = $branch->cashBalanceThreshold;

                        if (! $cashbook || ! $threshold) {
                            continue;
                        }

                        $balance = (float) $cashbook->balance;
                        $thresholdAmount = (float) $threshold->threshold_amount;
                        $percentageOfThreshold = $thresholdAmount > 0
                            ? ($balance / $thresholdAmount) * 100
                            : 0;
                    @endphp
                    <div class="flex items-center justify-between p-4 bg-warning/10 border border-warning/30 rounded-lg">
                        <div class="flex-1">
                            <h4 class="font-medium text-foreground">{{ $branch->name }}</h4>
                            <div class="flex items-center gap-4 mt-2">
                                <div>
                                    <p class="text-2sm text-secondary-foreground">
                                        {{ __('cash_balance.current_balance') }}: 
                                        <span class="font-medium text-warning">
                                            {{ $branch->currency?->symbol ?? '' }} {{ number_format($balance, 2) }}
                                        </span>
                                    </p>
                                    <p class="text-2sm text-secondary-foreground mt-1">
                                        {{ __('cash_balance.threshold') }}: 
                                        <span class="font-medium">{{ $branch->currency?->symbol ?? '' }} {{ number_format($thresholdAmount, 2) }}</span>
                                    </p>
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <div class="h-2 bg-secondary rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-warning transition-all"
                                            style="width: {{ min($percentageOfThreshold, 100) }}%"
                                        ></div>
                                    </div>
                                    <p class="text-2sm text-secondary-foreground mt-1 text-right">
                                        {{ number_format($percentageOfThreshold, 0) }}% of threshold
                                    </p>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('cash-balance-thresholds.index') }}" class="kt-btn kt-btn-sm kt-btn-ghost text-primary ml-4">
                            <i class="ki-filled ki-arrow-right"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
