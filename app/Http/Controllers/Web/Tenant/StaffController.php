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
use App\Repositories\StaffRepository;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffService $service,
        private readonly StaffRepository $repository,
    ) {}

    public function index(): View
    {
        $staff = $this->repository->allWithRelations();

        return view('tenant.staff.index', compact('staff'));
    }

    public function create(): View
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $users = $this->repository->unlinkedUsers();
        $branches = Branch::orderBy('name')->get();

        return view('tenant.staff.create', compact('departments', 'positions', 'branches', 'users'));
    }

    public function store(StaffStoreRequest $request): RedirectResponse
    {
        $this->service->create($request->validated(), $request->user());

        return redirect()->route('staff.index')->with('success', 'Staff member created successfully.');
    }

    public function edit(Staff $staff): View
    {
        $departments = Department::orderBy('name')->get();
        $positions = Position::orderBy('name')->get();
        $users = $this->repository->unlinkedUsersOrCurrent($staff);
        $branches = Branch::orderBy('name')->get();

        return view('tenant.staff.edit', compact('staff', 'departments', 'positions', 'branches', 'users'));
    }

    public function update(StaffUpdateRequest $request, Staff $staff): RedirectResponse
    {
        $this->service->update($staff, $request->validated(), $request->user());

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $this->service->delete($staff, auth()->user());

        return redirect()->route('staff.index')->with('success', 'Staff member deleted.');
    }
}
