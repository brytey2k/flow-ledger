<?php

declare(strict_types=1);

namespace Tests\Feature\Positions;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Position;
use Illuminate\Http\UploadedFile;
use Tests\TenantAppTestCase;

class PositionImportTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_import_form(): void
    {
        $response = $this->get(route('positions.import'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_import_submission(): void
    {
        $response = $this->post(route('positions.import.store'), []);

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_sample_download(): void
    {
        $response = $this->get(route('positions.import.template'));

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_create_permission_cannot_view_import_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreatePosition->value);

        $this->actingAs($this->user)
            ->get(route('positions.import'))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_download_sample(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreatePosition->value);

        $this->actingAs($this->user)
            ->get(route('positions.import.template'))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_submit_import(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreatePosition->value);

        $this->actingAs($this->user)
            ->post(route('positions.import.store'), [
                'file' => UploadedFile::fake()->createWithContent('positions.csv', "name\nManager\n"),
            ])
            ->assertForbidden();
    }

    // ── Import form and template ──────────────────────────────────────────────

    public function test_authorised_user_can_view_import_form(): void
    {
        $this->actingAs($this->user)
            ->get(route('positions.import'))
            ->assertOk();
    }

    public function test_authorised_user_can_download_import_template(): void
    {
        $this->actingAs($this->user)
            ->get(route('positions.import.template'))
            ->assertOk()
            ->assertDownload('positions-sample.csv');
    }

    // ── Import processing ─────────────────────────────────────────────────────

    public function test_authorised_user_can_import_positions_from_csv(): void
    {
        $file = UploadedFile::fake()->createWithContent('positions.csv', <<<'CSV'
name
Manager
Accountant
CSV);

        $this->actingAs($this->user)
            ->post(route('positions.import.store'), [
                'file' => $file,
            ])
            ->assertRedirect(route('positions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('positions', ['name' => 'Manager']);
        $this->assertDatabaseHas('positions', ['name' => 'Accountant']);
    }

    public function test_import_requires_a_file(): void
    {
        $this->actingAs($this->user)
            ->post(route('positions.import.store'), [])
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_invalid_headers(): void
    {
        $file = UploadedFile::fake()->createWithContent('positions.csv', <<<'CSV'
position_name
Manager
CSV);

        $this->actingAs($this->user)
            ->post(route('positions.import.store'), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_header_only_files(): void
    {
        $file = UploadedFile::fake()->createWithContent('positions.csv', <<<'CSV'
name
CSV);

        $this->actingAs($this->user)
            ->post(route('positions.import.store'), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_duplicate_names_in_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('positions.csv', <<<'CSV'
name
Manager
Manager
CSV);

        $this->actingAs($this->user)
            ->post(route('positions.import.store'), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('rows.3');
    }

    public function test_import_rejects_existing_positions(): void
    {
        Position::factory()->create(['name' => 'Manager']);

        $file = UploadedFile::fake()->createWithContent('positions.csv', <<<'CSV'
name
Manager
CSV);

        $this->actingAs($this->user)
            ->post(route('positions.import.store'), [
                'file' => $file,
            ])
            ->assertSessionHasErrors('rows.2');
    }
}
