<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Position;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PositionImportService
{
    public function import(UploadedFile $file): int
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => __('positions.import_errors.unreadable'),
            ]);
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => __('positions.import_errors.unreadable'),
            ]);
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                throw ValidationException::withMessages([
                    'file' => __('positions.import_errors.empty'),
                ]);
            }

            $headers = array_map(
                fn(string|null $header): string => $this->normalizeHeader($header),
                $headers,
            );

            if ($headers !== ['name']) {
                throw ValidationException::withMessages([
                    'file' => __('positions.import_errors.invalid_headers'),
                ]);
            }

            $rows = [];
            $errors = [];
            $rowNames = [];
            $seenNames = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $name = trim((string) ($row[0] ?? ''));

                if ($name === '') {
                    $errors["rows.$rowNumber"] = __('positions.import_errors.name_required', ['row' => $rowNumber]);
                    continue;
                }

                if (mb_strlen($name) > 100) {
                    $errors["rows.$rowNumber"] = __('positions.import_errors.name_too_long', [
                        'row' => $rowNumber,
                        'name' => $name,
                    ]);
                    continue;
                }

                if (isset($seenNames[$name])) {
                    $errors["rows.$rowNumber"] = __('positions.import_errors.duplicate_in_file', [
                        'row' => $rowNumber,
                        'name' => $name,
                    ]);
                    continue;
                }

                $seenNames[$name] = $rowNumber;
                $rowNames[$rowNumber] = $name;
                $rows[] = $name;
            }

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'file' => __('positions.import_errors.no_rows'),
                ]);
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $existingNames = Position::query()
                ->whereIn('name', $rows)
                ->pluck('name')
                ->all();

            if ($existingNames !== []) {
                $existingLookup = array_fill_keys($existingNames, true);

                foreach ($rowNames as $rowNumber => $name) {
                    if (isset($existingLookup[$name])) {
                        $errors["rows.$rowNumber"] = __('positions.import_errors.duplicate_existing', [
                            'row' => $rowNumber,
                            'name' => $name,
                        ]);
                    }
                }
            }

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            return DB::transaction(function () use ($rows): int {
                foreach ($rows as $name) {
                    Position::create(['name' => $name]);
                }

                return count($rows);
            });
        } finally {
            fclose($handle);
        }
    }

    private function normalizeHeader(string|null $header): string
    {
        $value = trim((string) $header);
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;

        return mb_strtolower($value);
    }

    /**
     * @param array<int, string|null> $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
