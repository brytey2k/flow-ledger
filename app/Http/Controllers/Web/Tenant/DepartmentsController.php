<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DepartmentImportRequest;
use App\Http\Requests\Tenant\DepartmentStoreRequest;
use App\Http\Requests\Tenant\DepartmentUpdateRequest;
use App\Models\Tenant\Department;
use App\Repositories\DepartmentRepository;
use App\Services\DepartmentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentsController extends Controller
{
    public function __construct(
        private readonly DepartmentRepository $repository,
        private readonly DepartmentImportService $importService,
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

    public function importForm(): View
    {
        return view('tenant.departments.import');
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        return Response::streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            fputcsv($output, ['name']);
            fputcsv($output, ['Finance']);
            fputcsv($output, ['Human Resources']);
            fputcsv($output, ['Operations']);

            fclose($output);
        }, 'departments-sample.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
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

    public function import(DepartmentImportRequest $request): RedirectResponse
    {
        $importedCount = $this->importService->import($request->file('file'));

        return redirect()->route('departments.index')
            ->with('success', trans_choice('flash.departments.imported', $importedCount, ['count' => $importedCount]));
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')->with('success', __('flash.departments.deleted'));
    }
}
