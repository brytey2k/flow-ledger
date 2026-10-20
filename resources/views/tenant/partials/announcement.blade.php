@php
    $announcement = tenant() instanceof \App\Models\Tenant ? app(\App\Services\TenantAnnouncementService::class)->get(tenant()) : null;
@endphp
@if($announcement && $announcement['announcement_message'] !== '')
    <div class="sgh-alert {{ $announcement['announcement_level'] === 'warning' ? 'sgh-alert-warning' : 'sgh-alert-info' }} mb-4" role="status">
        {{ $announcement['announcement_message'] }}
    </div>
@endif
