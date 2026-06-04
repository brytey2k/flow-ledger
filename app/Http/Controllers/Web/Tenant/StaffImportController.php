<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StaffImportRequest;
use App\Repositories\BranchRepository;
use App\Repositories\DepartmentRepository;
use App\Repositories\PositionRepository;
use App\Services\StaffImportService;
use App\Support\PhoneNumberFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffImportController extends Controller
{
    public function __construct(
        private readonly StaffImportService $importService,
        private readonly BranchRepository $branchRepository,
        private readonly DepartmentRepository $departmentRepository,
        private readonly PositionRepository $positionRepository,
    ) {}

    public function importForm(): View
    {
        return view('tenant.staff.import');
    }

    public function downloadImportTemplate(): StreamedResponse
    {
        $departments = $this->departmentRepository->allOrderedByName();
        $positions = $this->positionRepository->allOrderedByName();
        $branches = $this->branchRepository->allOrderedByName();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('staff');

        $headers = ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'];
        $sheet->fromArray($headers, null, 'A1');

        $sheet->fromArray([
            'John',
            'Doe',
            'john@example.com',
            'GH',
            '246227810',
            $departments->first()?->name ?? '',
            $positions->first()?->name ?? '',
            $branches->first()?->name ?? '',
            'Yes',
        ], null, 'A2');

        $listSheet = $spreadsheet->createSheet();
        $listSheet->setTitle('lists');
        $listSheet->fromArray(array_map(static fn(string $code): array => [$code], array_keys(PhoneNumberFormatter::dialCodeMap())), null, 'A1');
        $listSheet->fromArray($departments->map(static fn($department): array => [$department->name])->all(), null, 'B1');
        $listSheet->fromArray($positions->map(static fn($position): array => [$position->name])->all(), null, 'C1');
        $listSheet->fromArray($branches->map(static fn($branch): array => [$branch->name])->all(), null, 'D1');
        $listSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

        $this->applyDropdownFromRange($sheet, 'D2:D1048576', '=lists!$A$1:$A$' . max(1, count(PhoneNumberFormatter::dialCodeMap())));
        $this->applyDropdownFromRange($sheet, 'F2:F1048576', '=lists!$B$1:$B$' . max(1, $departments->count()));
        $this->applyDropdownFromRange($sheet, 'G2:G1048576', '=lists!$C$1:$C$' . max(1, $positions->count()));
        $this->applyDropdownFromRange($sheet, 'H2:H1048576', '=lists!$D$1:$D$' . max(1, $branches->count()));
        $this->applyDropdownFromRange($sheet, 'I2:I1048576', '"Yes,No"');

        $spreadsheet->setActiveSheetIndex(0);

        return Response::streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'staff-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(StaffImportRequest $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $actor */
        $actor = $request->user();
        $result = $this->importService->import($request->file('file'), $actor);

        return redirect()
            ->route('staff.import')
            ->with('success', __('staff.import_success', ['imported' => $result->imported, 'skipped' => $result->skipped]))
            ->with('import_errors', $result->errors);
    }

    private function applyDropdownFromRange(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range, string $formula): void
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1($formula);
        $validation->setSqref($range);

        [$startCell] = explode(':', $range);
        $sheet->setDataValidation($startCell, $validation);
    }
}
