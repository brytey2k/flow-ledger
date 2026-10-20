<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TenantStorageService
{
    /** @var list<string> */
    private const ATTACHMENT_FOLDERS = ['payment-requests', 'retirements'];

    /**
     * @param Tenant $tenant
     *
     * @return array{
     *     usage: list<array{type: string, files: int, bytes: int}>,
     *     orphaned_files: list<string>,
     *     orphaned_bytes: int,
     *     missing_files: list<string>
     * }
     */
    public function report(Tenant $tenant): array
    {
        /** @var array{usage: list<array{type: string, files: int, bytes: int}>, orphaned_files: list<string>, orphaned_bytes: int, missing_files: list<string>} $report */
        $report = $tenant->run(function (): array {
            $usage = DB::table('attachments')
                ->whereNull('deleted_at')
                ->selectRaw('attachable_type as type, count(*) as files, coalesce(sum(size), 0) as bytes')
                ->groupBy('attachable_type')
                ->get()
                ->map(fn(object $row): array => [
                    'type' => class_basename(is_string($row->type) ? $row->type : ''),
                    'files' => is_numeric($row->files) ? (int) $row->files : 0,
                    'bytes' => is_numeric($row->bytes) ? (int) $row->bytes : 0,
                ])
                ->all();

            $orphans = $this->orphanedFiles();
            $disk = Storage::disk('local');

            return [
                'usage' => $usage,
                'orphaned_files' => $orphans,
                'orphaned_bytes' => array_sum(array_map(static fn(string $path): int => $disk->size($path), $orphans)),
                'missing_files' => DB::table('attachments')
                    ->whereNull('deleted_at')
                    ->pluck('path')
                    ->filter(static fn(mixed $path): bool => is_string($path) && ! $disk->exists($path))
                    ->values()
                    ->all(),
            ];
        });

        return $report;
    }

    /** @return array<string, int> */
    public function deleteOrphans(Tenant $tenant): array
    {
        /** @var array<string, int> $result */
        $result = $tenant->run(function (): array {
            $disk = Storage::disk('local');
            $orphans = $this->orphanedFiles();
            $bytes = array_sum(array_map(static fn(string $path): int => $disk->size($path), $orphans));
            $disk->delete($orphans);

            return ['orphaned_files_deleted' => count($orphans), 'bytes_freed' => $bytes];
        });

        return $result;
    }

    /** @return list<string> */
    private function attachmentPaths(): array
    {
        $paths = [];

        foreach (DB::table('attachments')->pluck('path') as $path) {
            if (is_string($path)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Files on disk that no attachment row (soft-deleted included) points at.
     *
     * @return list<string>
     */
    private function orphanedFiles(): array
    {
        $referenced = array_flip($this->attachmentPaths());
        $disk = Storage::disk('local');
        /** @var list<string> $orphans */
        $orphans = [];

        foreach (self::ATTACHMENT_FOLDERS as $folder) {
            foreach ($disk->allFiles($folder) as $path) {
                if (! isset($referenced[$path])) {
                    $orphans[] = $path;
                }
            }
        }

        return $orphans;
    }
}
