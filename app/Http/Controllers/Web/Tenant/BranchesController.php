<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\BranchStoreRequest;
use App\Http\Requests\Tenant\BranchUpdateRequest;
use App\Models\Tenant\Branch;
use App\Repositories\BranchRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\LevelRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchesController extends Controller
{
    public function __construct(
        private readonly BranchRepository $repository,
        private readonly LevelRepository $levelRepository,
        private readonly CurrencyRepository $currencyRepository,
    ) {}

    public function index(): View
    {
        $branches = $this->repository->allWithRelations();

        return view('tenant.branches.index', compact('branches'));
    }

    public function create(): View
    {
        $levels = $this->levelRepository->allOrderedByPosition();
        $branches = $this->repository->allOrderedByName();
        $currencies = $this->currencyRepository->allOrderedByShortName();

        return view('tenant.branches.create', compact('levels', 'branches', 'currencies'));
    }

    public function store(BranchStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();

        $branch = new Branch([
            'name' => $dto->name,
            'code' => $dto->code,
            'level_id' => $dto->levelId,
            'currency_id' => $dto->currencyId,
            'position' => $dto->position,
        ]);

        if ($dto->parentId !== null) {
            $parent = $this->repository->findOrFail($dto->parentId);
            $parent->appendChild($branch);
        } else {
            $branch->save();
        }

        return redirect()->route('branches.index')->with('success', __('flash.branches.created'));
    }

    public function edit(Branch $branch): View
    {
        $levels = $this->levelRepository->allOrderedByPosition();
        $branches = $this->repository->allExcept($branch->id);
        $currencies = $this->currencyRepository->allOrderedByShortName();
        $descendantsCount = $branch->descendants()->count();

        return view('tenant.branches.edit', compact('branch', 'levels', 'branches', 'currencies', 'descendantsCount'));
    }

    public function update(BranchUpdateRequest $request, Branch $branch): RedirectResponse
    {
        $dto = $request->toDto();

        $branch->fill([
            'name' => $dto->name,
            'code' => $dto->code,
            'level_id' => $dto->levelId,
            'currency_id' => $dto->currencyId,
            'position' => $dto->position,
        ]);

        if ($dto->parentId !== (int) $branch->parent_id) {
            if ($dto->parentId !== null) {
                $parent = $this->repository->findOrFail($dto->parentId);
                $branch->moveTo(0, $parent);
            } else {
                $branch->makeRoot(0);
            }
        } else {
            $branch->save();
        }

        return redirect()->route('branches.index')->with('success', __('flash.branches.updated'));
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->staff()->exists()) {
            return back()->with('error', __('flash.branches.delete_blocked_staff'));
        }

        if ($branch->descendants()->exists()) {
            return back()->with('error', __('flash.branches.delete_blocked_children'));
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', __('flash.branches.deleted'));
    }
}
