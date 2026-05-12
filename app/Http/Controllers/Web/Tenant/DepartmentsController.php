<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DepartmentStoreRequest;
use App\Http\Requests\Tenant\DepartmentUpdateRequest;
use App\Models\Tenant\Department;
use App\Repositories\DepartmentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentsController extends Controller
{
    public function __construct(
        private readonly DepartmentRepository $repository,
    ) {}

    public function index(): View
    {
        $departments = $this->repository->allOrderedByName();

        return view('tenant.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('tenant.departments.create');
    }

    public function store(DepartmentStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        Department::create(['name' => $dto->name]);

        return redirect()->route('departments.index')->with('success', __('flash.departments.created'));
    }

    public function edit(Department $department): View
    {
        return view('tenant.departments.edit', compact('department'));
    }

    public function update(DepartmentUpdateRequest $request, Department $department): RedirectResponse
    {
        $dto = $request->toDto();
        $department->update(['name' => $dto->name]);

        return redirect()->route('departments.index')->with('success', __('flash.departments.updated'));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', __('flash.departments.deleted'));
    }
}
