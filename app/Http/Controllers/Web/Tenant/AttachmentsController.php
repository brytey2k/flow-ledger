<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Services\AttachmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            ->with('success', 'Attachment uploaded.');
    }

    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        $this->service->delete($attachment);

        return redirect()->back()->with('success', 'Attachment deleted.');
    }
}
