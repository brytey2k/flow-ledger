<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PositionImportRequest;
use App\Http\Requests\Tenant\PositionStoreRequest;
use App\Http\Requests\Tenant\PositionUpdateRequest;
use App\Models\Tenant\Position;
use App\Repositories\PositionRepository;
use App\Services\PositionImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PositionsController extends Controller
{
    public function __construct(
        private readonly PositionRepository $repository,
        private readonly PositionImportService $importService,
    ) {}

    public function index(): View
    {
        $positions = $this->repository->allOrderedByName();

        return view('tenant.positions.index', compact('positions'));
    }

    public function create(): View
    {
        return view('tenant.positions.create');
    }

    public function importForm(): View
    {
        return view('tenant.positions.import');
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        return Response::streamDownload(function (): void {
            $output = fopen('php://output', 'wb');

            fputcsv($output, ['name']);
            fputcsv($output, ['Manager']);
            fputcsv($output, ['Accountant']);
            fputcsv($output, ['Operations Lead']);

            fclose($output);
        }, 'positions-sample.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(PositionStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        Position::create(['name' => $dto->name]);

        return redirect()->route('positions.index')->with('success', __('flash.positions.created'));
    }

    public function import(PositionImportRequest $request): RedirectResponse
    {
        $importedCount = $this->importService->import($request->file('file'));

        return redirect()->route('positions.index')
            ->with('success', trans_choice('flash.positions.imported', $importedCount, ['count' => $importedCount]));
    }

    public function edit(Position $position): View
    {
        return view('tenant.positions.edit', compact('position'));
    }

    public function update(PositionUpdateRequest $request, Position $position): RedirectResponse
    {
        $dto = $request->toDto();
        $position->update(['name' => $dto->name]);

        return redirect()->route('positions.index')->with('success', __('flash.positions.updated'));
    }

    public function destroy(Position $position): RedirectResponse
    {
        if ($position->staff()->exists()) {
            return back()->with('error', __('flash.positions.delete_blocked_staff'));
        }

        $position->delete();

        return redirect()->route('positions.index')->with('success', __('flash.positions.deleted'));
    }
}
