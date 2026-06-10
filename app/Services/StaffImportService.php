<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreateUserDto;
use App\DTOs\Tenant\StaffImportResult;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Support\PhoneNumberFormatter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class StaffImportService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function import(UploadedFile $file, User $actor): StaffImportResult
    {
        $path = $file->getRealPath();

        if ($path === false) {
            return new StaffImportResult(imported: 0, skipped: 1, errors: [__('staff.import_errors.unreadable')]);
        }

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (Throwable) {
            return new StaffImportResult(imported: 0, skipped: 1, errors: [__('staff.import_errors.unreadable')]);
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if ($rows === []) {
            return new StaffImportResult(imported: 0, skipped: 1, errors: [__('staff.import_errors.empty')]);
        }

        /** @var array<int, string|null> $headerRow */
        $headerRow = array_shift($rows);
        $headers = $this->normalizeHeaders($headerRow);

        $expectedHeaders = ['first_name', 'last_name', 'email', 'phone_country', 'phone_number', 'department', 'position', 'branch', 'grant_login_access'];

        if ($headers !== $expectedHeaders) {
            return new StaffImportResult(imported: 0, skipped: 1, errors: [__('staff.import_errors.invalid_headers')]);
        }

        if ($rows === []) {
            return new StaffImportResult(imported: 0, skipped: 1, errors: [__('staff.import_errors.no_rows')]);
        }

        $departmentLookup = Department::query()->pluck('id', 'name')->all();
        $positionLookup = Position::query()->pluck('id', 'name')->all();

        $normalizedDepartmentLookup = [];
        foreach ($departmentLookup as $name => $id) {
            $normalizedDepartmentLookup[$this->normalizeLookupKey((string) $name)] = is_scalar($id) ? (int) $id : 0;
        }

        $normalizedPositionLookup = [];
        foreach ($positionLookup as $name => $id) {
            $normalizedPositionLookup[$this->normalizeLookupKey((string) $name)] = is_scalar($id) ? (int) $id : 0;
        }

        $allowedBranchIds = $this->branchScope->allowedBranchIds($actor);
        $branchLookup = Branch::query()
            ->whereIn('id', $allowedBranchIds)
            ->pluck('id', 'name')
            ->all();

        $normalizedBranchLookup = [];
        foreach ($branchLookup as $name => $id) {
            $normalizedBranchLookup[$this->normalizeLookupKey((string) $name)] = is_scalar($id) ? (int) $id : 0;
        }

        $countryCodes = array_keys(PhoneNumberFormatter::dialCodeMap());
        $seenStaffEmails = [];
        $seenUserEmails = [];
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            /** @var array<int, scalar|null> $row */
            $firstName = trim((string) ($row[0] ?? ''));
            $lastName = trim((string) ($row[1] ?? ''));
            $email = mb_strtolower(trim((string) ($row[2] ?? '')));
            $phoneCountry = mb_strtoupper(trim((string) ($row[3] ?? '')));
            $phoneNumber = trim((string) ($row[4] ?? ''));
            $departmentName = trim((string) ($row[5] ?? ''));
            $positionName = trim((string) ($row[6] ?? ''));
            $branchName = trim((string) ($row[7] ?? ''));
            $grantLoginAccessRaw = $row[8] ?? null;

            if ($firstName === '') {
                $skipped++;
                $errors[] = __('staff.import_errors.first_name_required', ['row' => $rowNumber]);
                continue;
            }

            if ($lastName === '') {
                $skipped++;
                $errors[] = __('staff.import_errors.last_name_required', ['row' => $rowNumber]);
                continue;
            }

            if ($email !== '') {
                if (isset($seenStaffEmails[$email])) {
                    $skipped++;
                    $errors[] = __('staff.import_errors.duplicate_staff_email_in_file', ['row' => $rowNumber, 'email' => $email]);
                    continue;
                }

                $staffEmailExists = Staff::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->exists();

                if ($staffEmailExists) {
                    $skipped++;
                    $errors[] = __('staff.import_errors.duplicate_staff_email_existing', ['row' => $rowNumber, 'email' => $email]);
                    continue;
                }

                $seenStaffEmails[$email] = true;
            }

            $departmentId = $normalizedDepartmentLookup[$this->normalizeLookupKey($departmentName)] ?? null;
            if ($departmentId === null) {
                $skipped++;
                $errors[] = __('staff.import_errors.department_not_found', ['row' => $rowNumber, 'department' => $departmentName]);
                continue;
            }

            $positionId = $normalizedPositionLookup[$this->normalizeLookupKey($positionName)] ?? null;
            if ($positionId === null) {
                $skipped++;
                $errors[] = __('staff.import_errors.position_not_found', ['row' => $rowNumber, 'position' => $positionName]);
                continue;
            }

            $branchId = $actor->operational_branch_id;
            if ($branchName !== '') {
                $branchId = $normalizedBranchLookup[$this->normalizeLookupKey($branchName)] ?? null;
            }

            if ($branchId === null) {
                $skipped++;
                $errors[] = __('staff.import_errors.branch_not_found', ['row' => $rowNumber, 'branch' => $branchName]);
                continue;
            }

            if (! in_array($branchId, $allowedBranchIds, true)) {
                $skipped++;
                $errors[] = __('staff.import_errors.branch_not_allowed', ['row' => $rowNumber, 'branch' => $branchName]);
                continue;
            }

            if ($phoneCountry !== '' && ! in_array($phoneCountry, $countryCodes, true)) {
                $skipped++;
                $errors[] = __('staff.import_errors.phone_country_invalid', ['row' => $rowNumber, 'country' => $phoneCountry]);
                continue;
            }

            $grantLoginAccess = $this->parseGrantLoginAccess($grantLoginAccessRaw);

            if ($grantLoginAccess === null) {
                $skipped++;
                $errors[] = __('staff.import_errors.grant_login_access_invalid', ['row' => $rowNumber]);
                continue;
            }

            if ($grantLoginAccess && $email !== '') {
                if (isset($seenUserEmails[$email])) {
                    $skipped++;
                    $errors[] = __('staff.import_errors.duplicate_user_email_in_file', ['row' => $rowNumber, 'email' => $email]);
                    continue;
                }

                $userEmailExists = User::query()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->exists();

                if ($userEmailExists) {
                    $skipped++;
                    $errors[] = __('staff.import_errors.duplicate_user_email_existing', ['row' => $rowNumber, 'email' => $email]);
                    continue;
                }

                $seenUserEmails[$email] = true;
            }

            try {
                DB::transaction(function () use (
                    $firstName,
                    $lastName,
                    $email,
                    $phoneCountry,
                    $phoneNumber,
                    $departmentId,
                    $positionId,
                    $branchId,
                    $grantLoginAccess,
                    $actor,
                ): void {
                    $userId = null;

                    if ($grantLoginAccess && $email !== '') {
                        $user = $this->userService->create(
                            new CreateUserDto(
                                firstName: $firstName,
                                lastName: $lastName,
                                email: $email,
                                password: Str::password(12),
                                branchId: $branchId,
                                operationalBranchId: $branchId,
                                roles: [],
                            ),
                            $actor,
                        );

                        $userId = $user->id;
                    }

                    Staff::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email !== '' ? $email : null,
                        'phone' => PhoneNumberFormatter::assemble(
                            $phoneCountry !== '' ? $phoneCountry : null,
                            $phoneNumber !== '' ? $phoneNumber : null,
                        ),
                        'department_id' => $departmentId,
                        'position_id' => $positionId,
                        'branch_id' => $branchId,
                        'user_id' => $userId,
                    ]);
                });

                $imported++;
            } catch (Throwable $throwable) {
                $skipped++;
                $errors[] = __('staff.import_errors.row_failed', ['row' => $rowNumber, 'error' => $throwable->getMessage()]);
            }
        }

        return new StaffImportResult(
            imported: $imported,
            skipped: $skipped,
            errors: $errors,
        );
    }

    /**
     * @param array<int, string|null> $row
     *
     * @return array<int, string>
     */
    private function normalizeHeaders(array $row): array
    {
        return array_map(
            fn(string|null $header): string => mb_strtolower(trim((string) $header)),
            array_slice($row, 0, 9),
        );
    }

    private function normalizeLookupKey(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    /**
     * @param array<int, string|null> $row
     */
    /** @param array<mixed> $row */
    private function isEmptyRow(array $row): bool
    {
        foreach (array_slice($row, 0, 9) as $cell) {
            if (trim(is_scalar($cell) ? (string) $cell : '') !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseGrantLoginAccess(mixed $value): bool|null
    {
        $normalized = mb_strtolower(trim(is_scalar($value) ? (string) $value : ''));

        if ($normalized === '') {
            return true;
        }

        if (in_array($normalized, ['yes', '1', 'true'], true)) {
            return true;
        }

        if (in_array($normalized, ['no', '0', 'false'], true)) {
            return false;
        }

        return null;
    }
}
