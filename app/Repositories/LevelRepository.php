<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Level;
use Illuminate\Database\Eloquent\Collection;

class LevelRepository
{
    /**
     * @param array{q?: string} $filters
     *
     * @return Collection<int, Level>
     */
    public function allOrderedByPosition(array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;

        return Level::query()
            ->when($search, fn($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function nextPosition(): int
    {
        $max = Level::max('position');

        return (is_numeric($max) ? (int) $max : 0) + 1;
    }
}
