<!--Megamenu Contaoner-->
<div class="flex items-stretch" id="megaMenuContainer">
    <!--Megamenu Inner-->
    <div class="flex items-stretch [--kt-reparent-mode:prepend] [--kt-reparent-target:body] lg:[--kt-reparent-mode:prepend] lg:[--kt-reparent-target:#megaMenuContainer]"
        data-kt-reparent="true">
        <!--Megamenu Wrapper-->
        <div class="hidden [--kt-drawer-enable:true] lg:flex lg:items-stretch lg:[--kt-drawer-enable:false]"
            data-kt-drawer="true"
            data-kt-drawer-class="kt-drawer kt-drawer-start fixed z-10 top-0 bottom-0 w-full me-5 max-w-[250px] p-5 lg:p-0 overflow-auto"
            id="mega_menu_wrapper">
            <!--Megamenu-->
            <div class="kt-menu flex-col gap-5 lg:flex-row lg:gap-7.5" data-kt-menu="true" id="mega_menu">
                <!--Megamenu Item-->
                <div class="kt-menu-item" data-kt-menu-item-placement="bottom-start"
                    data-kt-menu-item-placement-rtl="bottom-end" data-kt-menu-item-toggle="accordion|lg:dropdown"
                    data-kt-menu-item-trigger="click|lg:hover">
                </div>
                <!--End of Megamenu Item-->
                <!--Megamenu Item-->
                <div class="kt-menu-item" data-kt-menu-item-offset="0,0|lg:-20px, 0"
                    data-kt-menu-item-offset-rtl="0,0|lg:20px, 0" data-kt-menu-item-overflow="true"
                    data-kt-menu-item-placement="bottom-start" data-kt-menu-item-placement-rtl="bottom-end"
                    data-kt-menu-item-toggle="dropdown" data-kt-menu-item-trigger="click|lg:hover">
                    <div
                        class="kt-menu-link kt-menu-link-hover:text-primary kt-menu-item-active:text-mono kt-menu-item-show:text-primary kt-menu-item-here:text-mono kt-menu-item-active:font-semibold kt-menu-item-here:font-semibold text-sm font-medium text-secondary-foreground">
                        <span class="kt-menu-title text-nowrap">
                            Help
                        </span>
                        <span class="kt-menu-arrow flex lg:hidden">
                            <span class="kt-menu-item-show:hidden text-muted-foreground">
                                <i class="ki-filled ki-plus text-xs">
                                </i>
                            </span>
                            <span class="kt-menu-item-show:inline-flex hidden">
                                <i class="ki-filled ki-minus text-xs">
                                </i>
                            </span>
                        </span>
                    </div>
                    <div class="kt-menu-dropdown kt-menu-default w-full max-w-[220px] py-2.5">
                        <div class="kt-menu-item">
                            <a class="kt-menu-link"
                                href="{{ route('landlord.documentation') }}"
                                tabindex="0">
                                <span class="kt-menu-icon">
                                    <i class="ki-filled ki-questionnaire-tablet">
                                    </i>
                                </span>
                                <span class="kt-menu-title grow-0">
                                    Documentation
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
                <!--End of Megamenu Item-->
            </div>
            <!--End of Megamenu-->
        </div>
        <!--End of Megamenu Wrapper-->
    </div>
    <!--End of Megamenu Inner-->
</div>
<!--End of Megamenu Contaoner-->
