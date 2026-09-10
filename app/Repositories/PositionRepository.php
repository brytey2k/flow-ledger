<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Position;
use Illuminate\Database\Eloquent\Collection;

class PositionRepository
{
    /**
     * @param array{q?: string} $filters
     *
     * @return Collection<int, Position>
     */
    public function allOrderedByName(array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;

        return Position::query()
            ->when($search, fn($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }
}
