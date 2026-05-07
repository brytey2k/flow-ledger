@php
    /**
     * Supported flash keys mapped to Metronic kt-alert variants.
     * status maps to success for legacy usage.
     */
    $flashMap = [
        'success' => [
            'icon'    => 'ki-check',
            'variant' => 'kt-alert-success',
            'label'   => 'Success',
        ],
        'error' => [
            'icon'    => 'ki-information-2',
            'variant' => 'kt-alert-destructive',
            'label'   => 'Error',
        ],
        'warning' => [
            'icon'    => 'ki-information-4',
            'variant' => 'kt-alert-warning',
            'label'   => 'Warning',
        ],
        'info' => [
            'icon'    => 'ki-information',
            'variant' => 'kt-alert-info',
            'label'   => 'Info',
        ],
        'status' => [ // legacy alias used by Laravel redirects
            'icon'    => 'ki-check',
            'variant' => 'kt-alert-success',
            'label'   => 'Success',
        ],
    ];

    // Also support a single structured flash payload: ['type' => 'info', 'title' => '...', 'message' => '...']
    $structured = session('flash');
@endphp

<div class="kt-container-fixed">
    @php
        $hasFlash = $errors->any()
            || (is_array($structured) && ($structured['message'] ?? false))
            || collect(array_keys($flashMap))->some(fn($k) => session($k));
    @endphp
    <div class="{{ $hasFlash ? 'grid gap-3 pb-5' : '' }}">
        @if($errors->any())
            <div class="kt-alert kt-alert-light kt-alert-destructive">
                <span class="kt-alert-icon"><i class="ki-filled ki-information-2 text-xl"></i></span>
                <div class="kt-alert-content">
                    <h4 class="kt-alert-title">Please fix the following errors</h4>
                    <ul class="kt-alert-description list-disc ps-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if(is_array($structured) && ($structured['message'] ?? false))
            @php
                $type   = $structured['type'] ?? 'info';
                $config = $flashMap[$type] ?? $flashMap['info'];
            @endphp
            <div class="kt-alert kt-alert-light {{ $config['variant'] }}">
                <span class="kt-alert-icon">
                    <i class="ki-filled {{ $config['icon'] }} text-xl"></i>
                </span>
                <div class="kt-alert-content">
                    <h4 class="kt-alert-title">{{ $structured['title'] ?? $config['label'] }}</h4>
                    <div class="kt-alert-description">{!! is_array($structured['message']) ? implode('<br>', array_map('e', $structured['message'])) : e($structured['message']) !!}</div>
                </div>
            </div>
        @endif

        @foreach($flashMap as $key => $config)
            @if (session($key))
                @php $message = session($key); @endphp
                <div class="kt-alert kt-alert-light {{ $config['variant'] }}">
                    <span class="kt-alert-icon">
                        <i class="ki-filled {{ $config['icon'] }} text-xl"></i>
                    </span>
                    <div class="kt-alert-content">
                        <h4 class="kt-alert-title">{{ $config['label'] }}</h4>
                        @if (is_array($message))
                            <ul class="kt-alert-description list-disc ps-5">
                                @foreach ($message as $line)
                                    <li>{{ $line }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="kt-alert-description">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    @php(session()->forget('flash'))
</div>
