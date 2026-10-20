<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use Illuminate\Support\Facades\Password;

test('reset password page shows the live password requirements checklist', function () {
    $token = Password::createToken($this->user);

    $this->get(route('password.reset', ['token' => $token]))
        ->assertOk()
        ->assertSee('pw-req-password', false)
        ->assertSee(__('auth.pw_req_title'))
        ->assertSee(__('auth.pw_req_length', ['count' => 12]))
        ->assertSee(__('auth.pw_req_match'));
});
test('change password page shows the live password requirements checklist', function () {
    $this->actingAs($this->user)->get(route('password.change'))
        ->assertOk()
        ->assertSee('pw-req-password', false)
        ->assertSee(__('auth.pw_req_symbol'));
});
test('the checklist requirement count matches the server side password policy', function () {
    $rule = Illuminate\Validation\Rules\Password::defaults();

    $validator = validator(['password' => 'Abcdefghij1!'], ['password' => [$rule]]);
    expect($validator->passes())->toBeTrue();

    foreach (['Abcdefghij1', 'abcdefghij1!', 'ABCDEFGHIJ1!', 'Abcdefghijk!', 'Abcdef1!'] as $weak) {
        expect(validator(['password' => $weak], ['password' => [$rule]])->fails())->toBeTrue("{$weak} should fail");
    }
});
test('the checklist resolves the confirmation field lazily because it renders after the script', function () {
    $token = Password::createToken($this->user);

    $html = $this->get(route('password.reset', ['token' => $token]))->getContent();

    expect($html)->toContain('const confirmInput = () =>')
        ->and($html)->not->toContain('const confirmInput = document.getElementById');
});
