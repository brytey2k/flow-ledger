<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant\Position;
use App\Services\PositionImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TenantAppTestCase;

class PositionImportServiceTest extends TenantAppTestCase
{
    private PositionImportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PositionImportService::class);
    }

    // ── import() ─────────────────────────────────────────────────────────────

    public function test_valid_csv_with_two_positions_returns_two(): void
    {
        $csv = "name\nDeveloper\nDesigner\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $count = $this->service->import($file);

        $this->assertSame(2, $count);
    }

    public function test_valid_csv_creates_position_records_in_database(): void
    {
        $csv = "name\nDeveloper\nDesigner\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $this->service->import($file);

        $this->assertTrue(Position::where('name', 'Developer')->exists());
        $this->assertTrue(Position::where('name', 'Designer')->exists());
    }

    public function test_empty_csv_with_no_rows_after_header_throws_validation_exception(): void
    {
        $csv = "name\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_wrong_headers_throws_validation_exception(): void
    {
        $csv = "position_name\nDeveloper\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_blank_row_is_silently_skipped_and_rest_imported(): void
    {
        $csv = "name\n\nDesigner\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $count = $this->service->import($file);

        $this->assertSame(1, $count);
        $this->assertTrue(Position::where('name', 'Designer')->exists());
    }

    public function test_duplicate_name_within_file_throws_validation_exception(): void
    {
        $csv = "name\nDeveloper\nDeveloper\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_name_exceeding_100_characters_throws_validation_exception(): void
    {
        $longName = str_repeat('B', 101);
        $csv = "name\n{$longName}\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_duplicate_name_already_existing_in_database_throws_validation_exception(): void
    {
        Position::factory()->create(['name' => 'Developer']);

        $csv = "name\nDeveloper\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $this->expectException(ValidationException::class);

        $this->service->import($file);
    }

    public function test_single_valid_position_returns_one(): void
    {
        $csv = "name\nManager\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $count = $this->service->import($file);

        $this->assertSame(1, $count);
        $this->assertTrue(Position::where('name', 'Manager')->exists());
    }

    public function test_name_exactly_100_characters_is_accepted(): void
    {
        $name = str_repeat('B', 100);
        $csv = "name\n{$name}\n";
        $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

        $count = $this->service->import($file);

        $this->assertSame(1, $count);
        $this->assertTrue(Position::where('name', $name)->exists());
    }
}
