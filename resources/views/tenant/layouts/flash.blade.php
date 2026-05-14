@php
    /**
     * Supported flash keys mapped to Metronic kt-alert variants.
     * status maps to success for legacy usage.
     */
    $flashMap = [
        'success' => [
            'icon'    => 'ki-check-circle',
            'variant' => 'kt-alert-success',
            'label'   => __('common.alerts.success'),
        ],
        'error' => [
            'icon'    => 'ki-information',
            'variant' => 'kt-alert-danger',
            'label'   => __('common.alerts.error'),
        ],
        'warning' => [
            'icon'    => 'ki-information-4',
            'variant' => 'kt-alert-warning',
            'label'   => __('common.alerts.warning'),
        ],
        'info' => [
            'icon'    => 'ki-information',
            'variant' => 'kt-alert-info',
            'label'   => __('common.alerts.info'),
        ],
        'status' => [ // legacy alias used by Laravel redirects
            'icon'    => 'ki-check-circle',
            'variant' => 'kt-alert-success',
            'label'   => __('common.alerts.success'),
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
            <div class="kt-alert kt-alert-danger">
                <i class="ki-filled ki-information"></i>
                <ul class="list-disc ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(is_array($structured) && ($structured['message'] ?? false))
            @php
                $type   = $structured['type'] ?? 'info';
                $config = $flashMap[$type] ?? $flashMap['info'];
            @endphp
            <div class="kt-alert {{ $config['variant'] }}">
                <i class="ki-filled {{ $config['icon'] }}"></i>
                {!! is_array($structured['message']) ? implode('<br>', array_map('e', $structured['message'])) : e($structured['message']) !!}
            </div>
        @endif

        @foreach($flashMap as $key => $config)
            @if (session($key))
                @php $message = session($key); @endphp
                <div class="kt-alert {{ $config['variant'] }}">
                    <i class="ki-filled {{ $config['icon'] }}"></i>
                    @if (is_array($message))
                        <ul class="list-disc ps-5">
                            @foreach ($message as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    @else
                        {{ $message }}
                    @endif
                </div>
            @endif
        @endforeach
    </div>
    @php(session()->forget('flash'))
</div>
