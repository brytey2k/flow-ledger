<!-- Scripts -->
<script src="{{ asset('assets/js/core.bundle.js') }}" data-navigate-once></script>
<script src="{{ asset('assets/vendors/ktui/ktui.min.js') }}" data-navigate-once></script>
<script src="{{ asset('assets/vendors/apexcharts/apexcharts.min.js') }}" data-navigate-once></script>
<script src="{{ asset('assets/js/layouts/demo1.js') }}" data-navigate-once></script>

<!--end::Global Javascript Bundle-->
<!--begin::Custom Javascript-->
@stack('custom_js')
<!--end::Custom Javascript-->
<!--begin::Page Vendors Javascript-->
@stack('vendor_js')
<!--end::Page Vendors Javascript-->
<!--begin::Page Custom Javascript-->
@stack('page_js')
<!--end::Page Custom Javascript-->
<!--begin::Compiled App Scripts-->
@vite(['resources/js/app.js'])
<!--end::Compiled App Scripts-->
<!--end::Javascript-->
