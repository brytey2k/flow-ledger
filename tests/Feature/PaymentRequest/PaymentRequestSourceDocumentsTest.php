<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\SettingKey;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function enableRequireSourceDocumentsForPaymentRequestSourceDocuments(): void
{
    Setting::updateOrCreate(
        ['key' => SettingKey::RequireExpenseSourceDocuments->value],
        ['value' => ['required' => true]],
    );
}
function linkUserToStaffForPaymentRequestSourceDocuments(): Staff
{
    return Staff::factory()->withUser(test()->user)->withBranch(test()->branch)->create();
}
function expenseTemplateWithStage(): WorkflowTemplate
{
    $template = WorkflowTemplate::factory()->expense()->create();
    WorkflowStage::factory()->for($template, 'template')->create();

    return $template;
}
test('settings update enables require source documents', function () {
    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'require_expense_source_documents' => '1',
    ]);

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('success');

    $setting = Setting::where('key', SettingKey::RequireExpenseSourceDocuments->value)->first();
    expect($setting)->not->toBeNull();
    expect((bool) ($setting->value['required'] ?? false))->toBeTrue();
});
test('settings update disables require source documents', function () {
    enableRequireSourceDocumentsForPaymentRequestSourceDocuments();

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'require_expense_source_documents' => '0',
    ]);

    $response->assertRedirect(route('settings.index'));

    $setting = Setting::where('key', SettingKey::RequireExpenseSourceDocuments->value)->first();
    expect((bool) ($setting->value['required'] ?? false))->toBeFalse();
});
test('owner can upload attachment to draft expense', function () {
    Storage::fake('local');

    $staff = linkUserToStaffForPaymentRequestSourceDocuments();
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
});
test('owner can upload attachment to sent back expense', function () {
    Storage::fake('local');

    $staff = linkUserToStaffForPaymentRequestSourceDocuments();
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
    expect($expense->refresh()->attachments)->toHaveCount(1);
});
test('non owner cannot upload attachment', function () {
    Storage::fake('local');

    $expense = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

    $file = UploadedFile::fake()->create('receipt.pdf', 512, 'application/pdf');

    $response = $this->actingAs($this->user)->post(
        route('payment-requests.attachments.store', $expense),
        ['file' => $file],
    );

    $response->assertForbidden();
});
test('cannot upload attachment to approved expense', function () {
    Storage::fake('local');

    $staff = linkUserToStaffForPaymentRequestSourceDocuments();
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
});
test('expense submission blocked when setting on and no attachments', function () {
    enableRequireSourceDocumentsForPaymentRequestSourceDocuments();

    $staff = linkUserToStaffForPaymentRequestSourceDocuments();
    $expense = PaymentRequest::factory()->expense()->create([
        'status' => 'draft',
        'branch_id' => $this->branch->id,
        'staff_id' => $staff->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $expense));

    $response->assertRedirect(route('payment-requests.show', $expense));
    $response->assertSessionHas('error');

    expect($expense->refresh()->status)->toBe('draft');
});
test('expense submission succeeds when setting on and has attachment', function () {
    enableRequireSourceDocumentsForPaymentRequestSourceDocuments();
    expenseTemplateWithStage();

    $staff = linkUserToStaffForPaymentRequestSourceDocuments();
    $expense = PaymentRequest::factory()->expense()->create([
        'status' => 'draft',
        'branch_id' => $this->branch->id,
        'staff_id' => $staff->id,
    ]);
    Attachment::factory()->create([
        'attachable_type' => PaymentRequest::class,
        'attachable_id' => $expense->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $expense));

    $response->assertRedirect(route('payment-requests.show', $expense));
    $response->assertSessionHas('success');

    expect($expense->refresh()->status)->toBe('in_workflow');
});
test('expense submission succeeds when setting off and no attachments', function () {
    expenseTemplateWithStage();

    $staff = linkUserToStaffForPaymentRequestSourceDocuments();
    $expense = PaymentRequest::factory()->expense()->create([
        'status' => 'draft',
        'branch_id' => $this->branch->id,
        'staff_id' => $staff->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $expense));

    $response->assertRedirect(route('payment-requests.show', $expense));
    $response->assertSessionHas('success');
});
test('advance submission unaffected by source document setting', function () {
    enableRequireSourceDocumentsForPaymentRequestSourceDocuments();

    $staff = linkUserToStaffForPaymentRequestSourceDocuments();
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'branch_id' => $this->branch->id,
        'staff_id' => $staff->id,
    ]);
    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowStage::factory()->for($template, 'template')->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $advance));

    $response->assertRedirect(route('payment-requests.show', $advance));
    $response->assertSessionHas('success');
});
test('expense resubmit blocked when setting on and no attachments', function () {
    enableRequireSourceDocumentsForPaymentRequestSourceDocuments();

    $staff = linkUserToStaffForPaymentRequestSourceDocuments();
    $expense = PaymentRequest::factory()->expense()->create([
        'status' => 'sent_back',
        'staff_id' => $staff->id,
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('payment-requests.resubmit', $expense));

    $response->assertRedirect(route('payment-requests.show', $expense));
    $response->assertSessionHas('error');

    expect($expense->refresh()->status)->toBe('sent_back');
});
