<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Position;
use App\Services\PositionImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(PositionImportService::class);
});
test('valid csv with two positions returns two', function () {
    $csv = "name\nDeveloper\nDesigner\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $count = $this->service->import($file);

    expect($count)->toBe(2);
});
test('valid csv creates position records in database', function () {
    $csv = "name\nDeveloper\nDesigner\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $this->service->import($file);

    expect(Position::where('name', 'Developer')->exists())->toBeTrue();
    expect(Position::where('name', 'Designer')->exists())->toBeTrue();
});
test('empty csv with no rows after header throws validation exception', function () {
    $csv = "name\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('wrong headers throws validation exception', function () {
    $csv = "position_name\nDeveloper\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('blank row is silently skipped and rest imported', function () {
    $csv = "name\n\nDesigner\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $count = $this->service->import($file);

    expect($count)->toBe(1);
    expect(Position::where('name', 'Designer')->exists())->toBeTrue();
});
test('duplicate name within file throws validation exception', function () {
    $csv = "name\nDeveloper\nDeveloper\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('name exceeding 100 characters throws validation exception', function () {
    $longName = str_repeat('B', 101);
    $csv = "name\n{$longName}\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('duplicate name already existing in database throws validation exception', function () {
    Position::factory()->create(['name' => 'Developer']);

    $csv = "name\nDeveloper\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('single valid position returns one', function () {
    $csv = "name\nManager\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $count = $this->service->import($file);

    expect($count)->toBe(1);
    expect(Position::where('name', 'Manager')->exists())->toBeTrue();
});
test('name exactly 100 characters is accepted', function () {
    $name = str_repeat('B', 100);
    $csv = "name\n{$name}\n";
    $file = UploadedFile::fake()->createWithContent('positions.csv', $csv);

    $count = $this->service->import($file);

    expect($count)->toBe(1);
    expect(Position::where('name', $name)->exists())->toBeTrue();
});
