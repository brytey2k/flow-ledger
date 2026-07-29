<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\SettingKey;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\RetirementService;

function enableRequireSourceDocumentsForRetirementSourceDocuments(): void
{
    Setting::updateOrCreate(
        ['key' => SettingKey::RequireRetirementSourceDocuments->value],
        ['value' => ['required' => true]],
    );
}
function retirementTemplateWithStage(): WorkflowTemplate
{
    $template = WorkflowTemplate::factory()->retirement()->create();
    WorkflowStage::factory()->for($template, 'template')->create();

    return $template;
}
function disbursedAdvanceForUser(): PaymentRequest
{
    $staff = Staff::factory()->withUser(test()->user)->withBranch(test()->branch)->create();

    return PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'staff_id' => $staff->id,
        'branch_id' => test()->branch->id,
    ]);
}
test('settings update enables require retirement source documents', function () {
    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'require_retirement_source_documents' => '1',
    ]);

    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('success');

    $setting = Setting::where('key', SettingKey::RequireRetirementSourceDocuments->value)->first();
    expect($setting)->not->toBeNull();
    expect((bool) ($setting->value['required'] ?? false))->toBeTrue();
});
test('settings update disables require retirement source documents', function () {
    enableRequireSourceDocumentsForRetirementSourceDocuments();

    $response = $this->actingAs($this->user)->put(route('settings.update'), [
        'require_retirement_source_documents' => '0',
    ]);

    $response->assertRedirect(route('settings.index'));

    $setting = Setting::where('key', SettingKey::RequireRetirementSourceDocuments->value)->first();
    expect((bool) ($setting->value['required'] ?? false))->toBeFalse();
});
test('retirement submission blocked when setting on and no attachments', function () {
    enableRequireSourceDocumentsForRetirementSourceDocuments();
    retirementTemplateWithStage();

    $retirement = RetirementRequest::factory()->create([
        'status' => 'draft',
        'payment_request_id' => disbursedAdvanceForUser()->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $response->assertSessionHas('error');

    expect($retirement->refresh()->status)->toBe('draft');
});
test('retirement submission succeeds when setting on and has attachment', function () {
    enableRequireSourceDocumentsForRetirementSourceDocuments();
    retirementTemplateWithStage();

    $retirement = RetirementRequest::factory()->create([
        'status' => 'draft',
        'payment_request_id' => disbursedAdvanceForUser()->id,
    ]);
    Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $response->assertSessionHas('success');

    expect($retirement->refresh()->status)->toBe('in_workflow');
});
test('retirement submission succeeds when setting off and no attachments', function () {
    retirementTemplateWithStage();

    $retirement = RetirementRequest::factory()->create([
        'status' => 'draft',
        'payment_request_id' => disbursedAdvanceForUser()->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $response->assertSessionHas('success');
});
test('retirement resubmit blocked when setting on and no attachments', function () {
    enableRequireSourceDocumentsForRetirementSourceDocuments();
    retirementTemplateWithStage();

    $paymentRequest = disbursedAdvanceForUser();
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

    expect($retirement->refresh()->status)->toBe('sent_back');
});
test('retirement resubmit succeeds when setting on and has attachment', function () {
    enableRequireSourceDocumentsForRetirementSourceDocuments();
    retirementTemplateWithStage();

    $paymentRequest = disbursedAdvanceForUser();
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

    expect($retirement->refresh()->status)->toBe('in_workflow');
});
