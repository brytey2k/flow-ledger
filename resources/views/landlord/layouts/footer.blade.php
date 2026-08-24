<!-- Footer -->
<footer class="sgh-footer">
    <!-- Container -->
    <div class="sgh-container-fixed">
        @php
            $footerOrgUrl = is_string(config('app.org_url')) ? config('app.org_url') : '#';
            $footerOrgName = is_string(config('app.org_name')) ? config('app.org_name') : '';
        @endphp
        <div class="flex flex-col items-center justify-center gap-3 py-5 md:flex-row md:justify-between">
            <div class="order-2 flex gap-2 text-sm font-normal md:order-1">
                <span class="text-secondary-foreground">
                    {{ now()->format('Y') }}©
                </span>
                <a class="hover:text-primary text-secondary-foreground" href="{{ $footerOrgUrl }}" target="_blank">{{ $footerOrgName }}</a>
            </div>
            <nav class="order-1 flex gap-4 text-sm font-normal text-secondary-foreground md:order-2">
                <a class="hover:text-primary" href="https://eazismspro.com" target="_blank">
                    {{ __('common.bulk_sms') }}
                </a>
                <a class="hover:text-primary" href="https://softwaregh.com" target="_blank">
                    {{ __('common.buy_software') }}
                </a>
            </nav>
        </div>
    </div>
    <!-- End of Container -->
</footer>
<!-- End of Footer -->
