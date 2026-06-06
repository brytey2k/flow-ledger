<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentRequestAttachmentsController extends Controller
{
    public function __construct(
        private readonly AttachmentService $service,
    ) {}

    public function store(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        abort_unless($paymentRequest->isDraft() || $paymentRequest->isSentBack(), 403);

        /** @var User $user */
        $user = $request->user();

        abort_unless($user->staffProfile?->id === $paymentRequest->staff_id, 403);

        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('file');

        $this->service->store($paymentRequest, $file, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.attachments.uploaded'));
    }
}
