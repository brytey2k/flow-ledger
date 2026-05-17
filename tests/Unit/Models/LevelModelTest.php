<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\Level;
use Tests\TenantAppTestCase;

class LevelModelTest extends TenantAppTestCase
{
    public function test_get_next_level_returns_the_next_level_by_position(): void
    {
        $first = Level::factory()->create(['position' => 1]);
        $second = Level::factory()->create(['position' => 2]);

        $this->assertEquals($second->id, $first->getNextLevel()?->id);
    }

    public function test_get_next_level_returns_null_when_no_higher_level(): void
    {
        $level = Level::factory()->create(['position' => 999]);

        $this->assertNull($level->getNextLevel());
    }

    public function test_get_previous_level_returns_the_previous_level_by_position(): void
    {
        $first = Level::factory()->create(['position' => 10]);
        $second = Level::factory()->create(['position' => 20]);

        $this->assertEquals($first->id, $second->getPreviousLevel()?->id);
    }

    public function test_get_previous_level_returns_null_when_no_lower_level(): void
    {
        $level = Level::factory()->create(['position' => 1]);

        $this->assertNull($level->getPreviousLevel());
    }

    public function test_levels_below_returns_all_levels_with_higher_position(): void
    {
        $first = Level::factory()->create(['position' => 5]);
        Level::factory()->create(['position' => 10]);
        Level::factory()->create(['position' => 15]);

        $below = $first->levelsBelow();
        $this->assertGreaterThanOrEqual(2, $below->count());
    }

    public function test_is_first_returns_true_for_lowest_position(): void
    {
        // setUp creates a level at position=1, so use 0 to guarantee lowest
        $lowest = Level::factory()->create(['position' => 0]);
        Level::factory()->create(['position' => 100]);

        $this->assertTrue($lowest->isFirst());
    }

    public function test_is_first_returns_false_for_higher_position(): void
    {
        Level::factory()->create(['position' => 1]);
        $higher = Level::factory()->create(['position' => 50]);

        $this->assertFalse($higher->isFirst());
    }

    public function test_at_position_returns_level_at_given_position(): void
    {
        $level = Level::factory()->create(['position' => 42]);

        $found = Level::atPosition(42);
        $this->assertEquals($level->id, $found?->id);
    }

    public function test_at_position_returns_null_when_no_level_at_position(): void
    {
        $this->assertNull(Level::atPosition(9999));
    }

    public function test_is_before_or_at_level_returns_true_when_same_position(): void
    {
        $level = Level::factory()->create(['position' => 5]);
        $other = Level::factory()->create(['position' => 5]);

        $this->assertTrue($level->isBeforeOrAtLevel($other));
    }

    public function test_is_before_or_at_level_returns_true_when_lower_position(): void
    {
        $lower = Level::factory()->create(['position' => 3]);
        $higher = Level::factory()->create(['position' => 7]);

        $this->assertTrue($lower->isBeforeOrAtLevel($higher));
    }

    public function test_is_before_or_at_level_returns_false_when_higher(): void
    {
        $lower = Level::factory()->create(['position' => 3]);
        $higher = Level::factory()->create(['position' => 7]);

        $this->assertFalse($higher->isBeforeOrAtLevel($lower));
    }

    public function test_is_before_level_returns_true_when_strictly_lower_position(): void
    {
        $lower = Level::factory()->create(['position' => 2]);
        $higher = Level::factory()->create(['position' => 8]);

        $this->assertTrue($lower->isBeforeLevel($higher));
    }

    public function test_is_before_level_returns_false_when_same_position(): void
    {
        $a = Level::factory()->create(['position' => 5]);
        $b = Level::factory()->create(['position' => 5]);

        $this->assertFalse($a->isBeforeLevel($b));
    }

    public function test_is_penultimate_returns_true_for_second_to_last(): void
    {
        Level::factory()->create(['position' => 10]);
        $second = Level::factory()->create(['position' => 20]);
        Level::factory()->create(['position' => 30]);

        $this->assertTrue($second->isPenultimate());
    }

    public function test_is_penultimate_returns_false_for_last_level(): void
    {
        Level::factory()->create(['position' => 10]);
        Level::factory()->create(['position' => 20]);
        $last = Level::factory()->create(['position' => 30]);

        $this->assertFalse($last->isPenultimate());
    }
}
