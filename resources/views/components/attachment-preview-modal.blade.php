<div
    x-show="$store.attachmentPreview.open"
    x-cloak
    x-effect="if ($store.attachmentPreview.open) { $nextTick(() => $refs.closeButton.focus()) }"
    @keydown.escape.window="$store.attachmentPreview.hide()"
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6"
>
    <div class="sgh-modal-backdrop" @click="$store.attachmentPreview.hide()"></div>

    <section
        role="dialog"
        aria-modal="true"
        aria-labelledby="attachment-preview-title"
        class="relative z-50 grid h-[min(92vh,56rem)] w-full max-w-6xl grid-rows-[auto_1fr] overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-xl"
    >
        <header class="sgh-modal-header">
            <div class="min-w-0">
                <h2 id="attachment-preview-title" class="sgh-modal-title">{{ __('common.preview') }}</h2>
                <p class="truncate text-xs text-secondary-foreground" x-text="$store.attachmentPreview.name"></p>
            </div>
            <button
                x-ref="closeButton"
                type="button"
                class="sgh-modal-close shrink-0"
                aria-label="{{ __('common.close') }}"
                @click="$store.attachmentPreview.hide()"
            >
                <x-tabler-x-filled />
            </button>
        </header>

        <div class="relative min-h-0 bg-muted/30">
            <div
                x-show="$store.attachmentPreview.loading"
                class="absolute inset-0 z-10 flex items-center justify-center gap-2 bg-card text-sm text-secondary-foreground"
            >
                <x-tabler-loader-2 class="animate-spin" />
                {{ __('common.loading_preview') }}
            </div>
            <iframe
                :src="$store.attachmentPreview.open ? $store.attachmentPreview.url : 'about:blank'"
                :title="$store.attachmentPreview.name || '{{ __('common.preview') }}'"
                class="h-full w-full border-0 bg-white"
                @load="$store.attachmentPreview.loaded()"
            ></iframe>
        </div>
    </section>
</div>
