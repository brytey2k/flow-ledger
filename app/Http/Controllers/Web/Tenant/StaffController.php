<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StaffStoreRequest;
use App\Http\Requests\Tenant\StaffUpdateRequest;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(): View
    {
        $staff = Staff::with(['department', 'position'])->orderBy('last_name')->orderBy('first_name')->get();

        return view('tenant.staff.index', compact('staff'));
    }

    public function create(): View
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        return view('tenant.staff.create', compact('departments', 'positions'));
    }

    public function store(StaffStoreRequest $request): RedirectResponse
    {
        Staff::create($request->validated());

        return redirect()->route('staff.index')->with('success', 'Staff member created successfully.');
    }

    public function edit(Staff $staff): View
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();

        return view('tenant.staff.edit', compact('staff', 'departments', 'positions'));
    }

    public function update(StaffUpdateRequest $request, Staff $staff): RedirectResponse
    {
        $staff->update($request->validated());

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Staff member deleted.');
    }
}
