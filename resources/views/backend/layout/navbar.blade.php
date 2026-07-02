<div class="app-navbar flex-shrink-0">

    <!-- PWA Connection & Sync Status Badge -->
    <div class="app-navbar-item ms-2">
        <div id="pwa-sync-status" class="badge badge-light-success fw-bold px-4 py-3 d-flex align-items-center cursor-pointer" onclick="triggerManualSync()">
            <span class="bullet bullet-dot bg-success h-8px w-8px me-2" id="pwa-status-dot"></span>
            <span id="pwa-status-text">Online</span>
        </div>
    </div>

    <!--begin::Header menu mobile toggle-->
    <div class="app-navbar-item d-lg-none ms-2 me-n4" title="Show header menu">
        <div class="btn btn-icon btn-color-gray-600 btn-active-color-primary" id="kt_app_header_menu_toggle">
            <i class="ki-outline ki-text-align-left fs-2 fw-bold"></i>
        </div>
    </div>
    <!--end::Header menu mobile toggle-->
</div>
