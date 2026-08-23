<div class="flex items-center gap-2">
    <a href="{{ route($exportRoute, array_merge(request()->query(), ['format' => 'csv'])) }}"
       class="sgh-btn sgh-btn-sm sgh-btn-light">
        <x-tabler-file-arrow-right class="text-success mr-1" />
        CSV
    </a>
    <a href="{{ route($exportRoute, array_merge(request()->query(), ['format' => 'pdf'])) }}"
       class="sgh-btn sgh-btn-sm sgh-btn-light">
        <x-tabler-file-arrow-right class="text-danger mr-1" />
        PDF
    </a>
</div>
