<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StaffStoreRequest;
use App\Http\Requests\Tenant\StaffUpdateRequest;
use App\Models\Tenant\Staff;
use App\Repositories\BranchRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Repositories\StaffRepository;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffService $service,
        private readonly StaffRepository $repository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly PositionRepository $positionRepository,
        private readonly BranchRepository $branchRepository,
    ) {}

    public function index(): View
    {
        $staff = $this->repository->allWithRelations();

        return view('tenant.staff.index', compact('staff'));
    }

    public function create(): View
    {
        $departments = $this->departmentRepository->allOrderedByName();
        $positions = $this->positionRepository->allOrderedByName();
        $users = $this->repository->unlinkedUsers();
        $branches = $this->branchRepository->allOrderedByName();

        return view('tenant.staff.create', compact('departments', 'positions', 'branches', 'users'));
    }

    public function store(StaffStoreRequest $request): RedirectResponse
    {
        $this->service->create($request->toDto(), $request->user());

        return redirect()->route('staff.index')->with('success', 'Staff member created successfully.');
    }

    public function edit(Staff $staff): View
    {
        $departments = $this->departmentRepository->allOrderedByName();
        $positions = $this->positionRepository->allOrderedByName();
        $users = $this->repository->unlinkedUsersOrCurrent($staff);
        $branches = $this->branchRepository->allOrderedByName();

        return view('tenant.staff.edit', compact('staff', 'departments', 'positions', 'branches', 'users'));
    }

    public function update(StaffUpdateRequest $request, Staff $staff): RedirectResponse
    {
        $this->service->update($staff, $request->toDto(), $request->user());

        return redirect()->route('staff.index')->with('success', 'Staff member updated successfully.');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $this->service->delete($staff, auth()->user());

        return redirect()->route('staff.index')->with('success', 'Staff member deleted.');
    }
}
