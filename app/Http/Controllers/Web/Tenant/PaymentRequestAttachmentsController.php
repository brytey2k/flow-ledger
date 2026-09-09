<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AttachmentUploadRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class PaymentRequestAttachmentsController extends Controller
{
    public function __construct(
        private readonly AttachmentService $service,
    ) {}

    public function store(AttachmentUploadRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);

        $this->service->storeEditableRequestAttachment($paymentRequest, $file, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.attachments.uploaded'));
    }
}
