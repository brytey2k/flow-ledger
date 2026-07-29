<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;
use Illuminate\Http\UploadedFile;

test('guest is redirected from import form', function () {
    $response = $this->get(route('departments.import'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from import submission', function () {
    $response = $this->post(route('departments.import.store'), []);

    $response->assertRedirect(route('login'));
});
test('guest is redirected from sample download', function () {
    $response = $this->get(route('departments.import.template'));

    $response->assertRedirect(route('login'));
});
test('user without create permission cannot view import form', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateDepartment->value);

    $this->actingAs($this->user)
        ->get(route('departments.import'))
        ->assertForbidden();
});
test('user without create permission cannot download sample', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateDepartment->value);

    $this->actingAs($this->user)
        ->get(route('departments.import.template'))
        ->assertForbidden();
});
test('user without create permission cannot submit import', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateDepartment->value);

    $this->actingAs($this->user)
        ->post(route('departments.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('departments.csv', "name\nFinance\n"),
        ])
        ->assertForbidden();
});
test('authorised user can view import form', function () {
    $this->actingAs($this->user)
        ->get(route('departments.import'))
        ->assertOk();
});
test('authorised user can download import template', function () {
    $this->actingAs($this->user)
        ->get(route('departments.import.template'))
        ->assertOk()
        ->assertDownload('departments-sample.csv');
});
test('authorised user can import departments from csv', function () {
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
});
test('import requires a file', function () {
    $this->actingAs($this->user)
        ->post(route('departments.import.store'), [])
        ->assertSessionHasErrors('file');
});
test('import rejects invalid headers', function () {
    $file = UploadedFile::fake()->createWithContent('departments.csv', <<<'CSV'
department_name
Finance
CSV);

    $this->actingAs($this->user)
        ->post(route('departments.import.store'), [
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});
test('import rejects header only files', function () {
    $file = UploadedFile::fake()->createWithContent('departments.csv', <<<'CSV'
name
CSV);

    $this->actingAs($this->user)
        ->post(route('departments.import.store'), [
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});
test('import rejects duplicate names in file', function () {
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
});
test('import rejects existing departments', function () {
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
});
