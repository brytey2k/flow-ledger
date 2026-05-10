<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Position;
use Illuminate\Database\Eloquent\Collection;

class PositionRepository
{
    /** @return Collection<int, Position> */
    public function allOrderedByName(): Collection
    {
        return Position::orderBy('name')->get();
    }
}
