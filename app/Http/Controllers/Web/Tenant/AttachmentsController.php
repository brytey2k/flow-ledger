<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Services\AttachmentService;
use App\Services\BranchScopeService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentsController extends Controller
{
    public function __construct(
        private readonly AttachmentService $service,
        private readonly WorkflowEngineService $engine,
        private readonly BranchScopeService $branchScope,
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

        $branchIds = $this->branchScope->allowedBranchIds($user);
        $paymentBranchId = $retirementRequest->paymentRequest?->branch_id;
        abort_unless($paymentBranchId !== null && in_array($paymentBranchId, $branchIds, true), 403);
        abort_unless($retirementRequest->paymentRequest->staff?->user_id === $user->id, 403);

        $this->service->store($retirementRequest, $file, $user);

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', __('flash.attachments.uploaded'));
    }

    public function download(Request $request, Attachment $attachment): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($this->canDownload($attachment, $user), 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($attachment->user_id === $user->id, 403);

        $this->service->delete($attachment);

        return redirect()->back()->with('success', __('flash.attachments.deleted'));
    }

    private function canDownload(Attachment $attachment, User $user): bool
    {
        if ($attachment->user_id === $user->id) {
            return true;
        }

        $paymentRequest = null;

        if ($attachment->attachable_type === RetirementRequest::class && $attachment->attachable instanceof RetirementRequest) {
            $paymentRequest = $attachment->attachable->paymentRequest;
        } elseif ($attachment->attachable_type === PaymentRequest::class && $attachment->attachable instanceof PaymentRequest) {
            $paymentRequest = $attachment->attachable;
        }

        if ($paymentRequest !== null && $paymentRequest->staff !== null && $paymentRequest->staff->user_id === $user->id) {
            return true;
        }

        if ($attachment->attachable instanceof PaymentRequest || $attachment->attachable instanceof RetirementRequest) {
            return $this->engine->userIsInApprovalChain($attachment->attachable, $user);
        }

        return false;
    }
}
