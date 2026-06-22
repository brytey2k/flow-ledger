@php
    /**
     * Supported flash keys mapped to theme variants.
     * status maps to success for legacy usage.
     */
    $flashMap = [
        'success' => [
            'icon' => 'ki-check',
            'classes' => 'border-success/30 bg-success/10 text-success',
            'label' => 'Success',
        ],
        'error' => [
            'icon' => 'ki-information-2',
            'classes' => 'border-destructive/30 bg-destructive/10 text-destructive',
            'label' => 'Error',
        ],
        'warning' => [
            'icon' => 'ki-information-4',
            'classes' => 'border-warning/30 bg-warning/10 text-warning',
            'label' => 'Warning',
        ],
        'info' => [
            'icon' => 'ki-information',
            'classes' => 'border-info/30 bg-info/10 text-info',
            'label' => 'Info',
        ],
        'status' => [ // legacy alias used by Laravel redirects
            'icon' => 'ki-check',
            'classes' => 'border-success/30 bg-success/10 text-success',
            'label' => 'Success',
        ],
    ];

    // Also support a single structured flash payload: ['type' => 'info', 'message' => '...']
    $structured = session('flash');
@endphp

<div class="kt-container-fixed">
    <div class="grid gap-3">
        @if(is_array($structured) && ($structured['message'] ?? false))
            @php
                $type = $structured['type'] ?? 'info';
                $config = $flashMap[$type] ?? $flashMap['info'];
            @endphp
            <div class="mb-2 rounded-lg border p-4 {{ $config['classes'] }}">
                <div class="flex items-start gap-3">
                    <i class="ki-filled {{ $config['icon'] }} text-xl"></i>
                    <div class="flex-1">
                        <div class="font-medium">{{ $structured['title'] ?? $config['label'] }}</div>
                        <div class="text-sm">{!! is_array($structured['message']) ? implode('<br>', array_map('e', $structured['message'])) : e($structured['message']) !!}</div>
                    </div>
                </div>
            </div>
        @endif

        @foreach($flashMap as $key => $config)
            @if (session($key))
                @php
                    $message = session($key);
                @endphp
                <div class="mb-2 rounded-lg border p-4 {{ $config['classes'] }}">
                    <div class="flex items-start gap-3">
                        <i class="ki-filled {{ $config['icon'] }} text-xl"></i>
                        <div class="flex-1">
                            <div class="font-medium">{{ $config['label'] }}</div>
                            @if (is_array($message))
                                <ul class="mt-1 list-disc ps-5 text-sm">
                                    @foreach ($message as $line)
                                        <li>{{ $line }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <div class="text-sm">{{ $message }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
    @php(session()->forget('flash'))
</div>
