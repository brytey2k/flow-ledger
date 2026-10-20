<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\CsvSanitizer;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use ZipArchive;

/** Exports transactional tenant tables as CSV files inside a ZIP. Must run inside tenant context. */
class TenantDataExporter
{
    /** @var list<string> */
    public const TABLES = [
        'payment_requests',
        'payment_request_items',
        'retirement_requests',
        'retirement_request_items',
        'retirement_reminder_logs',
        'comments',
        'attachments',
        'cashbooks',
        'cashbook_entries',
        'cash_counts',
        'cash_count_items',
        'workflow_instances',
        'workflow_instance_stages',
        'workflow_actions',
        'workflow_document_requests',
        'activity_log',
    ];

    /** @return array<string, int> */
    public function counts(): array
    {
        $counts = [];

        foreach (self::TABLES as $table) {
            $counts[$table] = DB::table($table)->count();
        }

        return $counts;
    }

    /**
     * Writes the ZIP to a temporary file and returns its path along with the exported row counts.
     *
     * @return array{path: string, counts: array<string, int>}
     */
    public function export(): array
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'tenant-export-');
        $zip = new ZipArchive();

        if ($zipPath === false || $zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create the export archive.');
        }

        $counts = [];
        $csvPaths = [];

        foreach (self::TABLES as $table) {
            $csvPath = tempnam(sys_get_temp_dir(), 'tenant-export-csv-');
            $handle = $csvPath === false ? false : fopen($csvPath, 'w');

            if ($csvPath === false || $handle === false) {
                throw new RuntimeException('Unable to create an export file.');
            }

            $counts[$table] = $this->writeTable($table, $handle);
            fclose($handle);
            $zip->addFile($csvPath, "{$table}.csv");
            $csvPaths[] = $csvPath;
        }

        $zip->close();
        array_map(unlink(...), $csvPaths);

        return ['path' => $zipPath, 'counts' => $counts];
    }

    /** @param resource $handle */
    private function writeTable(string $table, $handle): int
    {
        $rows = 0;

        foreach (DB::table($table)->orderBy('id')->lazyById(1000) as $row) {
            /** @var array<string, bool|float|int|string|null> $values */
            $values = (array) $row;

            if ($rows === 0) {
                fputcsv($handle, array_keys($values), ',', '"', '\\');
            }

            fputcsv($handle, CsvSanitizer::sanitizeRow($values), ',', '"', '\\');
            $rows++;
        }

        return $rows;
    }
}
