<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\LevelStoreRequest;
use App\Http\Requests\Tenant\LevelUpdateRequest;
use App\Models\Tenant\Level;
use App\Repositories\LevelRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LevelController extends Controller
{
    public function __construct(
        private readonly LevelRepository $repository,
    ) {}

    public function index(): View
    {
        $levels = $this->repository->allOrderedByPosition();

        return view('tenant.levels.index', compact('levels'));
    }

    public function create(): View
    {
        $nextPosition = $this->repository->nextPosition();

        return view('tenant.levels.create', compact('nextPosition'));
    }

    public function store(LevelStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        Level::create(['name' => $dto->name, 'position' => $dto->position]);

        return redirect()->route('levels.index')->with('success', 'Level created successfully.');
    }

    public function edit(Level $level): View
    {
        return view('tenant.levels.edit', compact('level'));
    }

    public function update(LevelUpdateRequest $request, Level $level): RedirectResponse
    {
        $dto = $request->toDto();
        $level->update(['name' => $dto->name, 'position' => $dto->position]);

        return redirect()->route('levels.index')->with('success', 'Level updated successfully.');
    }

    public function destroy(Level $level): RedirectResponse
    {
        if ($level->branches()->exists()) {
            return back()->with('error', 'Cannot delete a level that has branches assigned to it.');
        }

        $level->delete();

        return redirect()->route('levels.index')->with('success', 'Level deleted.');
    }
}
