<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StaffStoreRequest;
use App\Http\Requests\Tenant\StaffUpdateRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
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
        $branches = Branch::orderBy('name')->get();
        $users = User::whereDoesntHave('staffProfile')->orderBy('first_name')->get();

        return view('tenant.staff.create', compact('departments', 'positions', 'branches', 'users'));
    }

    public function store(StaffStoreRequest $request): RedirectResponse
    {
        $staff = Staff::create($request->validated());

        activity()
            ->performedOn($staff)
            ->causedBy($request->user())
            ->event('staff.created')
            ->withProperties(['name' => $staff->full_name, 'email' => $staff->email])
            ->log('Staff member created');

        return redirect()->route('staff.index')->with('success', 'Staff member created successfully.');
    }

    public function edit(Staff $staff): View
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        // Users not linked to any staff, plus the current one if already linked
        $users = User::where(function ($q) use ($staff): void {
            $q->whereDoesntHave('staffProfile')->orWhere('id', $staff->user_id);
        })->orderBy('first_name')->get();

        return view('tenant.staff.edit', compact('staff', 'departments', 'positions', 'branches', 'users'));
    }

    public function update(StaffUpdateRequest $request, Staff $staff): RedirectResponse
    {
        $staff->update($request->validated());

        activity()
            ->performedOn($staff)
            ->causedBy($request->user())
            ->event('staff.updated')
            ->withProperties(['name' => $staff->full_name, 'email' => $staff->email])
            ->log('Staff member updated');

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        activity()
            ->performedOn($staff)
            ->causedBy(auth()->user())
            ->event('staff.deleted')
            ->withProperties(['name' => $staff->full_name, 'email' => $staff->email])
            ->log('Staff member deleted');

        $staff->delete();

        return redirect()->route('staff.index')->with('success', 'Staff member deleted.');
    }
}
