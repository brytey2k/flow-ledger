<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowEngineService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings as WordSettings;
use PhpOffice\PhpWord\Writer\Word2007;
use PHPUnit\Framework\Assert;

function draftRetirementForAttachmentsController(): RetirementRequest
{
    $staff = Staff::factory()->withUser(test()->user)->create(['branch_id' => test()->branch->id]);
    $advance = PaymentRequest::factory()->advance()->create([
        'staff_id' => $staff->id,
        'branch_id' => test()->branch->id,
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);

    return RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'draft',
    ]);
}
function retirementInWorkflow(): RetirementRequest
{
    $advance = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);

    return RetirementRequest::factory()->inWorkflow()->create([
        'payment_request_id' => $advance->id,
    ]);
}
/** @return array{template: WorkflowTemplate, stage: WorkflowStage, role: Role} */
function makeApprovalStageForAttachmentsController(int $displayOrder = 1): array
{
    $template = WorkflowTemplate::factory()->retirement()->create();
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => $displayOrder,
    ]);
    $stage->roles()->sync([$role->id]);

    return ['template' => $template, 'stage' => $stage->fresh(), 'role' => $role];
}

function createSpreadsheetForAttachmentPreview(string $path): void
{
    Storage::disk('local')->makeDirectory(dirname($path));

    $spreadsheet = new Spreadsheet();
    $spreadsheet->getActiveSheet()
        ->setTitle('Report')
        ->setCellValue('A1', 'Invoice Number')
        ->setCellValue('B1', 'Amount')
        ->setCellValue('A2', 'INV-001')
        ->setCellValue('B2', 1250);

    $spreadsheet->createSheet()
        ->setTitle('Summary')
        ->setCellValue('A1', 'Visible Summary Value');

    $spreadsheet->createSheet()
        ->setTitle('Dropdown Data')
        ->setCellValue('A1', 'Dropdown-only secret')
        ->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

    $spreadsheet->createSheet()
        ->setTitle('Internal Data')
        ->setCellValue('A1', 'Very-hidden secret')
        ->setSheetState(Worksheet::SHEETSTATE_VERYHIDDEN);

    $spreadsheet->setActiveSheetIndex(0);

    (new Xlsx($spreadsheet))->save(Storage::disk('local')->path($path));
    $spreadsheet->disconnectWorksheets();
}

