<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Department;
use App\Services\DepartmentImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->service = app(DepartmentImportService::class);
});
test('valid csv with two departments returns two', function () {
    $csv = "name\nEngineering\nMarketing\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $count = $this->service->import($file);

    expect($count)->toBe(2);
});
test('valid csv creates department records in database', function () {
    $csv = "name\nEngineering\nMarketing\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $this->service->import($file);

    expect(Department::where('name', 'Engineering')->exists())->toBeTrue();
    expect(Department::where('name', 'Marketing')->exists())->toBeTrue();
});
test('empty csv with no rows after header throws validation exception', function () {
    $csv = "name\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('wrong headers throws validation exception', function () {
    $csv = "department_name\nEngineering\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('blank row is silently skipped and rest imported', function () {
    $csv = "name\n\nMarketing\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $count = $this->service->import($file);

    expect($count)->toBe(1);
    expect(Department::where('name', 'Marketing')->exists())->toBeTrue();
});
test('duplicate name within file throws validation exception', function () {
    $csv = "name\nEngineering\nEngineering\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('name exceeding 100 characters throws validation exception', function () {
    $longName = str_repeat('A', 101);
    $csv = "name\n{$longName}\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('duplicate name already existing in database throws validation exception', function () {
    Department::factory()->create(['name' => 'Engineering']);

    $csv = "name\nEngineering\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $this->expectException(ValidationException::class);

    $this->service->import($file);
});
test('single valid department returns one', function () {
    $csv = "name\nFinance\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $count = $this->service->import($file);

    expect($count)->toBe(1);
    expect(Department::where('name', 'Finance')->exists())->toBeTrue();
});
test('name exactly 100 characters is accepted', function () {
    $name = str_repeat('A', 100);
    $csv = "name\n{$name}\n";
    $file = UploadedFile::fake()->createWithContent('departments.csv', $csv);

    $count = $this->service->import($file);

    expect($count)->toBe(1);
    expect(Department::where('name', $name)->exists())->toBeTrue();
});
