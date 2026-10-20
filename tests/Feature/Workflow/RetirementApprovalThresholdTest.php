<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

use App\DTOs\Tenant\CreateRetirementRequestDto;
use App\DTOs\Tenant\RetirementRequestItemDto;
use App\Models\Role;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\RetirementService;
use App\Services\WorkflowEngineService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TenantAppTestCase;

class RetirementApprovalThresholdTest extends TenantAppTestCase
{
    /** @return array<string, array{float, float|null, string}> */
    public static function retirementAmounts(): array
    {
        return [
            'below threshold' => [3000.0, 5000.0, 'skipped'],
            'exactly at threshold' => [5000.0, 5000.0, 'active'],
            'above threshold' => [8000.0, 5000.0, 'active'],
            'no threshold' => [8000.0, null, 'active'],
            'zero threshold' => [8000.0, 0.0, 'active'],
            'no spend below threshold' => [0.0, 5000.0, 'skipped'],
            'no spend without threshold' => [0.0, null, 'active'],
        ];
    }

    #[DataProvider('retirementAmounts')]
    public function test_retirement_stage_uses_recorded_expenditure(
        float $expenditure,
        float|null $threshold,
        string $expectedStatus,
    ): void {
        Notification::fake();

        $template = WorkflowTemplate::factory()->retirement()->create();
        $approver = User::factory()->create();
        $stage = $this->createStage($template, $approver, 1, $threshold);
        $retirement = $this->createRetirement($expenditure);

        app(RetirementService::class)->submit($retirement, $this->user);

        $instance = $retirement->workflowInstances()->sole();
        $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stage->id)->sole();
        $retirement->refresh();

