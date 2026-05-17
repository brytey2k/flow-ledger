<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CostCodeStoreRequest;
use App\Http\Requests\Tenant\CostCodeUpdateRequest;
use App\Models\Tenant\CostCode;
use App\Repositories\CostCodeRepository;
use App\Repositories\DepartmentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CostCodesController extends Controller
{
    public function __construct(
        private readonly CostCodeRepository $repository,
        private readonly DepartmentRepository $departmentRepository,
    ) {}

    public function index(): View
    {
        $costCodes = $this->repository->allWithDepartment();

        return view('tenant.cost-codes.index', compact('costCodes'));
    }

    public function create(): View
    {
        $departments = $this->departmentRepository->allOrderedByName();

        return view('tenant.cost-codes.create', compact('departments'));
    }

    public function store(CostCodeStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        CostCode::create([
            'code' => $dto->code,
            'name' => $dto->name,
            'department_id' => $dto->departmentId,
        ]);

        return redirect()->route('cost-codes.index')->with('success', __('flash.cost_codes.created'));
    }

    public function edit(CostCode $costCode): View
    {
        $departments = $this->departmentRepository->allOrderedByName();

        return view('tenant.cost-codes.edit', compact('costCode', 'departments'));
    }

    public function update(CostCodeUpdateRequest $request, CostCode $costCode): RedirectResponse
    {
        $dto = $request->toDto();
        $costCode->update([
            'code' => $dto->code,
            'name' => $dto->name,
            'department_id' => $dto->departmentId,
        ]);

        return redirect()->route('cost-codes.index')->with('success', __('flash.cost_codes.updated'));
    }

    public function destroy(CostCode $costCode): RedirectResponse
    {
        $costCode->delete();

        return redirect()->route('cost-codes.index')->with('success', __('flash.cost_codes.deleted'));
    }
}
