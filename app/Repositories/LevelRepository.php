<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Level;
use Illuminate\Database\Eloquent\Collection;

class LevelRepository
{
    /** @return Collection<int, Level> */
    public function allOrderedByPosition(): Collection
    {
        return Level::orderBy('position')->get();
    }

    public function nextPosition(): int
    {
        $max = Level::max('position');

        return (is_numeric($max) ? (int) $max : 0) + 1;
    }
}