        $this->assertSame($expectedStatus, $instanceStage->status, sprintf(
            'Expenditure %.2f, threshold %s: stage=%s, retirement=%s, actions=%d.',
            $expenditure,
            $threshold === null ? 'none' : number_format($threshold, 2, '.', ''),
            $instanceStage->status,
            $retirement->status,
            $instanceStage->actions()->count(),
        ));
    }

    public function test_high_value_retirement_cannot_complete_without_required_approvals(): void
    {
        Notification::fake();

        $template = WorkflowTemplate::factory()->retirement()->create();
        $this->createStage($template, User::factory()->create(), 1, 1000.0);
        $this->createStage($template, User::factory()->create(), 2, 5000.0);
        $retirement = $this->createRetirement(8000.0);

        app(RetirementService::class)->submit($retirement, $this->user);

        $instance = $retirement->workflowInstances()->sole();
        $retirement->refresh();
        $actionCount = $instance->instanceStages()->get()->sum(
            fn(WorkflowInstanceStage $stage): int => $stage->actions()->count(),
        );

        $this->assertSame('in_workflow', $retirement->status, sprintf(
            'Expenditure 8000.00, thresholds 1000.00 and 5000.00: retirement=%s, workflow=%s, skipped_stages=%d, actions=%d, approved_at=%s.',
            $retirement->status,
            $instance->status,
            $instance->instanceStages()->where('status', 'skipped')->count(),
            $actionCount,
            $retirement->approved_at?->toDateTimeString() ?? 'none',
        ));
        $this->assertSame('in_progress', $instance->status);
        $this->assertNull($retirement->approved_at);
        $this->assertSame(0, $actionCount);
        $this->assertSame(0, $instance->instanceStages()->where('status', 'skipped')->count());
    }

    public function test_required_director_review_remains_after_manager_approval(): void
    {
        Notification::fake();

        $template = WorkflowTemplate::factory()->retirement()->create();
        $manager = User::factory()->create();
        $managerStage = $this->createStage($template, $manager, 1, null);
        $directorStage = $this->createStage($template, User::factory()->create(), 2, 5000.0);
        $retirement = $this->createRetirement(8000.0);

        app(RetirementService::class)->submit($retirement, $this->user);

        $instance = $retirement->workflowInstances()->sole();
        $activeStage = $instance->instanceStages()->where('workflow_stage_id', $managerStage->id)->sole();
        $this->assertSame('active', $activeStage->status);

        app(WorkflowEngineService::class)->approve($activeStage, $manager, 'Manager review complete.');

        $directorInstanceStage = $instance->instanceStages()->where('workflow_stage_id', $directorStage->id)->sole();
        $retirement->refresh();

        $this->assertSame('active', $directorInstanceStage->status, sprintf(
            'After manager approval of expenditure 8000.00: director_stage=%s, retirement=%s, director_actions=%d.',
            $directorInstanceStage->status,
            $retirement->status,
            $directorInstanceStage->actions()->count(),
        ));
        $this->assertSame('in_workflow', $retirement->status);
        $this->assertNull($retirement->approved_at);
    }

    /** @return array<string, array{bool, mixed}> */
    public static function invalidApprovalAmounts(): array
    {
        $cases = [];
        foreach (['retirement' => true, 'payment' => false] as $type => $isRetirement) {
            foreach ([
                'missing' => null,
                'empty' => '',
                'non-numeric' => 'invalid',
                'negative' => -1.0,
                'not a number' => NAN,
                'infinity' => INF,
                'overflow' => '1e999',
            ] as $name => $amount) {
                $cases[$type . ' ' . $name] = [$isRetirement, $amount];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidApprovalAmounts')]
    public function test_invalid_amount_cannot_skip_pending_approval(bool $isRetirement, mixed $amount): void
    {
        Notification::fake();

        $retirement = $this->createRetirement(8000.0);
        $subject = $isRetirement ? $retirement : $retirement->paymentRequest;
        $this->assertNotNull($subject);
        $template = WorkflowTemplate::factory()->create(['type' => $isRetirement ? 'retirement' : 'advance']);
        $stage = $this->createStage($template, User::factory()->create(), 1, 5000.0);
        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => $subject::class,
            'workflowable_id' => $subject->getKey(),
            'status' => 'in_progress',
            'submitter_user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
        ]);
        $instanceStage = WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'pending',
        ]);

        $amountField = $isRetirement ? 'total_amount_expended' : 'total_amount';
        $attributes = $subject->getAttributes();
        if ($amount === null) {
            unset($attributes[$amountField]);
        } else {
            $attributes[$amountField] = $amount;
        }
        $subject->setRawAttributes($attributes);
        $instance->setRelation('workflowable', $subject);

        try {
            app(WorkflowEngineService::class)->activateNextStages($instance);
            $this->fail('An invalid amount must stop approval progression.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey($amountField, $exception->errors());
        }

        $this->assertSame('pending', $instanceStage->refresh()->status);
        $this->assertNull($instanceStage->completed_at);
        $this->assertSame('in_progress', $instance->refresh()->status);
        $this->assertNull($subject->refresh()->approved_at);
        $this->assertSame(0, $instanceStage->actions()->count());
        Notification::assertNothingSent();
    }

    public function test_invalid_retirement_submission_rolls_back_status_and_workflow(): void
    {
        Notification::fake();

        $template = WorkflowTemplate::factory()->retirement()->create();
        $this->createStage($template, User::factory()->create(), 1, 5000.0);
        $retirement = $this->createRetirement(8000.0);
        $retirement->update(['total_amount_expended' => -1]);

        try {
            app(RetirementService::class)->submit($retirement, $this->user);
            $this->fail('Invalid expenditure must prevent submission.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('total_amount_expended', $exception->errors());
        }

        $this->assertSame('draft', $retirement->refresh()->status);
        $this->assertNull($retirement->submitted_at);
        $this->assertNull($retirement->approved_at);
        $this->assertSame(0, $retirement->workflowInstances()->count());
        $this->assertDatabaseCount('workflow_instance_stages', 0);
        Notification::assertNothingSent();
    }

    private function createRetirement(float $expenditure): RetirementRequest
    {
        $advance = PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
            'currency_id' => $this->branch->currency_id,
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'total_amount' => 10000.0,
        ]);
        $costCode = CostCode::factory()->create();

        return app(RetirementService::class)->createDraft($advance, new CreateRetirementRequestDto(
            notes: 'Retirement approval threshold regression.',
            didNotSpendMoney: $expenditure === 0.0,
            items: $expenditure === 0.0 ? [] : [new RetirementRequestItemDto(
                description: 'Recorded expenditure',
                amount: $expenditure,
                costCodeId: $costCode->id,
                receiptNumber: null,
            )],
        ), $this->user);
    }

    private function createStage(
        WorkflowTemplate $template,
        User $approver,
        int $order,
        float|null $threshold,
    ): WorkflowStage {
        $role = Role::create([
            'name' => 'retirement_threshold_' . Str::uuid()->toString(),
            'guard_name' => 'web',
        ]);
        $approver->assignRole($role);

        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => $order,
            'skip_below_amount' => $threshold,
        ]);
        $stage->roles()->sync([$role->id]);

        return $stage;
    }
}
