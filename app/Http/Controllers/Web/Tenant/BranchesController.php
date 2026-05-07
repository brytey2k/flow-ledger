<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchStoreRequest;
use App\Http\Requests\Tenant\BranchUpdateRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Level;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchesController extends Controller
{
    public function index(): View
    {
        $branches = Branch::with(['level', 'parent'])->orderBy('position')->get();

        return view('tenant.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $levels = Level::orderBy('position')->get();
        $branches = Branch::orderBy('name')->get();
        $currencies = Currency::orderBy('short_name')->get();

        return view('tenant.branches.create', compact('levels', 'branches', 'currencies'));
    }

    public function store(BranchStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $parentId = $data['parent_id'] ?? null;

        $branch = new Branch($data);

        if ($parentId !== null) {
            $parent = Branch::findOrFail($parentId);
            $parent->appendNode($branch);
        } else {
            $branch->save();
        }

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch): View
    {
        $levels = Level::orderBy('position')->get();
        $branches = Branch::where('id', '!=', $branch->id)->orderBy('name')->get();
        $currencies = Currency::orderBy('short_name')->get();
        $descendantsCount = $branch->descendants()->count();

        return view('tenant.branches.edit', compact('branch', 'levels', 'branches', 'currencies', 'descendantsCount'));
    }

    public function update(BranchUpdateRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();
        $newParentId = $data['parent_id'] ?? null;

        $branch->fill($data);

        if ((int) $newParentId !== (int) $branch->parent_id) {
            if ($newParentId !== null) {
                $parent = Branch::findOrFail($newParentId);
                $branch->moveTo($parent);
            } else {
                $branch->makeRoot();
            }
        } else {
            $branch->save();
        }

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->users()->exists()) {
            return back()->with('error', 'Cannot delete a branch that has users assigned to it.');
        }

        if ($branch->descendants()->exists()) {
            return back()->with('error', 'Cannot delete a branch that has child branches.');
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Branch deleted.');
    }
}
