<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Position;
use Illuminate\Http\UploadedFile;

test('guest is redirected from import form', function () {
    $response = $this->get(route('positions.import'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from import submission', function () {
    $response = $this->post(route('positions.import.store'), []);

    $response->assertRedirect(route('login'));
});
test('guest is redirected from sample download', function () {
    $response = $this->get(route('positions.import.template'));

    $response->assertRedirect(route('login'));
});
test('user without create permission cannot view import form', function () {
    $this->role->revokePermissionTo(PermissionKey::CreatePosition->value);

    $this->actingAs($this->user)
        ->get(route('positions.import'))
        ->assertForbidden();
});
test('user without create permission cannot download sample', function () {
    $this->role->revokePermissionTo(PermissionKey::CreatePosition->value);

    $this->actingAs($this->user)
        ->get(route('positions.import.template'))
        ->assertForbidden();
});
test('user without create permission cannot submit import', function () {
    $this->role->revokePermissionTo(PermissionKey::CreatePosition->value);

    $this->actingAs($this->user)
        ->post(route('positions.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('positions.csv', "name\nManager\n"),
        ])
        ->assertForbidden();
});
test('authorised user can view import form', function () {
    $this->actingAs($this->user)
        ->get(route('positions.import'))
        ->assertOk();
});
test('authorised user can download import template', function () {
    $this->actingAs($this->user)
        ->get(route('positions.import.template'))
        ->assertOk()
        ->assertDownload('positions-sample.csv');
});
test('authorised user can import positions from csv', function () {
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
});
test('import requires a file', function () {
    $this->actingAs($this->user)
        ->post(route('positions.import.store'), [])
        ->assertSessionHasErrors('file');
});
test('import rejects invalid headers', function () {
    $file = UploadedFile::fake()->createWithContent('positions.csv', <<<'CSV'
position_name
Manager
CSV);

    $this->actingAs($this->user)
        ->post(route('positions.import.store'), [
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});
test('import rejects header only files', function () {
    $file = UploadedFile::fake()->createWithContent('positions.csv', <<<'CSV'
name
CSV);

    $this->actingAs($this->user)
        ->post(route('positions.import.store'), [
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});
test('import rejects duplicate names in file', function () {
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
});
test('import rejects existing positions', function () {
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
});
