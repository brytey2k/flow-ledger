<?php

declare(strict_types=1);

namespace Tests\Feature\Departments;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;
use Illuminate\Http\UploadedFile;
use Tests\TenantAppTestCase;

class DepartmentImportTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_import_form(): void
    {
        $response = $this->get(route('departments.import'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_import_submission(): void
    {
        $response = $this->post(route('departments.import.store'), []);

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_sample_download(): void
    {
        $response = $this->get(route('departments.import.template'));

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_create_permission_cannot_view_import_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateDepartment->value);

        $this->actingAs($this->user)
            ->get(route('departments.import'))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_download_sample(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateDepartment->value);

        $this->actingAs($this->user)
            ->get(route('departments.import.template'))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_submit_import(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateDepartment->value);

        $this->actingAs($this->user)
            ->post(route('departments.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('departments.csv', "name\nFinance\n"),
            ])
            ->assertForbidden();
    }

    // ── Import form and template ──────────────────────────────────────────────

    public function test_authorised_user_can_view_import_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('departments.import'))
            ->assertOk();
    }

    public function test_authorised_user_can_download_import_template(): void
    {
        $this->actingAs($this->user)
            ->get(route('departments.import.template'))
            ->assertOk()
            ->assertDownload('departments-sample.csv');
    }

    // ── Import processing ─────────────────────────────────────────────────────

    public function test_authorised_user_can_import_departments_from_csv(): void
    {
        $file = UploadedFile::fake()->createWithContent('departments.csv', <<<'CSV'
name
Finance
Human Resources
CSV);

        $this->actingAs($this->user)
            ->post(route('departments.import.store'), [
                'file' => $file,
            ])
            ->assertRedirect(route('departments.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('departments', ['name' => 'Finance']);
        $this->assertDatabaseHas('departments', ['name' => 'Human Resources']);
    }

    public function test_import_requires_a_file(): void
    {
        $this->actingAs($this->user)
            ->post(route('departments.import.store'), [])
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_invalid_headers(): void
    {
        $file = UploadedFile::fake()->createWithContent('departments.csv', <<<'CSV'
department_name
Finance
CSV);

        $this->actingAs($this->user)
            ->post(route('departments.import.store'), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_header_only_files(): void
    {
        $file = UploadedFile::fake()->createWithContent('departments.csv', <<<'CSV'
name
CSV);

        $this->actingAs($this->user)
            ->post(route('departments.import.store'), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_duplicate_names_in_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('departments.csv', <<<'CSV'
name
Finance
Finance
CSV);

        $this->actingAs($this->user)
            ->post(route('departments.import.store'), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('rows.3');
    }

    public function test_import_rejects_existing_departments(): void
    {
        Department::factory()->create(['name' => 'Finance']);

        $file = UploadedFile::fake()->createWithContent('departments.csv', <<<'CSV'
name
Finance
CSV);

        $this->actingAs($this->user)
            ->post(route('departments.import.store'), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('rows.2');
    }
}
