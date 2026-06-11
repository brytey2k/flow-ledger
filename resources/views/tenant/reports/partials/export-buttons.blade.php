<div class="flex items-center gap-2">
    <a href="{{ route($exportRoute, array_merge(request()->query(), ['format' => 'csv'])) }}"
       class="kt-btn kt-btn-sm kt-btn-light">
        <i class="ki-filled ki-file-down text-success mr-1"></i>
        CSV
    </a>
    <a href="{{ route($exportRoute, array_merge(request()->query(), ['format' => 'pdf'])) }}"
       class="kt-btn kt-btn-sm kt-btn-light">
        <i class="ki-filled ki-file-down text-danger mr-1"></i>
        PDF
    </a>
</div>
