<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DepartmentStoreRequest;
use App\Http\Requests\Tenant\DepartmentUpdateRequest;
use App\Models\Tenant\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentsController extends Controller
{
    public function index(): View
    {
        $departments = Department::orderBy('name')->get();

        return view('tenant.departments.index', compact('departments'));
    }

    public function create(): View
    {
        return view('tenant.departments.create');
    }

    public function store(DepartmentStoreRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()->route('departments.index')->with('success', 'Department created successfully.');
    }

    public function edit(Department $department): View
    {
        return view('tenant.departments.edit', compact('department'));
    }

    public function update(DepartmentUpdateRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('departments.index')->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department deleted.');
    }
}
