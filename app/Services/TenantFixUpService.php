<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\PaymentRequestStatus;
use App\Enums\Tenant\WorkflowDocumentRequestStatus;
use App\Models\Tenant;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementReminderLog;
use App\Models\Tenant\RetirementRequest;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

/** Targeted, single-record repairs run on behalf of a landlord admin. */
class TenantFixUpService
{
    public function __construct(
        private readonly PaymentRequestService $paymentRequests,
        private readonly RetirementService $retirements,
        private readonly RetirementReminderService $reminders,
    ) {}

    /** @return array<string, int> */
    public function forceCancel(Tenant $tenant, string $kind, int $id): array
    {
        /** @var array<string, int> $result */
        $result = $tenant->run(function () use ($kind, $id): array {
            $request = match ($kind) {
                'payment' => PaymentRequest::findOrFail($id),
                'retirement' => RetirementRequest::findOrFail($id),
                default => throw new InvalidArgumentException('Unknown request kind.'),
            };

            if (! $request->isCancelable()) {
                throw new LogicException("A request with status \"{$request->status}\" cannot be cancelled.");
            }

            return DB::transaction(function () use ($request): array {
                $cancelledDocumentRequests = DB::table('workflow_document_requests')
                    ->whereIn('workflow_instance_id', $request->workflowInstances()->select('id')->where('status', 'in_progress'))
                    ->whereIn('status', [
                        WorkflowDocumentRequestStatus::AwaitingUploads->value,
                        WorkflowDocumentRequestStatus::Submitted->value,
                        WorkflowDocumentRequestStatus::AwaitingReReview->value,
                    ])
                    ->update([
                        'status' => WorkflowDocumentRequestStatus::Cancelled->value,
                        'resolution' => 'administrative_cancel',
                        'cancelled_at' => now(),
                    ]);

                $request instanceof PaymentRequest
                    ? $this->paymentRequests->cancel($request)
                    : $this->retirements->cancel($request);

                return ['requests_cancelled' => 1, 'document_requests_cancelled' => $cancelledDocumentRequests];
            });
        });

        return $result;
    }

    /** @return array<string, int> */
    public function reopenRetirement(Tenant $tenant, int $retirementId, string $actor): array
    {
        /** @var array<string, int> $result */
        $result = $tenant->run(fn(): array => DB::transaction(function () use ($retirementId, $actor): array {
            $retirement = RetirementRequest::lockForUpdate()->findOrFail($retirementId);

            if ($retirement->status !== 'settled') {
                throw new LogicException('Only a settled retirement can be re-opened.');
            }

            $entries = $this->reverseCashbookEntries($retirement);

            $retirement->update([
                'status' => 'approved',
                'settled_at' => null,
                'settled_by_user_id' => null,
                'settlement_notes' => null,
            ]);

            $paymentRequest = $retirement->paymentRequest;
            $reverted = 0;
            if ($paymentRequest instanceof PaymentRequest && $paymentRequest->isRetired()) {
                $paymentRequest->update(['status' => PaymentRequestStatus::Disbursed->value]);
                $reverted = 1;
            }

            activity()
                ->performedOn($retirement)
                ->event('retirement.reopened')
                ->withProperties(['old_status' => 'settled', 'new_status' => 'approved', 'by_landlord' => $actor])
                ->log('Settled retirement re-opened by a system administrator');

            return ['retirements_reopened' => 1, 'cashbook_entries_removed' => $entries, 'payment_requests_reverted_to_disbursed' => $reverted];
        }));

        return $result;
    }

    /** @return array<string, int> */
    public function reverseDisbursement(Tenant $tenant, int $paymentRequestId, string $actor): array
    {
        /** @var array<string, int> $result */
        $result = $tenant->run(fn(): array => DB::transaction(function () use ($paymentRequestId, $actor): array {
            $request = PaymentRequest::lockForUpdate()->findOrFail($paymentRequestId);

            if (! $request->isDisbursed()) {
                throw new LogicException('Only a disbursed payment request can have its disbursement reversed.');
            }

            if ($request->retirementRequests()->where('status', '!=', 'cancelled')->exists()) {
                throw new LogicException('This request has a retirement. Clear or cancel the retirement first.');
            }

            $entries = $this->reverseCashbookEntries($request);

            $request->update([
                'status' => PaymentRequestStatus::Approved->value,
                'disbursed_at' => null,
                'disbursed_by_user_id' => null,
                'disbursement_method' => null,
                'disbursement_reference' => null,
            ]);

            activity()
                ->performedOn($request)
                ->event('request.disbursement_reversed')
                ->withProperties(['old_status' => 'disbursed', 'new_status' => 'approved', 'by_landlord' => $actor])
                ->log('Disbursement reversed by a system administrator');

            return ['disbursements_reversed' => 1, 'cashbook_entries_removed' => $entries];
        }));

        return $result;
    }

    /** @return array<string, int> */
    public function resendRetirementReminders(Tenant $tenant, int|null $paymentRequestId): array
    {
        /** @var array<string, int> $result */
        $result = $tenant->run(function () use ($paymentRequestId): array {
            $cleared = RetirementReminderLog::query()
                ->whereDate('notified_date', today())
                ->when($paymentRequestId !== null, fn($query) => $query->where('payment_request_id', $paymentRequestId))
                ->delete();

            return ['reminder_logs_cleared' => $cleared, 'reminders_sent' => $this->reminders->sendReminders()];
        });

        return $result;
    }

    /** Removes the request's cashbook entries and moves each cashbook balance back by their net amount. */
    private function reverseCashbookEntries(PaymentRequest|RetirementRequest $request): int
    {
        $entries = $request->cashbookEntries()->get();

        foreach ($entries as $entry) {
            $amount = is_numeric($entry->amount) ? (float) $entry->amount : 0.0;
            $entry->type === 'debit'
                ? $entry->cashbook()->decrement('balance', $amount)
                : $entry->cashbook()->increment('balance', $amount);
            $entry->forceDelete();
        }

        return $entries->count();
    }
}