function createWordDocumentForAttachmentPreview(string $path): void
{
    Storage::disk('local')->makeDirectory(dirname($path));

    $document = new PhpWord();
    $section = $document->addSection();
    $section->addTitle('Quarterly report', 1);
    $section->addText('DOCX preview content');
    $section->addText('HOSTILE_TEXT');

    $outputEscapingWasEnabled = WordSettings::isOutputEscapingEnabled();

    try {
        WordSettings::setOutputEscapingEnabled(true);
        (new Word2007($document))->save(Storage::disk('local')->path($path));
    } finally {
        WordSettings::setOutputEscapingEnabled($outputEscapingWasEnabled);
    }

    $archive = new ZipArchive();
    Assert::assertTrue($archive->open(Storage::disk('local')->path($path)) === true);
    $documentXml = $archive->getFromName('word/document.xml');
    Assert::assertIsString($documentXml);
    $archive->addFromString(
        'word/document.xml',
        str_replace('HOSTILE_TEXT', '&lt;script&gt;alert(&quot;docx&quot;)&lt;/script&gt;', $documentXml),
    );
    $archive->close();
}
test('guest cannot upload', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();

    $this->post(route('retirement-requests.attachments.store', $retirement), [
        'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('login'));
});
test('guest cannot delete', function () {
    $retirement = draftRetirementForAttachmentsController();
    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => 'retirements/1/attachments/test.pdf',
    ]);

    $this->delete(route('attachments.destroy', $attachment))
        ->assertRedirect(route('login'));
});
test('user without permission cannot upload', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateRetirementRequest->value);
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.attachments.store', $retirement), [
            'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
});
test('non owner cannot upload', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    $otherUser = User::factory()->create(['operational_branch_id' => $this->branch->id]);
    $otherUser->assignRole($this->role);

    $this->actingAs($otherUser)
        ->post(route('retirement-requests.attachments.store', $retirement), [
            'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])->assertForbidden();
});
test('non owner cannot delete', function () {
    $retirement = draftRetirementForAttachmentsController();
    $otherUser = User::factory()->create();
    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $otherUser->id,
        'path' => 'retirements/1/attachments/test.pdf',
    ]);

    $this->actingAs($this->user)
        ->delete(route('attachments.destroy', $attachment))
        ->assertForbidden();
});
test('requester who is not uploader cannot delete', function () {
    $requester = User::factory()->create();
    $staff = Staff::factory()->withUser($requester)->create();
    $advance = PaymentRequest::factory()->advance()->create([
        'staff_id' => $staff->id,
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'draft',
    ]);

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => 'retirements/1/attachments/test.pdf',
    ]);

    $this->actingAs($requester)
        ->delete(route('attachments.destroy', $attachment))
        ->assertForbidden();
});
test('user can upload pdf attachment', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.attachments.store', $retirement), [
            'file' => UploadedFile::fake()->create('receipt.pdf', 500, 'application/pdf'),
        ])
        ->assertRedirect(route('retirement-requests.show', $retirement));

    $this->assertDatabaseHas('attachments', [
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'original_name' => 'receipt.pdf',
        'user_id' => $this->user->id,
    ]);
});
test('user can upload image attachment', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.attachments.store', $retirement), [
            'file' => UploadedFile::fake()->image('receipt.jpg'),
        ])
        ->assertRedirect(route('retirement-requests.show', $retirement));

    expect($retirement->fresh()->attachments)->toHaveCount(1);
});
test('file is required', function () {
    $retirement = draftRetirementForAttachmentsController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.attachments.store', $retirement))
        ->assertSessionHasErrors('file');
});
test('file size limit enforced', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.attachments.store', $retirement), [
            'file' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf'),
        ])
        ->assertSessionHasErrors('file');
});
test('guest cannot download', function () {
    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => draftRetirementForAttachmentsController()->id,
        'user_id' => $this->user->id,
        'path' => 'retirements/1/attachments/test.pdf',
        'original_name' => 'test.pdf',
    ]);

    $this->get(route('attachments.download', $attachment))
        ->assertRedirect(route('login'));
});
test('authenticated user can download attachment', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => "retirements/{$retirement->id}/attachments/test.pdf",
        'original_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($this->user)
        ->get(route('attachments.download', $attachment))
        ->assertOk()
        ->assertDownload('test.pdf');
});
test('guest cannot preview attachment', function () {
    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => draftRetirementForAttachmentsController()->id,
        'user_id' => $this->user->id,
        'path' => 'retirements/1/attachments/test.pdf',
        'original_name' => 'test.pdf',
    ]);

    $this->get(route('attachments.preview', $attachment))
        ->assertRedirect(route('login'));
});
test('authenticated user can preview attachment inline', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => "retirements/{$retirement->id}/attachments/test.pdf",
        'original_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($this->user)
        ->get(route('attachments.preview', $attachment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertHeader('content-disposition', 'inline; filename=test.pdf');
});
test('spreadsheet preview renders visible worksheets and excludes hidden worksheets', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    $path = "retirements/{$retirement->id}/attachments/report.xlsx";
    createSpreadsheetForAttachmentPreview($path);

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => $path,
        'original_name' => 'report.xlsx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);

    $this->actingAs($this->user)
        ->get(route('attachments.preview', $attachment))
        ->assertOk()
        ->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertHeader('content-security-policy', "sandbox; default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'")
        ->assertSee('data-spreadsheet-preview', false)
        ->assertSee(__('common.spreadsheet_preview_hint'))
        ->assertSee('Invoice Number')
        ->assertSee('INV-001')
        ->assertSee('Summary')
        ->assertSee('Visible Summary Value')
        ->assertDontSee('Dropdown Data')
        ->assertDontSee('Dropdown-only secret')
        ->assertDontSee('Internal Data')
        ->assertDontSee('Very-hidden secret');
});
test('authenticated user can preview a docx document as sandboxed html', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    $path = "retirements/{$retirement->id}/attachments/report.docx";
    createWordDocumentForAttachmentPreview($path);

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => $path,
        'original_name' => 'report.docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);

    $this->actingAs($this->user)
        ->get(route('attachments.preview', $attachment))
        ->assertOk()
        ->assertHeader('content-type', 'text/html; charset=UTF-8')
        ->assertHeader('x-content-type-options', 'nosniff')
        ->assertHeader('content-security-policy', "sandbox; default-src 'none'; img-src data:; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'")
        ->assertSee('Quarterly report')
        ->assertSee('DOCX preview content')
        ->assertSee('&lt;script&gt;alert("docx")&lt;/script&gt;', false)
        ->assertDontSee('<script>alert("docx")</script>', false);
});
test('malformed docx documents return an unprocessable response', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    $path = "retirements/{$retirement->id}/attachments/malformed.docx";
    Storage::disk('local')->put($path, 'not a valid docx archive');

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => $path,
        'original_name' => 'malformed.docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ]);

    $this->actingAs($this->user)
        ->get(route('attachments.preview', $attachment))
        ->assertUnprocessable();
});
test('legacy doc documents remain download only', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    $path = "retirements/{$retirement->id}/attachments/report.doc";
    Storage::disk('local')->put($path, 'legacy document content');

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => $path,
        'original_name' => 'report.doc',
        'mime_type' => 'application/msword',
    ]);

    $this->actingAs($this->user)
        ->get(route('attachments.preview', $attachment))
        ->assertStatus(415);
});
test('preview returns 404 when file missing from storage', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => 'retirements/99/attachments/nonexistent.pdf',
        'original_name' => 'nonexistent.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($this->user)
        ->get(route('attachments.preview', $attachment))
        ->assertNotFound();
});
test('unrelated user cannot preview attachment', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => "retirements/{$retirement->id}/attachments/test.pdf",
        'original_name' => 'test.pdf',
    ]);

    $unrelatedUser = User::factory()->create();

    $this->actingAs($unrelatedUser)
        ->get(route('attachments.preview', $attachment))
        ->assertForbidden();
});
test('download returns 404 when file missing from storage', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => 'retirements/99/attachments/nonexistent.pdf',
        'original_name' => 'nonexistent.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($this->user)
        ->get(route('attachments.download', $attachment))
        ->assertNotFound();
});
test('unrelated user cannot download', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();
    Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => "retirements/{$retirement->id}/attachments/test.pdf",
        'original_name' => 'test.pdf',
    ]);

    $unrelatedUser = User::factory()->create();

    $this->actingAs($unrelatedUser)
        ->get(route('attachments.download', $attachment))
        ->assertForbidden();
});
test('requester can download attachment they did not upload', function () {
    Storage::fake('local');
    $requester = User::factory()->create();
    $staff = Staff::factory()->withUser($requester)->create();
    $advance = PaymentRequest::factory()->advance()->create([
        'staff_id' => $staff->id,
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'draft',
    ]);
    Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => "retirements/{$retirement->id}/attachments/test.pdf",
        'original_name' => 'test.pdf',
    ]);

    $this->actingAs($requester)
        ->get(route('attachments.download', $attachment))
        ->assertOk()
        ->assertDownload('test.pdf');
});
test('user eligible to act on active stage can download', function () {
    Storage::fake('local');
    $engine = app(WorkflowEngineService::class);
    $retirement = retirementInWorkflow();

    ['template' => $template, 'role' => $role] = makeApprovalStageForAttachmentsController();
    $approver = User::factory()->create();
    $approver->assignRole($role);

    $engine->startWorkflow($retirement, $template, $this->user);

    Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');
    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => "retirements/{$retirement->id}/attachments/test.pdf",
        'original_name' => 'test.pdf',
    ]);

    $this->actingAs($approver)
        ->get(route('attachments.download', $attachment))
        ->assertOk()
        ->assertDownload('test.pdf');
});
test('user who approved earlier stage can still download', function () {
    Storage::fake('local');
    $engine = app(WorkflowEngineService::class);
    $retirement = retirementInWorkflow();

    ['template' => $template, 'role' => $role1] = makeApprovalStageForAttachmentsController(1);
    $stage2 = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 2,
    ]);
    $role2 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
    $stage2->roles()->sync([$role2->id]);

    $firstApprover = User::factory()->create();
    $firstApprover->assignRole($role1);

    $instance = $engine->startWorkflow($retirement, $template, $this->user);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
    $engine->approve($activeStage, $firstApprover);

    Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');
    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => "retirements/{$retirement->id}/attachments/test.pdf",
        'original_name' => 'test.pdf',
    ]);

    $this->actingAs($firstApprover)
        ->get(route('attachments.download', $attachment))
        ->assertOk()
        ->assertDownload('test.pdf');
});
test('user can delete attachment', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentsController();

    Storage::disk('local')->put('retirements/1/attachments/test.pdf', 'content');

    $attachment = Attachment::factory()->create([
        'attachable_type' => RetirementRequest::class,
        'attachable_id' => $retirement->id,
        'user_id' => $this->user->id,
        'path' => 'retirements/1/attachments/test.pdf',
    ]);

    $this->actingAs($this->user)
        ->delete(route('attachments.destroy', $attachment))
        ->assertRedirect();

    $this->assertSoftDeleted('attachments', ['id' => $attachment->id]);
});
