<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Enums\AuditLogStatus;
use App\Enums\TenantDataTool;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\TenantDataToolRequest;
use App\Jobs\RunTenantDataToolJob;
use App\Models\Landlord\AuditLog;
use App\Models\Tenant;
use App\Services\AuditLogRecorder;
use App\Services\TenantDataToolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantDataToolsController extends Controller
{
    public function index(Tenant $tenant): View
    {
        return view('landlord.tenants.data-tools', [
            'tenant' => $tenant,
            'tools' => TenantDataTool::cases(),
            'tenantName' => $this->resolveTenantName($tenant),
            'recentRuns' => AuditLog::where('tenant_id', $tenant->getTenantKey())->latest('id')->limit(15)->get(),
            'hasActiveRun' => $this->hasActiveRun($tenant),
        ]);
    }

    public function preview(TenantDataToolRequest $request, Tenant $tenant, TenantDataTool $tool, TenantDataToolService $service): RedirectResponse
    {
        $counts = $service->preview($tenant, $tool, $request->filters());

        return redirect()
            ->route('landlord.tenants.data-tools.index', $tenant)
            ->withInput()
            ->with('preview', ['tool' => $tool->value, 'counts' => $counts]);
    }

    public function run(TenantDataToolRequest $request, Tenant $tenant, TenantDataTool $tool): RedirectResponse
    {
        $back = redirect()->route('landlord.tenants.data-tools.index', $tenant);

        if ($tool->isDestructive()) {
            $provided = trim($request->string('confirm_tenant_name')->toString());

            if (strcasecmp($provided, $this->resolveTenantName($tenant)) !== 0) {
                return $back->withInput()->with('error', __('flash.tenants.data_tool_confirm_mismatch'));
            }
        }

        if ($this->hasActiveRun($tenant)) {
            return $back->withInput()->with('error', __('flash.tenants.data_tool_already_running'));
        }

        $user = Auth::guard('landlord')->user();

        $auditLog = AuditLog::create([
            'landlord_user_id' => $user?->getAuthIdentifier(),
            'landlord_user_email' => $user instanceof \App\Models\Landlord\User ? $user->email : null,
            'tenant_id' => $tenant->tenantId(),
            'action' => $tool->value,
            'status' => AuditLogStatus::Queued,
            'filters' => $request->filters()->toArray(),
        ]);

        dispatch(new RunTenantDataToolJob($auditLog->id));

        return $back->with('success', __('flash.tenants.data_tool_queued'));
    }

    public function download(Tenant $tenant, AuditLog $auditLog, AuditLogRecorder $audit): StreamedResponse
    {
        abort_unless(
            $auditLog->tenant_id === $tenant->tenantId()
                && $auditLog->artifact_path !== null
                && Storage::disk('local')->exists($auditLog->artifact_path),
            404,
        );

        $path = $auditLog->artifact_path;
        $audit->record($auditLog->tenant_id, 'download-export', ['audit_log_id' => $auditLog->id], static fn(): array => ['downloads' => 1]);

        return Storage::disk('local')->download($path, sprintf('%s-export-%s.zip', $auditLog->tenant_id, $auditLog->created_at?->format('Ymd-His')));
    }

    private function hasActiveRun(Tenant $tenant): bool
    {
        return AuditLog::where('tenant_id', $tenant->getTenantKey())
            ->whereIn('status', [AuditLogStatus::Queued, AuditLogStatus::Running])
            ->where('created_at', '>=', now()->subHour())
            ->exists();
    }

    private function resolveTenantName(Tenant $tenant): string
    {
        $name = $tenant->getAttribute('name');

        return is_string($name) && $name !== '' ? $name : $tenant->tenantId();
    }
}
