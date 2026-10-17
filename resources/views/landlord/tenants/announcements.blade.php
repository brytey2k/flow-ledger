@extends('landlord.layouts.base')

@section('content')
@include('landlord.tenants.partials.admin-nav')
<div class="sgh-container-fixed">
    <div class="flex flex-col gap-2 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Announcements & Maintenance</h1>
        <p class="text-sm text-secondary-foreground">Show a banner to everyone in this tenant, or take the workspace offline. Impersonation still works during maintenance.</p>
    </div>
</div>

<div class="sgh-container-fixed">
    <form method="POST" action="{{ route('landlord.tenants.announcements.update', $tenant) }}" class="grid gap-5 lg:gap-7.5">
        @csrf
        @method('PUT')
        <div class="sgh-card">
            <div class="sgh-card-header"><h3 class="sgh-card-title">Announcement banner</h3><p class="sgh-card-subtitle">Leave the message empty to hide the banner.</p></div>
            <div class="sgh-card-content grid gap-4">
                <div class="grid gap-2">
                    <label class="sgh-form-label" for="announcement_message">Message</label>
                    <textarea id="announcement_message" name="announcement_message" rows="2" maxlength="500" class="sgh-input">{{ old('announcement_message', $settings['announcement_message']) }}</textarea>
                </div>
                <div class="grid gap-2">
                    <label class="sgh-form-label" for="announcement_level">Style</label>
                    <select id="announcement_level" name="announcement_level" class="sgh-select w-48">
                        <option value="info" @selected(old('announcement_level', $settings['announcement_level']) === 'info')>Info</option>
                        <option value="warning" @selected(old('announcement_level', $settings['announcement_level']) === 'warning')>Warning</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="sgh-card">
            <div class="sgh-card-header"><h3 class="sgh-card-title">Maintenance mode</h3><p class="sgh-card-subtitle">While on, tenant users and API clients receive a 503 page.</p></div>
            <div class="sgh-card-content grid gap-4">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="maintenance_mode" value="1" class="sgh-checkbox" @checked(old('maintenance_mode', $settings['maintenance_mode']))>
                    Put this tenant into maintenance mode
                </label>
                <div class="grid gap-2">
                    <label class="sgh-form-label" for="maintenance_message">Message shown to users</label>
                    <textarea id="maintenance_message" name="maintenance_message" rows="2" maxlength="500" class="sgh-input">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                </div>
            </div>
        </div>

        <div><button class="sgh-btn sgh-btn-primary" type="submit">Save</button></div>
    </form>
</div>
@endsection
