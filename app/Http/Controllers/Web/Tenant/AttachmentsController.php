<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\RetirementRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentsController extends Controller
{
    public function store(Request $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx'],
        ]);

        $file = $request->file('file');
        $path = $file->store("retirements/{$retirementRequest->id}/attachments", 'local');

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $retirementRequest->attachments()->create([
            'user_id' => $user->id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', 'Attachment uploaded.');
    }

    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();

        return redirect()->back()->with('success', 'Attachment deleted.');
    }
}
