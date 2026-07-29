<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Level;

test('get next level returns the next level by position', function () {
    $first = Level::factory()->create(['position' => 1]);
    $second = Level::factory()->create(['position' => 2]);

    expect($first->getNextLevel()?->id)->toEqual($second->id);
});
test('get next level returns null when no higher level', function () {
    $level = Level::factory()->create(['position' => 999]);

    expect($level->getNextLevel())->toBeNull();
});
test('get previous level returns the previous level by position', function () {
    $first = Level::factory()->create(['position' => 10]);
    $second = Level::factory()->create(['position' => 20]);

    expect($second->getPreviousLevel()?->id)->toEqual($first->id);
});
test('get previous level returns null when no lower level', function () {
    $level = Level::factory()->create(['position' => 1]);

    expect($level->getPreviousLevel())->toBeNull();
});
test('levels below returns all levels with higher position', function () {
    $first = Level::factory()->create(['position' => 5]);
    Level::factory()->create(['position' => 10]);
    Level::factory()->create(['position' => 15]);

    $below = $first->levelsBelow();
    expect($below->count())->toBeGreaterThanOrEqual(2);
});
test('is first returns true for lowest position', function () {
    // setUp creates a level at position=1, so use 0 to guarantee lowest
    $lowest = Level::factory()->create(['position' => 0]);
    Level::factory()->create(['position' => 100]);

    expect($lowest->isFirst())->toBeTrue();
});
test('is first returns false for higher position', function () {
    Level::factory()->create(['position' => 1]);
    $higher = Level::factory()->create(['position' => 50]);

    expect($higher->isFirst())->toBeFalse();
});
test('at position returns level at given position', function () {
    $level = Level::factory()->create(['position' => 42]);

    $found = Level::atPosition(42);
    expect($found?->id)->toEqual($level->id);
});
test('at position returns null when no level at position', function () {
    expect(Level::atPosition(9999))->toBeNull();
});
test('is before or at level returns true when same position', function () {
    $level = Level::factory()->create(['position' => 5]);
    $other = Level::factory()->create(['position' => 5]);

    expect($level->isBeforeOrAtLevel($other))->toBeTrue();
});
test('is before or at level returns true when lower position', function () {
    $lower = Level::factory()->create(['position' => 3]);
    $higher = Level::factory()->create(['position' => 7]);

    expect($lower->isBeforeOrAtLevel($higher))->toBeTrue();
});
test('is before or at level returns false when higher', function () {
    $lower = Level::factory()->create(['position' => 3]);
    $higher = Level::factory()->create(['position' => 7]);

    expect($higher->isBeforeOrAtLevel($lower))->toBeFalse();
});
test('is before level returns true when strictly lower position', function () {
    $lower = Level::factory()->create(['position' => 2]);
    $higher = Level::factory()->create(['position' => 8]);

    expect($lower->isBeforeLevel($higher))->toBeTrue();
});
test('is before level returns false when same position', function () {
    $a = Level::factory()->create(['position' => 5]);
    $b = Level::factory()->create(['position' => 5]);

    expect($a->isBeforeLevel($b))->toBeFalse();
});
test('is penultimate returns true for second to last', function () {
    Level::factory()->create(['position' => 10]);
    $second = Level::factory()->create(['position' => 20]);
    Level::factory()->create(['position' => 30]);

    expect($second->isPenultimate())->toBeTrue();
});
test('is penultimate returns false for last level', function () {
    Level::factory()->create(['position' => 10]);
    Level::factory()->create(['position' => 20]);
    $last = Level::factory()->create(['position' => 30]);

    expect($last->isPenultimate())->toBeFalse();
});
