<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AccountCodeStoreRequest;
use App\Http\Requests\Tenant\AccountCodeUpdateRequest;
use App\Models\Tenant\AccountCode;
use App\Models\Tenant\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountCodesController extends Controller
{
    public function index(): View
    {
        $accountCodes = AccountCode::with('department')->orderBy('code')->get();

        return view('tenant.account-codes.index', compact('accountCodes'));
    }

    public function create(): View
    {
        $departments = Department::orderBy('name')->get();

        return view('tenant.account-codes.create', compact('departments'));
    }

    public function store(AccountCodeStoreRequest $request): RedirectResponse
    {
        AccountCode::create($request->validated());

        return redirect()->route('account-codes.index')->with('success', 'Account code created successfully.');
    }

    public function edit(AccountCode $accountCode): View
    {
        $departments = Department::orderBy('name')->get();

        return view('tenant.account-codes.edit', compact('accountCode', 'departments'));
    }

    public function update(AccountCodeUpdateRequest $request, AccountCode $accountCode): RedirectResponse
    {
        $accountCode->update($request->validated());

        return redirect()->route('account-codes.index')->with('success', 'Account code updated successfully.');
    }

    public function destroy(AccountCode $accountCode): RedirectResponse
    {
        $accountCode->delete();

        return redirect()->route('account-codes.index')->with('success', 'Account code deleted.');
    }
}
