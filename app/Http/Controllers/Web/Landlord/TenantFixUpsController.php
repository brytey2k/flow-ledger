<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Enums\TenantFixUp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\TenantFixUpRequest;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant;
use App\Services\AuditLogRecorder;
use App\Services\TenantFixUpService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use LogicException;

class TenantFixUpsController extends Controller
{
    public function index(Tenant $tenant): View
    {
        return view('landlord.tenants.fix-ups', ['tenant' => $tenant, 'fixUps' => TenantFixUp::cases()]);
    }

    public function run(TenantFixUpRequest $request, Tenant $tenant, TenantFixUp $fixUp, TenantFixUpService $service, AuditLogRecorder $audit): RedirectResponse
    {
        $user = Auth::guard('landlord')->user();
        $actor = $user instanceof LandlordUser ? $user->email : 'system administrator';
        $kind = $request->string('kind')->toString();
        $id = $request->integer('record_id');
        $parameters = ['kind' => $kind, 'record_id' => $id];
        $tenantId = $tenant->tenantId();

        try {
            $result = $audit->record($tenantId, $fixUp->value, $parameters, fn(): array => match ($fixUp) {
                TenantFixUp::ForceCancelRequest => $service->forceCancel($tenant, $kind, $id),
                TenantFixUp::ReopenRetirement => $service->reopenRetirement($tenant, $id, $actor),
                TenantFixUp::ReverseDisbursement => $service->reverseDisbursement($tenant, $id, $actor),
                TenantFixUp::ResendRetirementReminders => $service->resendRetirementReminders($tenant, $id > 0 ? $id : null),
            });
        } catch (ModelNotFoundException) {
            return back()->withInput()->with('error', __('flash.tenants.fix_up_not_found'));
        } catch (LogicException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', $fixUp->label() . ': ' . $this->summarise($result));
    }

    /** @param array<string, int> $result */
    private function summarise(array $result): string
    {
        return implode(', ', array_map(
            static fn(string $label, int $count): string => str_replace('_', ' ', $label) . ' ' . $count,
            array_keys($result),
            $result,
        ));
    }
}
