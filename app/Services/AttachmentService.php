<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Attachment;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function store(RetirementRequest $retirementRequest, UploadedFile $file, User $uploader): Attachment
    {
        $path = $file->store("retirements/{$retirementRequest->id}/attachments", 'local');

        return DB::transaction(function () use ($retirementRequest, $file, $uploader, $path): Attachment {
            /** @var Attachment $attachment */
            $attachment = $retirementRequest->attachments()->create([
                'user_id' => $uploader->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            return $attachment;
        });
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
