<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AccountCodeStoreRequest;
use App\Http\Requests\Tenant\AccountCodeUpdateRequest;
use App\Models\Tenant\AccountCode;
use App\Repositories\AccountCodeRepository;
use App\Repositories\DepartmentRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountCodesController extends Controller
{
    public function __construct(
        private readonly AccountCodeRepository $repository,
        private readonly DepartmentRepository $departmentRepository,
    ) {}

    public function index(): View
    {
        $accountCodes = $this->repository->allWithDepartment();

        return view('tenant.account-codes.index', compact('accountCodes'));
    }

    public function create(): View
    {
        $departments = $this->departmentRepository->allOrderedByName();

        return view('tenant.account-codes.create', compact('departments'));
    }

    public function store(AccountCodeStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        AccountCode::create([
            'code' => $dto->code,
            'name' => $dto->name,
            'department_id' => $dto->departmentId,
        ]);

        return redirect()->route('account-codes.index')->with('success', 'Account code created successfully.');
    }

    public function edit(AccountCode $accountCode): View
    {
        $departments = $this->departmentRepository->allOrderedByName();

        return view('tenant.account-codes.edit', compact('accountCode', 'departments'));
    }

    public function update(AccountCodeUpdateRequest $request, AccountCode $accountCode): RedirectResponse
    {
        $dto = $request->toDto();
        $accountCode->update([
            'code' => $dto->code,
            'name' => $dto->name,
            'department_id' => $dto->departmentId,
        ]);

        return redirect()->route('account-codes.index')->with('success', 'Account code updated successfully.');
    }

    public function destroy(AccountCode $accountCode): RedirectResponse
    {
        $accountCode->delete();

        return redirect()->route('account-codes.index')->with('success', 'Account code deleted.');
    }
}
