<div class="app-header-menu app-header-mobile-drawer align-items-start align-items-lg-center w-100" data-kt-drawer="true"
    data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="end"
    data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true"
    data-kt-swapper-mode="{default: 'append', lg: 'prepend'}"
    data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
    <div class="menu menu-rounded menu-active-bg menu-state-primary menu-column menu-lg-row menu-title-gray-700 menu-icon-gray-500 menu-arrow-gray-500 menu-bullet-gray-500 my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0"
        id="kt_app_header_menu" data-kt-menu="true">
        <div
            class="menu-item menu-here-bg me-0 me-lg-2 menu-hover-bg menu-hover-bg-warning {{ request()->routeIs('dashboard') ? 'here show ' : '' }}">
            <a href="{{ route('dashboard') }}"
                class="menu-link px-4 {{ request()->routeIs('dashboard') ? 'active ' : '' }}">

                <span class="menu-title">Dashboards</span>
            </a>
        </div>
        {{-- DATA MASTER (disembunyikan utk Superadmin di mode Analitik) --}}
        @if (! ($isSuperadminView ?? false) || ($saMode ?? 'pos') === 'pos')
        @can('view_data_master')
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <span
                    class="menu-link py-3  {{ request()->routeIs('categories.index', 'menus.index', 'promos.index') ? 'active ' : '' }}">
                    <span class="menu-title">Data Master</span>
                    <span class="menu-arrow d-lg-none">
                    </span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-210px">

                    <div class="menu-item {{ request()->routeIs('categories.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('categories.index') }}">
                            <span class="menu-icon"><i class="ki-outline ki-category fs-2"></i></span>
                            <span class="menu-title">Kategori Menu</span>
                        </a>
                    </div>
                    <div class="menu-item {{ request()->routeIs('menus.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3" href="{{ route('menus.index') }}"> <span class="menu-icon"><i
                                    class="ki-outline ki-coffee fs-2"></i></span>
                            <span class="menu-title">Menu Makanan & Minuman</span>
                        </a>
                    </div>
                    @if (\App\Tenancy\Plan::tenantAllows($currentTenant ?? null, 'promo'))
                        <div class="menu-item {{ request()->routeIs('promos.index') ? 'here show ' : '' }}">
                            <a class="menu-link py-3" href="{{ route('promos.index') }}">
                                <span class="menu-icon"><i class="ki-outline ki-discount fs-2"></i></span>
                                <span class="menu-title">Promo & Diskon</span>
                            </a>
                        </div>
                    @endif
                    @if (\App\Tenancy\Plan::tenantAllows($currentTenant ?? null, 'tables'))
                        <div class="menu-item {{ request()->routeIs('tables.index') ? 'here show ' : '' }}">
                            <a class="menu-link py-3" href="{{ route('tables.index') }}">
                                <span class="menu-icon"><i class="ki-outline ki-tablet-book fs-2"></i></span>
                                <span class="menu-title">Manajemen Meja</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endcan
        @endif

        {{-- REPORT (disembunyikan utk Superadmin di mode Analitik) --}}
        @if (! ($isSuperadminView ?? false) || ($saMode ?? 'pos') === 'pos')
        @can('view_report')
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <span
                    class="menu-link py-3  {{ request()->routeIs('reports.sales.index', 'reports.items.index') ? 'active ' : '' }}">
                    <span class="menu-title">Report</span>
                    <span class="menu-arrow d-lg-none">
                    </span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-210px">

                    <div class="menu-item {{ request()->routeIs('reports.sales.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('reports.sales.index') }}"> <span class="menu-icon">
                                <i class="ki-outline ki-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">Sales Report</span>
                        </a>
                    </div>
                    @if (\App\Tenancy\Plan::tenantAllows($currentTenant ?? null, 'report_items'))
                        <div class="menu-item {{ request()->routeIs('reports.items.index') ? 'here show ' : '' }}">
                            <a class="menu-link py-3 " href="{{ route('reports.items.index') }}"> <span class="menu-icon">
                                    <i class="ki-outline ki-rocket fs-2"></i>
                                </span>
                                <span class="menu-title">Sales Items Report</span>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endcan
        @endif

        {{-- PENGELUARAN: owner/admin/kasir tenant (Superadmin tanpa tenant tidak tampil) --}}
        @if (auth()->user()->tenant_id)
            @can('view_expense')
                <div class="menu-item menu-here-bg me-0 me-lg-2 {{ request()->routeIs('expenses.*') ? 'here show ' : '' }}">
                    <a href="{{ route('expenses.index') }}" class="menu-link px-4 {{ request()->routeIs('expenses.*') ? 'active ' : '' }}">
                        <span class="menu-title">Pengeluaran</span>
                    </a>
                </div>
            @endcan
        @endif

        {{-- RESOURCES: Superadmin only --}}
        @can('view_resources')
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <span class="menu-link py-3  {{ request()->routeIs('users.index', 'roles.index') ? 'active ' : '' }}">
                    <span class="menu-title">Resources</span>
                    <span class="menu-arrow d-lg-none">
                    </span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-210px">

                    <div class="menu-item {{ request()->routeIs('users.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('users.index') }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">User Management</span>
                        </a>
                    </div>
                    @hasrole('Superadmin')
                        <div class="menu-item {{ request()->routeIs('roles.index') ? 'here show ' : '' }}">
                            <a class="menu-link py-3" href="{{ route('roles.index') }}">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-code fs-2"></i>
                                </span>
                                <span class="menu-title">Role Management</span>
                            </a>
                        </div>
                    @endhasrole
                </div>
            </div>
        @endcan


        {{-- MANAJEMEN TENANT: Superadmin only --}}
        @can('view_tenants')
            <div
                class="menu-item menu-here-bg me-0 me-lg-2 {{ request()->routeIs('tenants.*') ? 'here show ' : '' }}">
                <a href="{{ route('tenants.index') }}"
                    class="menu-link px-4 {{ request()->routeIs('tenants.*') ? 'active ' : '' }}">
                    <span class="menu-title">Manajemen Tenant</span>
                </a>
            </div>

            {{-- SETELAN DEPOSIT: Superadmin only --}}
            <div
                class="menu-item menu-here-bg me-0 me-lg-2 {{ request()->routeIs('deposit-settings.*') ? 'here show ' : '' }}">
                <a href="{{ route('deposit-settings.index') }}"
                    class="menu-link px-4 {{ request()->routeIs('deposit-settings.*') ? 'active ' : '' }}">
                    <span class="menu-title">Setelan Deposit</span>
                </a>
            </div>
        @endcan

        {{-- LANGGANAN / BILLING: owner & admin tenant (bukan Superadmin yang tanpa tenant) --}}
        @if (auth()->user()->tenant_id && auth()->user()->can('view_billing'))
            <div
                class="menu-item menu-here-bg me-0 me-lg-2 {{ request()->routeIs('billing.*') ? 'here show ' : '' }}">
                <a href="{{ route('billing.index') }}"
                    class="menu-link px-4 {{ request()->routeIs('billing.*') ? 'active ' : '' }}">
                    <span class="menu-title">Langganan</span>
                </a>
            </div>

            {{-- PLAN DEPOSIT / POIN --}}
            <div
                class="menu-item menu-here-bg me-0 me-lg-2 {{ request()->routeIs('deposit.*') ? 'here show ' : '' }}">
                <a href="{{ route('deposit.index') }}"
                    class="menu-link px-4 {{ request()->routeIs('deposit.*') ? 'active ' : '' }}">
                    <span class="menu-title">Deposit</span>
                </a>
            </div>
        @endif

        {{-- APLIKASI TABLET (semua user login) --}}
        <div class="menu-item menu-here-bg me-0 me-lg-2 {{ request()->routeIs('download-app') ? 'here show ' : '' }}">
            <a href="{{ route('download-app') }}"
                class="menu-link px-4 {{ request()->routeIs('download-app') ? 'active ' : '' }}">
                <span class="menu-title">Aplikasi</span>
            </a>
        </div>

        {{-- HELP: Superadmin + admin + owner (yang punya view_help) --}}
        @can('view_help')
            <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                class="menu-item menu-lg-down-accordion menu-sub-lg-down-indention me-0 me-lg-2">
                <span class="menu-link py-3  {{ request()->routeIs('log-activity.index') ? 'active ' : '' }}">
                    <span class="menu-title">Help</span>
                    <span class="menu-arrow d-lg-none">
                    </span>
                </span>
                <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px">
                    <div class="menu-item {{ request()->routeIs('log-activity.index') ? 'here show ' : '' }}">
                        <a class="menu-link py-3 " href="{{ route('log-activity.index') }}">
                            <span class="menu-icon">
                                <i class="ki-outline ki-rocket fs-2"></i>
                            </span>
                            <span class="menu-title">Log Activity</span>
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        {{-- TOGGLE MODE (Superadmin): Analitik <-> Kasir + pemilih toko utk mode POS --}}
        @if ($isSuperadminView ?? false)
            <div class="menu-item d-flex align-items-center ms-lg-2 my-2 my-lg-0 gap-2 flex-wrap">
                @if (($saMode ?? 'analytics') === 'pos' && !empty($saTenants) && count($saTenants))
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light-warning fw-bold dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ki-outline ki-shop fs-5 me-1"></i> Toko: {{ $saPosTenantName ?? 'Pilih' }}
                        </button>
                        <div class="dropdown-menu p-2" style="max-height: 320px; overflow-y: auto;">
                            <div class="text-muted fw-semibold fs-8 px-3 pb-2">Operasikan kasir untuk toko:</div>
                            @foreach ($saTenants as $t)
                                <a class="dropdown-item rounded {{ ($saPosTenantId == $t->id) ? 'active' : '' }}"
                                    href="{{ route('pos-tenant.set', $t->id) }}">{{ $t->name }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if (($saMode ?? 'analytics') === 'analytics')
                    <a href="{{ route('view-mode.switch', 'pos') }}" class="btn btn-sm btn-light-primary fw-bold">
                        <i class="ki-outline ki-handcart fs-4 me-1"></i> Mode Kasir
                    </a>
                @else
                    <a href="{{ route('view-mode.switch', 'analytics') }}" class="btn btn-sm btn-light-primary fw-bold">
                        <i class="ki-outline ki-chart-simple fs-4 me-1"></i> Mode Analitik
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
