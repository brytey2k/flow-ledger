<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Enums\Tenant\SettingKey;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantAppTestCase;

class PaymentRequestSourceDocumentsTest extends TenantAppTestCase
{
    private function enableRequireSourceDocuments(): void
    {
        Setting::updateOrCreate(
            ['key' => SettingKey::RequireExpenseSourceDocuments->value],
            ['value' => ['required' => true]],
        );
    }

    private function linkUserToStaff(): Staff
    {
        return Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    }

    private function expenseTemplateWithStage(): WorkflowTemplate
    {
        $template = WorkflowTemplate::factory()->expense()->create();
        WorkflowStage::factory()->for($template, 'template')->create();

        return $template;
    }

    // ── Settings toggle ───────────────────────────────────────────────────────

    public function test_settings_update_enables_require_source_documents(): void
    {
        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'require_expense_source_documents' => '1',
        ]);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        $setting = Setting::where('key', SettingKey::RequireExpenseSourceDocuments->value)->first();
        $this->assertNotNull($setting);
        $this->assertTrue((bool) ($setting->value['required'] ?? false));
    }

    public function test_settings_update_disables_require_source_documents(): void
    {
        $this->enableRequireSourceDocuments();

        $response = $this->actingAs($this->user)->put(route('settings.update'), [
            'require_expense_source_documents' => '0',
        ]);

        $response->assertRedirect(route('settings.index'));

        $setting = Setting::where('key', SettingKey::RequireExpenseSourceDocuments->value)->first();
        $this->assertFalse((bool) ($setting->value['required'] ?? false));
    }

    // ── Attachment upload ─────────────────────────────────────────────────────

    public function test_owner_can_upload_attachment_to_draft_expense(): void
    {
        Storage::fake('local');

        $staff = $this->linkUserToStaff();
        $expense = PaymentRequest::factory()->expense()->create([
            'status' => 'draft',
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);

        $file = UploadedFile::fake()->create('receipt.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.attachments.store', $expense),
            ['file' => $file],
        );

        $response->assertRedirect(route('payment-requests.show', $expense));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => PaymentRequest::class,
            'attachable_id' => $expense->id,
        ]);
    }

    public function test_owner_can_upload_attachment_to_sent_back_expense(): void
    {
        Storage::fake('local');

        $staff = $this->linkUserToStaff();
        $expense = PaymentRequest::factory()->expense()->create([
            'status' => 'sent_back',
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);

        $file = UploadedFile::fake()->create('invoice.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.attachments.store', $expense),
            ['file' => $file],
        );

        $response->assertRedirect(route('payment-requests.show', $expense));
        $this->assertCount(1, $expense->refresh()->attachments);
    }

    public function test_non_owner_cannot_upload_attachment(): void
    {
        Storage::fake('local');

        $expense = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

        $file = UploadedFile::fake()->create('receipt.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.attachments.store', $expense),
            ['file' => $file],
        );

        $response->assertForbidden();
    }

    public function test_cannot_upload_attachment_to_approved_expense(): void
    {
        Storage::fake('local');

        $staff = $this->linkUserToStaff();
        $expense = PaymentRequest::factory()->expense()->create([
            'status' => 'approved',
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);

        $file = UploadedFile::fake()->create('receipt.pdf', 512, 'application/pdf');

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.attachments.store', $expense),
            ['file' => $file],
        );

        $response->assertForbidden();
    }

    // ── Submit blocked ────────────────────────────────────────────────────────

    public function test_expense_submission_blocked_when_setting_on_and_no_attachments(): void
    {
        $this->enableRequireSourceDocuments();

        $expense = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $expense));

        $response->assertRedirect(route('payment-requests.show', $expense));
        $response->assertSessionHas('error');

        $this->assertSame('draft', $expense->refresh()->status);
    }

    // ── Submit passes ─────────────────────────────────────────────────────────

    public function test_expense_submission_succeeds_when_setting_on_and_has_attachment(): void
    {
        $this->enableRequireSourceDocuments();
        $this->expenseTemplateWithStage();

        $expense = PaymentRequest::factory()->expense()->create(['status' => 'draft']);
        Attachment::factory()->create([
            'attachable_type' => PaymentRequest::class,
            'attachable_id' => $expense->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $expense));

        $response->assertRedirect(route('payment-requests.show', $expense));
        $response->assertSessionHas('success');

        $this->assertSame('in_workflow', $expense->refresh()->status);
    }

    public function test_expense_submission_succeeds_when_setting_off_and_no_attachments(): void
    {
        $this->expenseTemplateWithStage();

        $expense = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $expense));

        $response->assertRedirect(route('payment-requests.show', $expense));
        $response->assertSessionHas('success');
    }

    // ── Advance unaffected ────────────────────────────────────────────────────

    public function test_advance_submission_unaffected_by_source_document_setting(): void
    {
        $this->enableRequireSourceDocuments();

        $advance = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        $template = WorkflowTemplate::factory()->advance()->create();
        WorkflowStage::factory()->for($template, 'template')->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $advance));

        $response->assertRedirect(route('payment-requests.show', $advance));
        $response->assertSessionHas('success');
    }

    // ── Resubmit blocked ──────────────────────────────────────────────────────

    public function test_expense_resubmit_blocked_when_setting_on_and_no_attachments(): void
    {
        $this->enableRequireSourceDocuments();

        $staff = $this->linkUserToStaff();
        $expense = PaymentRequest::factory()->expense()->create([
            'status' => 'sent_back',
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('payment-requests.resubmit', $expense));

        $response->assertRedirect(route('payment-requests.show', $expense));
        $response->assertSessionHas('error');

        $this->assertSame('sent_back', $expense->refresh()->status);
    }
}
