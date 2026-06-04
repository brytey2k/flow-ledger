<?php

declare(strict_types=1);

namespace Tests\Feature\Staff;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TenantAppTestCase;

class StaffImportControllerTest extends TenantAppTestCase
{
    public function test_guest_is_redirected_from_import_form(): void
    {
        $this->get(route('staff.import'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_import_submission(): void
    {
        $this->post(route('staff.import.store'), [])->assertRedirect(route('login'));
    }

    public function test_user_without_create_permission_is_forbidden(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateStaff->value);

        $this->actingAs($this->user)->get(route('staff.import'))->assertForbidden();
    }

    public function test_authorised_user_can_access_import_form(): void
    {
        $this->actingAs($this->user)->get(route('staff.import'))->assertOk();
    }

    public function test_authorised_user_can_download_template(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        Position::factory()->create(['name' => 'Manager']);

        $this->actingAs($this->user)
            ->get(route('staff.import.template'))
            ->assertOk()
            ->assertDownload('staff-import-template.xlsx');
    }

    public function test_import_skips_rows_with_missing_required_fields(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        Position::factory()->create(['name' => 'Manager']);

        $file = $this->makeSpreadsheetFile([
            ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
            ['', 'Doe', 'john@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'Yes'],
            ['John', '', 'jane@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'Yes'],
        ]);

        $this->actingAs($this->user)
            ->post(route('staff.import.store'), ['file' => $file])
            ->assertRedirect(route('staff.import'))
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('staff', 0);
    }

    public function test_import_skips_rows_when_department_not_found(): void
    {
        Position::factory()->create(['name' => 'Manager']);

        $file = $this->makeSpreadsheetFile([
            ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
            ['John', 'Doe', 'john@example.com', 'GH', '246227810', 'Unknown', 'Manager', '', 'Yes'],
        ]);

        $this->actingAs($this->user)
            ->post(route('staff.import.store'), ['file' => $file])
            ->assertRedirect(route('staff.import'))
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('staff', 0);
    }

    public function test_import_skips_rows_when_position_not_found(): void
    {
        Department::factory()->create(['name' => 'Finance']);

        $file = $this->makeSpreadsheetFile([
            ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
            ['John', 'Doe', 'john@example.com', 'GH', '246227810', 'Finance', 'Unknown', '', 'Yes'],
        ]);

        $this->actingAs($this->user)
            ->post(route('staff.import.store'), ['file' => $file])
            ->assertRedirect(route('staff.import'))
            ->assertSessionHas('import_errors');

        $this->assertDatabaseCount('staff', 0);
    }

    public function test_import_successfully_imports_valid_rows(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        Position::factory()->create(['name' => 'Manager']);

        $file = $this->makeSpreadsheetFile([
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
    }

    public function test_import_creates_user_when_grant_login_access_is_yes(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        Position::factory()->create(['name' => 'Manager']);

        $file = $this->makeSpreadsheetFile([
            ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
            ['John', 'Doe', 'john.login@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'Yes'],
        ]);

        $this->actingAs($this->user)->post(route('staff.import.store'), ['file' => $file]);

        $this->assertDatabaseHas('users', ['email' => 'john.login@example.com']);
        $this->assertDatabaseHas('staff', ['email' => 'john.login@example.com']);
    }

    public function test_import_does_not_create_user_when_grant_login_access_is_no(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        Position::factory()->create(['name' => 'Manager']);

        $file = $this->makeSpreadsheetFile([
            ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
            ['John', 'Doe', 'john.no.login@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'No'],
        ]);

        $this->actingAs($this->user)->post(route('staff.import.store'), ['file' => $file]);

        $this->assertDatabaseMissing('users', ['email' => 'john.no.login@example.com']);
        $this->assertDatabaseHas('staff', ['email' => 'john.no.login@example.com']);
    }

    public function test_import_skips_row_when_staff_email_already_exists(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        Position::factory()->create(['name' => 'Manager']);
        Staff::factory()->create(['email' => 'duplicate@example.com']);

        $file = $this->makeSpreadsheetFile([
            ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
            ['John', 'Doe', 'duplicate@example.com', 'GH', '246227810', 'Finance', 'Manager', '', 'No'],
        ]);

        $this->actingAs($this->user)
            ->post(route('staff.import.store'), ['file' => $file])
            ->assertRedirect(route('staff.import'))
            ->assertSessionHas('import_errors');
    }

    public function test_import_uses_branch_from_template_when_present(): void
    {
        Department::factory()->create(['name' => 'Finance']);
        Position::factory()->create(['name' => 'Manager']);
        $this->branch->update(['name' => 'Tema Branch']);

        $file = $this->makeSpreadsheetFile([
            ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'],
            ['John', 'Doe', 'branch@example.com', 'GH', '246227810', 'Finance', 'Manager', 'Tema Branch', 'No'],
        ]);

        $this->actingAs($this->user)->post(route('staff.import.store'), ['file' => $file]);

        $this->assertDatabaseHas('staff', [
            'email' => 'branch@example.com',
            'branch_id' => $this->branch->id,
        ]);
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function makeSpreadsheetFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($rows, null, 'A1');

        $path = tempnam(sys_get_temp_dir(), 'staff_import_');

        if ($path === false) {
            self::fail('Unable to create temporary XLSX file.');
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
}
