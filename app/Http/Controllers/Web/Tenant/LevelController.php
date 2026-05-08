<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\LevelStoreRequest;
use App\Http\Requests\Tenant\LevelUpdateRequest;
use App\Models\Tenant\Level;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LevelController extends Controller
{
    public function index(): View
    {
        $levels = Level::orderBy('position')->get();

        return view('tenant.levels.index', compact('levels'));
    }

    public function create(): View
    {
        $maxPosition = Level::max('position');
        $nextPosition = (is_numeric($maxPosition) ? (int) $maxPosition : 0) + 1;

        return view('tenant.levels.create', compact('nextPosition'));
    }

    public function store(LevelStoreRequest $request): RedirectResponse
    {
        Level::create($request->validated());

        return redirect()->route('levels.index')->with('success', 'Level created successfully.');
    }

    public function edit(Level $level): View
    {
        return view('tenant.levels.edit', compact('level'));
    }

    public function update(LevelUpdateRequest $request, Level $level): RedirectResponse
    {
        $level->update($request->validated());

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
