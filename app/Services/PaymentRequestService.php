<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Facades\DB;

class PaymentRequestService
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param array{staff_id: int, branch_id: int, currency_id: int, type: string, notes: string|null, items: array<int, array{description: string, amount: float|string}>} $data
     * @param User|null|null $user
     */
    public function createDraft(array $data, User|null $user = null): PaymentRequest
    {
        return DB::transaction(function () use ($data, $user): PaymentRequest {
            $items = $data['items'];
            unset($data['items']);

            $data['total_amount'] = collect($items)->sum('amount');
            $data['status'] = 'draft';

            $request = PaymentRequest::create($data);

            foreach ($items as $item) {
                $request->items()->create($item);
            }

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.created')
                ->withProperties(['new_status' => 'draft'])
                ->log('Request created as draft');

            return $request;
        });
    }

    public function submit(PaymentRequest $request, User|null $user = null): void
    {
        DB::transaction(function () use ($request, $user): void {
            $template = WorkflowTemplate::where('type', $request->type)->firstOrFail();

            $request->update([
                'status' => 'in_workflow',
                'submitted_at' => now(),
            ]);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.submitted')
                ->withProperties(['old_status' => 'draft', 'new_status' => 'in_workflow'])
                ->log('Submitted for approval');

            $this->engine->startWorkflow($request, $template);
        });
    }

    public function disburse(PaymentRequest $request, string $method, string|null $reference, User|null $user = null): void
    {
        DB::transaction(function () use ($request, $method, $reference, $user): void {
            $request->update([
                'status' => 'disbursed',
                'disbursed_at' => now(),
                'disbursed_by_user_id' => $user?->id,
                'disbursement_method' => $method,
                'disbursement_reference' => $reference,
            ]);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.disbursed')
                ->withProperties(['old_status' => 'approved', 'new_status' => 'disbursed', 'method' => $method])
                ->log('Disbursed');
        });

        $this->notifications->notifyDisbursed($request);
    }

    public function cancel(PaymentRequest $request, User|null $user = null): void
    {
        DB::transaction(function () use ($request, $user): void {
            $oldStatus = $request->status;
            $activeInstance = $request->activeWorkflowInstance;

            if ($activeInstance instanceof \App\Models\Tenant\WorkflowInstance) {
                $activeInstance->instanceStages()
                    ->whereIn('status', ['pending', 'active'])
                    ->update(['status' => 'cancelled', 'completed_at' => now()]);

                $activeInstance->update(['status' => 'cancelled']);
            }

            $request->update(['status' => 'cancelled']);

            activity()
                ->performedOn($request)
                ->causedBy($user)
                ->event('request.cancelled')
                ->withProperties(['old_status' => $oldStatus, 'new_status' => 'cancelled'])
                ->log('Request cancelled');
        });
    }
}
