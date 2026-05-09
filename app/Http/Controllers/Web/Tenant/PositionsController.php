<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PositionStoreRequest;
use App\Http\Requests\Tenant\PositionUpdateRequest;
use App\Models\Tenant\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PositionsController extends Controller
{
    public function index(): View
    {
        $positions = Position::orderBy('name')->get();

        return view('tenant.positions.index', compact('positions'));
    }

    public function create(): View
    {
        return view('tenant.positions.create');
    }

    public function store(PositionStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        Position::create(['name' => $dto->name]);

        return redirect()->route('positions.index')->with('success', 'Position created successfully.');
    }

    public function edit(Position $position): View
    {
        return view('tenant.positions.edit', compact('position'));
    }

    public function update(PositionUpdateRequest $request, Position $position): RedirectResponse
    {
        $dto = $request->toDto();
        $position->update(['name' => $dto->name]);

        return redirect()->route('positions.index')->with('success', 'Position updated successfully.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->staff()->exists()) {
            return back()->with('error', 'Cannot delete a position that has staff assigned to it.');
        }

        $position->delete();

        return redirect()->route('positions.index')->with('success', 'Position deleted.');
    }
}
