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
use App\Repositories\RoleRepository;
use App\Repositories\StaffRepository;
use App\Services\BranchScopeService;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function __construct(
        private readonly StaffService $service,
        private readonly StaffRepository $repository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly PositionRepository $positionRepository,
        private readonly BranchRepository $branchRepository,
        private readonly RoleRepository $roleRepository,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $staff = $this->repository->allWithRelations($this->branchScope->allowedBranchIds($user));

        return view('tenant.staff.index', compact('staff'));
    }

    public function create(): View
    {
        $departments = $this->departmentRepository->allOrderedByName();
        $positions = $this->positionRepository->allOrderedByName();
        $branches = $this->branchRepository->allOrderedByName();
        $roles = $this->roleRepository->allOrderedByName();
        $unlinkedUsers = $this->repository->unlinkedUsers();

        return view('tenant.staff.create', compact('departments', 'positions', 'branches', 'roles', 'unlinkedUsers'));
    }

    public function store(StaffStoreRequest $request): RedirectResponse
    {
        $this->service->create($request->toDto(), $request->user());

        return redirect()->route('staff.index')->with('success', __('flash.staff.created'));
    }

    public function edit(Staff $staff): View
    {
        $departments = $this->departmentRepository->allOrderedByName();
        $positions = $this->positionRepository->allOrderedByName();
        $branches = $this->branchRepository->allOrderedByName();
        $roles = $this->roleRepository->allOrderedByName();
        $unlinkedUsers = $staff->user_id === null ? $this->repository->unlinkedUsers() : collect();

        return view('tenant.staff.edit', compact('staff', 'departments', 'positions', 'branches', 'roles', 'unlinkedUsers'));
    }

    public function show(Staff $staff): View
    {
        $staff->load('user');

        return view('tenant.staff.show', compact('staff'));
    }

    public function update(StaffUpdateRequest $request, Staff $staff): RedirectResponse
    {
        $this->service->update($staff, $request->toDto(), $request->user());

        return redirect()->route('staff.index')->with('success', __('flash.staff.updated'));
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $this->service->delete($staff, auth()->user());

        return redirect()->route('staff.index')->with('success', __('flash.staff.deleted'));
    }
}
