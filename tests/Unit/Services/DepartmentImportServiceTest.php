<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant\Department;
use App\Services\DepartmentImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TenantAppTestCase;

class DepartmentImportServiceTest extends TenantAppTestCase
{
    private DepartmentImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DepartmentImportService::class);
    }

    // ── import() ─────────────────────────────────────────────────────────────

    public function test_valid_csv_with_two_departments_returns_two(): void
    {
        $csv = "name\nEngineering\nMarketing\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $count = $this->service->import($file);

        $this->assertSame(2, $count);
    }

    public function test_valid_csv_creates_department_records_in_database(): void
    {
        $csv = "name\nEngineering\nMarketing\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $this->service->import($file);

        $this->assertTrue(Department::where('name', 'Engineering')->exists());
        $this->assertTrue(Department::where('name', 'Marketing')->exists());
    }

    public function test_empty_csv_with_no_rows_after_header_throws_validation_exception(): void
    {
        $csv = "name\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_wrong_headers_throws_validation_exception(): void
    {
        $csv = "department_name\nEngineering\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_blank_row_is_silently_skipped_and_rest_imported(): void
    {
        $csv = "name\n\nMarketing\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $count = $this->service->import($file);

        $this->assertSame(1, $count);
        $this->assertTrue(Department::where('name', 'Marketing')->exists());
    }

    public function test_duplicate_name_within_file_throws_validation_exception(): void
    {
        $csv = "name\nEngineering\nEngineering\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_name_exceeding_100_characters_throws_validation_exception(): void
    {
        $longName = str_repeat('A', 101);
        $csv = "name\n{$longName}\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_duplicate_name_already_existing_in_database_throws_validation_exception(): void
    {
        Department::factory()->create(['name' => 'Engineering']);

        $csv = "name\nEngineering\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_single_valid_department_returns_one(): void
    {
        $csv = "name\nFinance\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $count = $this->service->import($file);

        $this->assertSame(1, $count);
        $this->assertTrue(Department::where('name', 'Finance')->exists());
    }

    public function test_name_exactly_100_characters_is_accepted(): void
    {
        $name = str_repeat('A', 100);
        $csv = "name\n{$name}\n";
        $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

        $count = $this->service->import($file);

        $this->assertSame(1, $count);
        $this->assertTrue(Department::where('name', $name)->exists());
    }
}
