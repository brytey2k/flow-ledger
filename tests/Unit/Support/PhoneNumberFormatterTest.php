<?php

declare(strict_types=1);
use App\Support\PhoneNumberFormatter;

test('dial code map returns array of country codes', function () {
    $map = PhoneNumberFormatter::dialCodeMap();

    expect($map)->toBeArray();
    expect($map)->not->toBeEmpty();
});
test('dial code map contains ghana', function () {
    $map = PhoneNumberFormatter::dialCodeMap();

    expect($map)->toHaveKey('GH');
    expect($map['GH'])->toBe('+233');
});
test('dial code map contains nigeria', function () {
    $map = PhoneNumberFormatter::dialCodeMap();

    expect($map)->toHaveKey('NG');
    expect($map['NG'])->toBe('+234');
});
test('dial code map contains us and canada with same code', function () {
    $map = PhoneNumberFormatter::dialCodeMap();

    expect($map)->toHaveKey('US');
    expect($map)->toHaveKey('CA');
    expect($map['US'])->toBe('+1');
    expect($map['CA'])->toBe('+1');
});
test('dial code map contains 19 countries', function () {
    $map = PhoneNumberFormatter::dialCodeMap();

    expect($map)->toHaveCount(19);
});
test('assemble returns null for empty local number', function () {
    $result = PhoneNumberFormatter::assemble('GH', '');

    expect($result)->toBeNull();
});
test('assemble returns null for null local number', function () {
    $result = PhoneNumberFormatter::assemble('GH', null);

    expect($result)->toBeNull();
});
test('assemble returns null for whitespace only local number', function () {
    $result = PhoneNumberFormatter::assemble('GH', '   ');

    expect($result)->toBeNull();
});
test('assemble returns null for non digit only local number', function () {
    $result = PhoneNumberFormatter::assemble('GH', '---');

    expect($result)->toBeNull();
});
test('assemble prepends ghana dial code', function () {
    $result = PhoneNumberFormatter::assemble('GH', '244000000');

    expect($result)->toBe('+233244000000');
});
test('assemble prepends nigeria dial code', function () {
    $result = PhoneNumberFormatter::assemble('NG', '8012345678');

    expect($result)->toBe('+2348012345678');
});
test('assemble strips leading zeros from local number', function () {
    $result = PhoneNumberFormatter::assemble('GH', '0244000000');

    expect($result)->toBe('+233244000000');
});
test('assemble strips non digit characters from local number', function () {
    $result = PhoneNumberFormatter::assemble('GH', '024-400-0000');

    expect($result)->toBe('+233244000000');
});
test('assemble returns raw digits for unknown country code', function () {
    $result = PhoneNumberFormatter::assemble('XX', '123456789');

    expect($result)->toBe('123456789');
});
test('assemble is case insensitive for country code', function () {
    $lower = PhoneNumberFormatter::assemble('gh', '244000000');
    $upper = PhoneNumberFormatter::assemble('GH', '244000000');

    expect($lower)->toBe($upper);
});
test('assemble handles null country code', function () {
    $result = PhoneNumberFormatter::assemble(null, '244000000');

    expect($result)->toBe('244000000');
});
test('assemble prepends us dial code', function () {
    $result = PhoneNumberFormatter::assemble('US', '2125551234');

    expect($result)->toBe('+12125551234');
});
test('assemble prepends kenya dial code', function () {
    $result = PhoneNumberFormatter::assemble('KE', '722000000');

    expect($result)->toBe('+254722000000');
});
test('split returns default gh for empty string', function () {
    [$country, $local] = PhoneNumberFormatter::split('');

    expect($country)->toBe('GH');
    expect($local)->toBe('');
});
test('split returns default gh for null', function () {
    [$country, $local] = PhoneNumberFormatter::split(null);

    expect($country)->toBe('GH');
    expect($local)->toBe('');
});
test('split extracts ghana from international number', function () {
    [$country, $local] = PhoneNumberFormatter::split('+233244000000');

    expect($country)->toBe('GH');
    expect($local)->toBe('244000000');
});
test('split extracts nigeria from international number', function () {
    [$country, $local] = PhoneNumberFormatter::split('+2348012345678');

    expect($country)->toBe('NG');
    expect($local)->toBe('8012345678');
});
test('split extracts kenya from international number', function () {
    [$country, $local] = PhoneNumberFormatter::split('+254722000000');

    expect($country)->toBe('KE');
    expect($local)->toBe('722000000');
});
test('split returns gh with digits for local number without plus', function () {
    [$country, $local] = PhoneNumberFormatter::split('0244000000');

    expect($country)->toBe('GH');
    expect($local)->toBe('0244000000');
});
test('split returns two element array', function () {
    $result = PhoneNumberFormatter::split('+233244000000');

    expect($result)->toHaveCount(2);
});
test('assemble and split are inverse operations', function () {
    $assembled = PhoneNumberFormatter::assemble('NG', '8012345678');
    expect($assembled)->not->toBeNull();

    [$country, $local] = PhoneNumberFormatter::split($assembled);

    expect($country)->toBe('NG');
    expect($local)->toBe('8012345678');
});
