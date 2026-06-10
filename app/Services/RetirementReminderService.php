<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\PaymentRequestStatus;
use App\Enums\Tenant\PaymentRequestType;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementReminderLog;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowAction;
use App\Notifications\RetirementOverdueNotification;
use App\Repositories\BranchRepository;
use App\Repositories\RetirementReminderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class RetirementReminderService
{
    public function __construct(
        private readonly SettingsService $settingsService,
        private readonly RetirementReminderRepository $reminderRepository,
        private readonly BranchRepository $branches,
    ) {}

    public function sendReminders(): int
    {
        $settings = $this->settingsService->getRetirementReminderSettings();
        $gracePeriodDays = $settings['grace_period_days'];
        $frequencyDays = $settings['frequency_days'];
        $today = Carbon::today();

        $overdueAdvances = PaymentRequest::query()
            ->where('type', PaymentRequestType::Advance->value)
            ->where('status', PaymentRequestStatus::Disbursed->value)
            ->whereNotNull('disbursed_at')
            ->whereDate('disbursed_at', '<=', $today->copy()->subDays($gracePeriodDays))
            ->whereDoesntHave('retirementRequests', fn($q) => $q->whereIn('status', ['in_workflow', 'approved', 'sent_back']))
            ->get();

        $sent = 0;

        foreach ($overdueAdvances as $paymentRequest) {
            $disbursedAt = Carbon::parse($paymentRequest->getAttribute('disbursed_at'))->startOfDay();
            $daysLate = (int) abs($today->diffInDays($disbursedAt)) - $gracePeriodDays;

            if ($daysLate < 0 || ($frequencyDays > 0 && $daysLate % $frequencyDays !== 0)) {
                continue;
            }

            $recipients = $this->buildRecipients($paymentRequest, $settings);

            foreach ($recipients as $user) {
                $alreadySent = RetirementReminderLog::query()
                    ->where('payment_request_id', $paymentRequest->id)
                    ->where('user_id', $user->id)
                    ->whereDate('notified_date', $today)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $recipientType = $this->resolveRecipientType($paymentRequest, $user, $settings);
                $user->notify(new RetirementOverdueNotification($paymentRequest, $recipientType));

                RetirementReminderLog::firstOrCreate([
                    'payment_request_id' => $paymentRequest->id,
                    'user_id' => $user->id,
                    'notified_date' => $today->toDateString(),
                ]);

                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param PaymentRequest $paymentRequest
     * @param array{grace_period_days: int, frequency_days: int, notify_submitter: bool, notify_approvers: bool, notify_role_ids: list<int>} $settings
     *
     * @return Collection<int, User>
     */
    private function buildRecipients(PaymentRequest $paymentRequest, array $settings): Collection
    {
        /** @var Collection<int, User> $recipients */
        $recipients = new Collection();

        if ($settings['notify_submitter']) {
            $submitter = $this->resolveSubmitter($paymentRequest);
            if ($submitter instanceof User) {
                $recipients->push($submitter);
            }
        }

        if ($settings['notify_approvers']) {
            $approvers = User::query()
                ->whereIn('id', function ($query) use ($paymentRequest): void {
                    $query->select('wa.user_id')
                        ->from('workflow_actions as wa')
                        ->join('workflow_instance_stages as wis', 'wis.id', '=', 'wa.workflow_instance_stage_id')
                        ->join('workflow_instances as wi', 'wi.id', '=', 'wis.workflow_instance_id')
                        ->where('wi.workflowable_type', PaymentRequest::class)
                        ->where('wi.workflowable_id', $paymentRequest->id)
                        ->where('wa.action', 'approve');
                })
                ->whereNotNull('email')
                ->get();

            $recipients = $recipients->merge($approvers);
        }

        if (! empty($settings['notify_role_ids'])) {
            $roleUsers = User::query()
                ->whereHas('roles', fn($q) => $q->whereIn('id', $settings['notify_role_ids']))
                ->whereNotNull('email')
                ->get();

            $recipients = $recipients->merge($roleUsers);
        }

        return $recipients->unique('id')->values();
    }

    private function resolveSubmitter(PaymentRequest $paymentRequest): User|null
    {
        /** @var Activity|null $activity */
        $activity = $paymentRequest->activities()
            ->where('event', 'request.submitted')
            ->latest()
            ->first();

        return $activity?->causer instanceof User ? $activity->causer : null;
    }

    /**
     * @param PaymentRequest $paymentRequest
     * @param User $user
     * @param array{grace_period_days: int, frequency_days: int, notify_submitter: bool, notify_approvers: bool, notify_role_ids: list<int>} $settings
     */
    private function resolveRecipientType(PaymentRequest $paymentRequest, User $user, array $settings): string
    {
        if ($settings['notify_submitter']) {
            $submitter = $this->resolveSubmitter($paymentRequest);
            if ($submitter instanceof User && $submitter->id === $user->id) {
                return 'submitter';
            }
        }

        if ($settings['notify_approvers']) {
            $isApprover = WorkflowAction::query()
                ->where('user_id', $user->id)
                ->where('action', 'approve')
                ->whereHas(
                    'instanceStage.instance',
                    fn($q) => $q
                        ->where('workflowable_type', PaymentRequest::class)
                        ->where('workflowable_id', $paymentRequest->id),
                )
                ->exists();

            if ($isApprover) {
                return 'approver';
            }
        }

        return 'role';
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     *
     * @return array<string, mixed>
     */
    public function getReport(array $allowedBranchIds, string $dateFrom, string $dateTo, int|string|null $branchId): array
    {
        return [
            'rows' => $this->reminderRepository->reportRows($allowedBranchIds, $dateFrom, $dateTo, $branchId),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branchId' => $branchId,
            'branches' => $this->branches->allByIdsOrderedByName($allowedBranchIds),
        ];
    }
}
