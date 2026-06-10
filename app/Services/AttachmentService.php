<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Attachment;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function store(Model $attachable, UploadedFile $file, User $uploader): Attachment
    {
        $folder = $this->storageFolder($attachable);
        $path = $file->store($folder, 'local');

        return DB::transaction(function () use ($attachable, $file, $uploader, $path): Attachment {
            /** @var Attachment $attachment */
            // @phpstan-ignore-next-line method.notFound, method.nonObject
            $attachment = $attachable->attachments()->create([
                'user_id' => $uploader->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            return $attachment;
        });
    }

    private function storageFolder(Model $model): string
    {
        $rawKey = $model->getKey();
        $key = is_scalar($rawKey) ? (string) $rawKey : '';

        return match (true) {
            $model instanceof RetirementRequest => "retirements/{$key}/attachments",
            default => "payment-requests/{$key}/attachments",
        };
    }

    public function delete(Attachment $attachment): void
    {
        DB::transaction(function () use ($attachment): void {
            $path = $attachment->path;
            $attachment->delete();
            Storage::disk('local')->delete($path);
        });
    }
}
