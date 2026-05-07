<!-- Footer -->
<footer class="kt-footer">
    <!-- Container -->
    <div class="kt-container-fixed">
        <div class="flex flex-col items-center justify-center gap-3 py-5 md:flex-row md:justify-between">
            <div class="order-2 flex gap-2 text-sm font-normal md:order-1">
                <span class="text-secondary-foreground">
                    {{ now()->year }}©
                </span>
                <a class="hover:text-primary text-secondary-foreground" href="https://keenthemes.com">
                    <a href="{{ config('app.org_url') }}" target="_blank">{{ config('app.org_name') }}</a>
                </a>
            </div>
            <nav class="order-1 flex gap-4 text-sm font-normal text-secondary-foreground md:order-2">
                <a class="hover:text-primary" href="https://eazismspro.com" target="_blank">
                    Bulk SMS
                </a>
                <a class="hover:text-primary" href="https://softwaregh.com" target="_blank">
                    Buy Software
                </a>
            </nav>
        </div>
    </div>
    <!-- End of Container -->
</footer>
<!-- End of Footer -->
