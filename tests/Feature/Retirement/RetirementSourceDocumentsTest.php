<?php

declare(strict_types=1);

namespace Tests\Feature\Retirement;

use App\Enums\Tenant\SettingKey;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\RetirementService;
use Tests\TenantAppTestCase;

class RetirementSourceDocumentsTest extends TenantAppTestCase
{
    private function enableRequireSourceDocuments(): void
    {
        Setting::updateOrCreate(
            ['key' => SettingKey::RequireRetirementSourceDocuments->value],
            ['value' => ['required' => true]],
        );
    }

    private function retirementTemplateWithStage(): WorkflowTemplate
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        WorkflowStage::factory()->for($template, 'template')->create();

        return $template;
    }

    private function disbursedAdvanceForUser(): PaymentRequest
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        return PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    // ── Settings toggle ───────────────────────────────────────────────────────

    public function test_settings_update_enables_require_retirement_source_documents(): void
    {
        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'require_retirement_source_documents' => '1',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $setting = Setting::where('key', SettingKey::RequireRetirementSourceDocuments->value)->first();
        $this->assertNotNull($setting);
        $this->assertTrue((bool) ($setting->value['required'] ?? false));
    }

    public function test_settings_update_disables_require_retirement_source_documents(): void
    {
        $this->enableRequireSourceDocuments();

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'require_retirement_source_documents' => '0',
        ]);

        $response->assertRedirect(route('settings.index'));

        $setting = Setting::where('key', SettingKey::RequireRetirementSourceDocuments->value)->first();
        $this->assertFalse((bool) ($setting->value['required'] ?? false));
    }

    // ── Submit blocked ────────────────────────────────────────────────────────

    public function test_retirement_submission_blocked_when_setting_on_and_no_attachments(): void
    {
        $this->enableRequireSourceDocuments();
        $this->retirementTemplateWithStage();

        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => $this->disbursedAdvanceForUser()->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error');

        $this->assertSame('draft', $retirement->refresh()->status);
    }

    // ── Submit passes ─────────────────────────────────────────────────────────

    public function test_retirement_submission_succeeds_when_setting_on_and_has_attachment(): void
    {
        $this->enableRequireSourceDocuments();
        $this->retirementTemplateWithStage();

        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => $this->disbursedAdvanceForUser()->id,
        ]);
        Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('success');

        $this->assertSame('in_workflow', $retirement->refresh()->status);
    }

    public function test_retirement_submission_succeeds_when_setting_off_and_no_attachments(): void
    {
        $this->retirementTemplateWithStage();

        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => $this->disbursedAdvanceForUser()->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('success');
    }

    // ── Resubmit blocked ──────────────────────────────────────────────────────

    public function test_retirement_resubmit_blocked_when_setting_on_and_no_attachments(): void
    {
        $this->enableRequireSourceDocuments();
        $this->retirementTemplateWithStage();

        $paymentRequest = $this->disbursedAdvanceForUser();
        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => $paymentRequest->id,
        ]);
        app(RetirementService::class)->submit($retirement);

        $retirement->refresh();
        $instance = $retirement->activeWorkflowInstance;
        $instanceStage = $instance->instanceStages()->first();
        $instanceStage->update(['status' => 'sent_back', 'completed_at' => now()]);
        $instance->update(['sent_back_to_stage_id' => $instanceStage->id]);
        $retirement->update(['status' => 'sent_back']);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.resubmit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error');

        $this->assertSame('sent_back', $retirement->refresh()->status);
    }

    public function test_retirement_resubmit_succeeds_when_setting_on_and_has_attachment(): void
    {
        $this->enableRequireSourceDocuments();
        $this->retirementTemplateWithStage();

        $paymentRequest = $this->disbursedAdvanceForUser();
        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => $paymentRequest->id,
        ]);
        app(RetirementService::class)->submit($retirement);

        $retirement->refresh();
        $instance = $retirement->activeWorkflowInstance;
        $instanceStage = $instance->instanceStages()->first();
        $instanceStage->update(['status' => 'sent_back', 'completed_at' => now()]);
        $instance->update(['sent_back_to_stage_id' => $instanceStage->id]);
        $retirement->update(['status' => 'sent_back']);

        Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.resubmit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('success');

        $this->assertSame('in_workflow', $retirement->refresh()->status);
    }
}
