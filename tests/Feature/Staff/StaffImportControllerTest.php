<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\Assert;

test('guest is redirected from import form', function () {
    $this->get(route('staff.import'))->assertRedirect(route('login'));
});
test('guest is redirected from import submission', function () {
    $this->post(route('staff.import.store'), [])->assertRedirect(route('login'));
});
test('user without create permission is forbidden', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateStaff->value);

    $this->actingAs($this->user)->get(route('staff.import'))->assertForbidden();
});
test('authorised user can access import form', function () {
    $this->actingAs($this->user)->get(route('staff.import'))->assertOk();
});
test('authorised user can download template', function () {
    Department::factory()->create(['name' => 'Finance']);
    Position::factory()->create(['name' => 'Manager']);

    $this->actingAs($this->user)
        ->get(route('staff.import.template'))
        ->assertOk()
        ->assertDownload('staff-import-template.xlsx');
});
test('import skips rows with missing required fields', function () {
    Department::factory()->create(['name' => 'Finance']);
    Position::factory()->create(['name' => 'Manager']);

    $file = makeSpreadsheetFileForStaffImportController([
        ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
        ['', 'Doe', 'john@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'Yes'],
        ['John', '', 'jane@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'Yes'],
    ]);

    $this->actingAs($this->user)
        ->post(route('staff.import.store'), ['file' => $file])
        ->assertRedirect(route('staff.import'))
        ->assertSessionHas('import_errors');

    $this->assertDatabaseCount('staff', 0);
});
test('import skips rows when department not found', function () {
    Position::factory()->create(['name' => 'Manager']);

    $file = makeSpreadsheetFileForStaffImportController([
        ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
        ['John', 'Doe', 'john@example.com', 'GH', '246227810', 'Unknown', 'Manager', '', 'Yes'],
    ]);

    $this->actingAs($this->user)
        ->post(route('staff.import.store'), ['file' => $file])
        ->assertRedirect(route('staff.import'))
        ->assertSessionHas('import_errors');

    $this->assertDatabaseCount('staff', 0);
});
test('import skips rows when position not found', function () {
    Department::factory()->create(['name' => 'Finance']);

    $file = makeSpreadsheetFileForStaffImportController([
        ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
        ['John', 'Doe', 'john@example.com', 'GH', '246227810', 'Finance', 'Unknown', '', 'Yes'],
    ]);

    $this->actingAs($this->user)
        ->post(route('staff.import.store'), ['file' => $file])
        ->assertRedirect(route('staff.import'))
        ->assertSessionHas('import_errors');

    $this->assertDatabaseCount('staff', 0);
});
test('import successfully imports valid rows', function () {
    Department::factory()->create(['name' => 'Finance']);
    Position::factory()->create(['name' => 'Manager']);

    $file = makeSpreadsheetFileForStaffImportController([
        ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
        ['John', 'Doe', 'john@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'No'],
        ['Jane', 'Smith', '', 'GH', '201234567', 'Finance', 'Manager', '', 'No'],
    ]);

    $this->actingAs($this->user)
        ->post(route('staff.import.store'), ['file' => $file])
        ->assertRedirect(route('staff.import'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('staff', ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com']);
    $this->assertDatabaseHas('staff', ['first_name' => 'Jane', 'last_name' => 'Smith']);
});
test('import creates user when grant login access is yes', function () {
    Department::factory()->create(['name' => 'Finance']);
    Position::factory()->create(['name' => 'Manager']);

    $file = makeSpreadsheetFileForStaffImportController([
        ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
        ['John', 'Doe', 'john.login@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'Yes'],
    ]);

    $this->actingAs($this->user)->post(route('staff.import.store'), ['file' => $file]);

    $this->assertDatabaseHas('users', ['email' => 'john.login@example.com']);
    $this->assertDatabaseHas('staff', ['email' => 'john.login@example.com']);
});
test('import does not create user when grant login access is no', function () {
    Department::factory()->create(['name' => 'Finance']);
    Position::factory()->create(['name' => 'Manager']);

    $file = makeSpreadsheetFileForStaffImportController([
        ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
        ['John', 'Doe', 'john.no.login@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'No'],
    ]);

    $this->actingAs($this->user)->post(route('staff.import.store'), ['file' => $file]);

    $this->assertDatabaseMissing('users', ['email' => 'john.no.login@example.com']);
    $this->assertDatabaseHas('staff', ['email' => 'john.no.login@example.com']);
});
test('import skips row when staff email already exists', function () {
    Department::factory()->create(['name' => 'Finance']);
    Position::factory()->create(['name' => 'Manager']);
    Staff::factory()->create(['email' => 'duplicate@example.com']);

    $file = makeSpreadsheetFileForStaffImportController([
        ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
        ['John', 'Doe', 'duplicate@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'No'],
    ]);

    $this->actingAs($this->user)
        ->post(route('staff.import.store'), ['file' => $file])
        ->assertRedirect(route('staff.import'))
        ->assertSessionHas('import_errors');
});
test('import uses branch from template when present', function () {
    Department::factory()->create(['name' => 'Finance']);
    Position::factory()->create(['name' => 'Manager']);
    $this->branch->update(['name' => 'Tema Branch']);

    $file = makeSpreadsheetFileForStaffImportController([
        ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
        ['John', 'Doe', 'branch@example.com', 'GH', '246227810', 'Finance', 'Manager', 'Tema Branch', 'No'],
    ]);

    $this->actingAs($this->user)->post(route('staff.import.store'), ['file' => $file]);

    $this->assertDatabaseHas('staff', [
        'email' => 'branch@example.com',
        'branch_id' => $this->branch->id,
    ]);
});
/**
 * @param array<int, array<int, string>> $rows
 */
function makeSpreadsheetFileForStaffImportController(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray($rows, null, 'A1');

    $path = tempnam(sys_get_temp_dir(), 'staff_import_');

    if ($path === false) {
        Assert::fail('Unable to create temporary XLSX file.');
    }

    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile(
        $path,
        'staff-import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}
