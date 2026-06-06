<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentsController extends Controller
{
    public function __construct(
        private readonly AttachmentService $service,
    ) {}

    public function store(Request $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');
        /** @var User $user */
        $user = $request->user();

        $this->service->store($retirementRequest, $file, $user);

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', __('flash.attachments.uploaded'));
    }

    public function download(Attachment $attachment): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $allowed = false;

        if ($attachment->attachable_type === RetirementRequest::class && $attachment->attachable instanceof RetirementRequest) {
            $paymentRequest = $attachment->attachable->paymentRequest;
            if ($paymentRequest !== null && $paymentRequest->staff !== null) {
                $allowed = $paymentRequest->staff->user_id === $user->id;
            }
        } elseif ($attachment->attachable_type === PaymentRequest::class && $attachment->attachable instanceof PaymentRequest) {
            $paymentRequest = $attachment->attachable;
            if ($paymentRequest->staff !== null) {
                $allowed = $paymentRequest->staff->user_id === $user->id;
            }
        }

        if (! $allowed && $attachment->user_id === $user->id) {
            $allowed = true;
        }

        abort_unless($allowed, 403);

        $this->service->delete($attachment);

        return redirect()->back()->with('success', __('flash.attachments.deleted'));
    }
}
