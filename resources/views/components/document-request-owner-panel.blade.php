@props(['documentRequest'])

@if($documentRequest && $documentRequest->requester_user_id === auth()->id())
    <div class="rounded-lg border border-primary/30 bg-primary/5 p-4">
        <div class="flex flex-col gap-3">
            <div>
                <p class="text-sm font-medium text-mono">Additional documents requested</p>
                <p class="text-sm text-secondary-foreground">{{ $documentRequest->reason }}</p>
            </div>

            @if($documentRequest->status === \App\Enums\Tenant\WorkflowDocumentRequestStatus::AwaitingUploads)
                <form method="POST" action="{{ route('document-requests.attachments.store', $documentRequest) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border p-4 transition-colors hover:border-primary/50 hover:bg-muted/30">
                        <x-tabler-cloud-upload class="text-2xl text-muted-foreground" />
                        <span class="text-sm font-medium text-foreground">Upload a requested document</span>
                        <span class="text-xs text-secondary-foreground">PDF, JPG, PNG, Word or Excel — max 10MB</span>
                        <input type="file" name="file" class="sr-only" onchange="this.closest('form').submit()" />
                    </label>
                </form>

                @foreach($documentRequest->attachments as $attachment)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-background p-3">
                        <span class="min-w-0 truncate text-sm text-foreground">{{ $attachment->original_name }}</span>
                        <form method="POST" action="{{ route('document-requests.attachments.destroy', [$documentRequest, $attachment]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-outline text-destructive">
                                <x-tabler-trash-filled />
                            </button>
                        </form>
                    </div>
                @endforeach

                <form method="POST" action="{{ route('document-requests.submit', $documentRequest) }}">
                    @csrf
                    <button type="submit" class="sgh-btn sgh-btn-primary w-full" {{ $documentRequest->attachments->isEmpty() ? 'disabled' : '' }}>
                        Submit documents
                    </button>
                </form>
            @else
                <p class="text-sm text-secondary-foreground">Your uploads are closed and the approver is reviewing the new documents.</p>
            @endif
        </div>
    </div>
@endif
