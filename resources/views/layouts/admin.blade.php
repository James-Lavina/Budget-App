<!DOCTYPE html>
<html lang="en" class="h-full bg-[#f4f6fa]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ \App\Models\AppSetting::current()->application_name }} — Admin</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/chart.min.js') }}"></script>
    @livewireStyles

    <style>
        :root {
            --brand-primary: {{ \App\Models\AppSetting::current()->primary_color }};
        }
    </style>
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-[#f4f6fa]">

    @php $appSettings = \App\Models\AppSetting::current(); @endphp

    <div id="admin-sidebar-overlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

    <div class="min-h-full flex flex-col lg:flex-row w-full">

        {{-- SIDEBAR --}}
        <aside id="admin-sidebar" class="fixed inset-y-3 left-3 z-50 w-64 lg:w-64 bg-white border border-slate-200/70 rounded-3xl p-5 flex flex-col justify-between transform -translate-x-[calc(100%+1rem)] lg:translate-x-0 transition-all duration-300 ease-in-out lg:fixed lg:top-4 lg:bottom-4 lg:left-4 lg:z-30 shrink-0 overflow-y-auto overflow-x-hidden shadow-sm">
            <div class="space-y-6">

                {{-- Brand --}}
                <div id="admin-brand-row" class="flex items-center justify-between px-2 pt-1">
                    <a href="{{ route('admin.dashboard') }}" id="admin-brand-link" class="flex items-center gap-3 min-w-0">
                        <div class="h-10 w-10 rounded-2xl flex items-center justify-center text-white shadow-md shrink-0 overflow-hidden" style="background-color: var(--brand-primary);">
                            @if($appSettings->logo_path)
                                <img src="{{ Storage::url($appSettings->logo_path) }}" class="h-full w-full object-cover">
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                            @endif
                        </div>
                        <div class="leading-tight min-w-0" data-sidebar-label>
                            <span class="font-black text-slate-900 text-lg tracking-tight block truncate">{{ $appSettings->application_name }}</span>
                            <span class="text-[11px] font-semibold text-slate-400 block truncate">Admin Panel</span>
                        </div>
                    </a>
                    <button id="admin-close-sidebar-btn" type="button" class="lg:hidden p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 shrink-0">
                        <x-heroicon-o-x class="w-5 h-5" />
                    </button>
                </div>

                {{-- Nav links --}}
                <nav class="space-y-1.5">
                    <a href="{{ route('admin.dashboard') }}" title="Dashboard" data-sidebar-nav-item
                        @if(request()->routeIs('admin.dashboard')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-sm transition-all {{ request()->routeIs('admin.dashboard') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <x-heroicon-o-home class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>Dashboard</span>
                    </a>

                    <a href="{{ route('admin.users') }}" title="User Management" data-sidebar-nav-item
                        @if(request()->routeIs('admin.users')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('admin.users') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <x-heroicon-o-users class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>User Management</span>
                    </a>

                    <a href="{{ route('admin.categories') }}" title="Expense Categories" data-sidebar-nav-item
                        @if(request()->routeIs('admin.categories')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('admin.categories') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <x-heroicon-o-tag class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>Expense Categories</span>
                    </a>

                    <a href="{{ route('admin.risk-rules') }}" title="Risk Detection Rules" data-sidebar-nav-item
                        @if(request()->routeIs('admin.risk-rules')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('admin.risk-rules') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <x-heroicon-o-shield-check class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>Risk Detection Rules</span>
                    </a>

                    <a href="{{ route('admin.ocr-ai-settings') }}" title="OCR & AI Settings" data-sidebar-nav-item
                        @if(request()->routeIs('admin.ocr-ai-settings')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('admin.ocr-ai-settings') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <x-heroicon-o-desktop-computer class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>OCR &amp; AI Settings</span>
                    </a>

                    <a href="{{ route('admin.reports') }}" title="Reports" data-sidebar-nav-item
                        @if(request()->routeIs('admin.reports')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('admin.reports') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <x-heroicon-o-document-report class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>Reports</span>
                    </a>

                    <a href="{{ route('admin.activity-logs') }}" title="Activity Logs" data-sidebar-nav-item
                        @if(request()->routeIs('admin.activity-logs')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('admin.activity-logs') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <x-heroicon-o-clock class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>Activity Logs</span>
                    </a>

                    <a href="{{ route('admin.settings') }}" title="Settings" data-sidebar-nav-item
                        @if(request()->routeIs('admin.settings')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                        class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('admin.settings') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                        <x-heroicon-o-cog class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>Settings</span>
                    </a>

                    @if(auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.accounts') }}" title="Admin Accounts" data-sidebar-nav-item
                            @if(request()->routeIs('admin.accounts')) style="background-color: color-mix(in srgb, var(--brand-primary) 10%, white); color: var(--brand-primary);" @endif
                            class="flex items-center gap-3 px-4 py-3 rounded-2xl font-semibold text-sm transition-all {{ request()->routeIs('admin.accounts') ? '' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-100/70' }}">
                            <x-heroicon-o-key class="w-5 h-5 shrink-0" />
                            <span data-sidebar-label>Admin Accounts</span>
                        </a>
                    @endif
                </nav>

                {{-- Desktop-only collapse toggle --}}
                <button id="admin-sidebar-toggle-btn" type="button"
                    class="hidden lg:flex items-center justify-center gap-2 w-full py-2.5 rounded-2xl text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 transition-all">
                    <x-heroicon-o-chevron-double-left id="admin-sidebar-toggle-icon" class="w-4 h-4 shrink-0 transition-transform duration-300" />
                    <span data-sidebar-label>Collapse</span>
                </button>
            </div>

            {{-- Logout --}}
            <div class="pt-4 border-t border-slate-100 mt-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Logout" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl font-semibold text-sm text-rose-600 hover:bg-rose-50 transition-all">
                        <x-heroicon-o-logout class="w-5 h-5 shrink-0" />
                        <span data-sidebar-label>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- MAIN --}}
        <div id="admin-main-wrap" class="flex-1 flex flex-col min-w-0 w-full lg:pl-72 transition-all duration-300 ease-in-out">

            {{-- Mobile top bar --}}
            <header class="lg:hidden bg-white border-b border-slate-200/80 px-4 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm w-full">
                <button id="admin-open-sidebar-btn" type="button" class="p-2 text-slate-600 hover:text-indigo-600 rounded-xl focus:outline-none bg-slate-100/80">
                    <x-heroicon-o-menu class="w-6 h-6" />
                </button>
                <span class="font-extrabold text-slate-900 text-lg tracking-tight">Admin Panel</span>
                <div class="w-9"></div>
            </header>

            {{-- Desktop top bar --}}
            <header class="hidden lg:flex items-center justify-end px-4 sm:px-6 lg:px-8 pt-6 pb-2 w-full max-w-7xl mx-auto gap-4">
                <div class="flex items-center gap-4 shrink-0">
                    <span class="text-xs font-semibold text-slate-500">{{ now()->format('M d, Y') }}</span>
                    <div class="flex items-center gap-2.5">
                        <div class="h-9 w-9 rounded-full text-white font-extrabold text-xs flex items-center justify-center shrink-0" style="background-color: var(--brand-primary);">
                            {{ strtoupper(substr(auth()->user()->name ?? 'Admin', 0, 1) . substr(strrchr(' ' . (auth()->user()->name ?? 'Admin'), ' '), 1, 1)) }}
                        </div>
                        <span class="text-sm font-bold text-slate-700">{{ auth()->user()->name ?? 'Admin' }}</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 min-w-0 w-full p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar    = document.getElementById('admin-sidebar');
            const overlay    = document.getElementById('admin-sidebar-overlay');
            const openBtn    = document.getElementById('admin-open-sidebar-btn');
            const closeBtn   = document.getElementById('admin-close-sidebar-btn');
            const mainWrap   = document.getElementById('admin-main-wrap');
            const toggleBtn  = document.getElementById('admin-sidebar-toggle-btn');
            const toggleIcon = document.getElementById('admin-sidebar-toggle-icon');
            const brandRow   = document.getElementById('admin-brand-row');

            const COLLAPSED_KEY = 'adminSidebarCollapsed';

            // --- Mobile drawer (unaffected by desktop collapse) ---
            function openSidebar() {
                sidebar.classList.remove('-translate-x-[calc(100%+1rem)]');
                overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-[calc(100%+1rem)]');
                overlay.classList.add('hidden');
            }

            if (openBtn) openBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (overlay) overlay.addEventListener('click', closeSidebar);

            // --- Desktop collapse/expand ---
            function setCollapsed(collapsed) {
                sidebar.classList.toggle('lg:w-20', collapsed);
                sidebar.classList.toggle('lg:w-64', !collapsed);
                mainWrap.classList.toggle('lg:pl-28', collapsed);
                mainWrap.classList.toggle('lg:pl-72', !collapsed);
                brandRow.classList.toggle('lg:justify-center', collapsed);
                brandRow.classList.toggle('lg:px-0', collapsed);

                const brandLink = document.getElementById('admin-brand-link');
                if (brandLink) brandLink.classList.toggle('lg:justify-center', collapsed);

                document.querySelectorAll('[data-sidebar-label]').forEach((el) => {
                    el.classList.toggle('lg:hidden', collapsed);
                });

                document.querySelectorAll('[data-sidebar-nav-item]').forEach((el) => {
                    el.classList.toggle('lg:justify-center', collapsed);
                    el.classList.toggle('lg:px-0', collapsed);
                });

                if (toggleIcon) toggleIcon.classList.toggle('rotate-180', collapsed);

                localStorage.setItem(COLLAPSED_KEY, collapsed ? '1' : '0');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', () => {
                    const isCollapsed = sidebar.classList.contains('lg:w-20');
                    setCollapsed(!isCollapsed);
                });
            }

            // Restore saved preference on desktop only — mobile always starts
            // as the full-width drawer regardless of what was saved.
            if (window.matchMedia('(min-width: 1024px)').matches) {
                setCollapsed(localStorage.getItem(COLLAPSED_KEY) === '1');
            }
        });
    </script>

    @livewireScripts
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>