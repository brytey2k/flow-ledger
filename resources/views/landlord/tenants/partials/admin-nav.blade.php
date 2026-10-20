@php
    $links = [
        'landlord.tenants.health.index' => 'Health',
        'landlord.tenants.data-tools.index' => 'Data Tools',
        'landlord.tenants.fix-ups.index' => 'Fix-ups',
        'landlord.tenants.failed-jobs.index' => 'Failed Jobs',
        'landlord.tenants.storage.index' => 'Storage',
        'landlord.tenants.user-admin.index' => 'User Admin',
        'landlord.tenants.announcements.edit' => 'Announcements',
    ];
@endphp
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center gap-2 pb-5">
        <span class="text-sm font-medium text-mono pe-2">{{ $tenant->id }}</span>
        @foreach($links as $routeName => $label)
            <a href="{{ route($routeName, $tenant) }}" class="sgh-btn sgh-btn-sm {{ request()->routeIs($routeName) ? 'sgh-btn-primary' : 'sgh-btn-light' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>
