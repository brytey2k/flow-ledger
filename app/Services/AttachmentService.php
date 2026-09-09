<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowDocumentRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AttachmentService
{
    public function storeEditableRequestAttachment(
        PaymentRequest|RetirementRequest $attachable,
        UploadedFile $file,
        User $uploader,
    ): Attachment {
        $this->authorizeEditableRequest($attachable, $uploader);

        return $this->store($attachable, $file, $uploader);
    }

    public function deleteEditableRequestAttachment(Attachment $attachment, User $user): void
    {
        $attachment->loadMissing('attachable');
        if ($attachment->workflow_document_request_id !== null || $attachment->user_id !== $user->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException('This attachment is immutable.');
        }
        $attachable = $attachment->attachable;
        if (! $attachable instanceof PaymentRequest && ! $attachable instanceof RetirementRequest) {
            throw new \Illuminate\Auth\Access\AuthorizationException('This attachment cannot be changed.');
        }

        $this->authorizeEditableRequest($attachable, $user);
        $this->delete($attachment);
    }

    public function store(
        Model $attachable,
        UploadedFile $file,
        User $uploader,
        WorkflowDocumentRequest|null $documentRequest = null,
    ): Attachment {
        $folder = $this->storageFolder($attachable);
        $path = $file->store($folder, 'local');
        if ($path === false) {
            throw new \RuntimeException('The attachment could not be stored.');
        }

        try {
            return DB::transaction(function () use ($attachable, $file, $uploader, $documentRequest, $path): Attachment {
                /** @var Attachment $attachment */
                // @phpstan-ignore-next-line method.notFound, method.nonObject
                $attachment = $attachable->attachments()->create([
                    'user_id' => $uploader->id,
                    'workflow_document_request_id' => $documentRequest?->id,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);

                return $attachment;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }
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
            DB::afterCommit(static fn() => Storage::disk('local')->delete($path));
        });
    }

    private function authorizeEditableRequest(PaymentRequest|RetirementRequest $attachable, User $user): void
    {
        if (! $attachable->isDraft() && ! $attachable->isSentBack()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Attachments may only be changed while the request is editable.');
        }

        $staffId = $attachable instanceof RetirementRequest
            ? $attachable->paymentRequest?->staff_id
            : $attachable->staff_id;
        if ($user->staffProfile?->id !== $staffId) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You do not own this request.');
        }
    }
}
