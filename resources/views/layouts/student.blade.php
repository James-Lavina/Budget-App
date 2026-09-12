<!DOCTYPE html>
<html lang="en" class="h-full bg-[#f4f6fa]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appSettings->application_name }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/chart.min.js') }}"></script>
    <style>
        :root {
            --brand: {{ $appSettings->primary_color }};
            --brand-rgb: {{ $appSettings->primaryColorRgb() }};
            --brand-dark: {{ $appSettings->primaryColorDark() }};
        }
    </style>
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-[#f4f6fa]">

    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity"></div>

    <div class="min-h-full flex flex-col lg:flex-row w-full">

        <aside id="sidebar" class="fixed inset-y-3 left-3 z-50 w-64 bg-white border border-slate-200/70 rounded-3xl p-5 flex flex-col justify-between transform -translate-x-[calc(100%+1rem)] lg:translate-x-0 transition-all duration-300 ease-in-out lg:fixed lg:top-4 lg:bottom-4 lg:left-4 lg:z-30 shrink-0 overflow-y-auto overflow-x-hidden shadow-sm">

            <div class="space-y-6">
                <div id="brand-row" class="flex items-center justify-between px-2 pt-1">
                    <a href="{{ route('student.dashboard') }}" id="brand-link" class="flex items-center gap-3 min-w-0">
                        <div class="h-10 w-10 rounded-2xl flex items-center justify-center text-white shrink-0 overflow-hidden bg-[var(--brand)] shadow-md shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.3)]">
                            @if($appSettings->logo_path)
                                <img src="{{ Storage::url($appSettings->logo_path) }}"
                                     alt="{{ $appSettings->application_name }}"
                                     class="h-full w-full object-cover">
                            @else
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                            @endif
                        </div>
                        <span data-sidebar-label class="font-black text-slate-900 text-xl tracking-tight truncate">{{ $appSettings->application_name }}</span>
                    </a>
                    <button id="close-sidebar-btn" type="button" class="lg:hidden p-1.5 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <nav class="space-y-1.5">
                    <x-nav-link route="student.dashboard" title="Dashboard" data-sidebar-nav-item>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span data-sidebar-label>Dashboard</span>
                    </x-nav-link>

                    <x-nav-link route="student.expenses.create" title="Add Expense" data-sidebar-nav-item>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span data-sidebar-label>Add Expense</span>
                    </x-nav-link>

                    <x-nav-link route="student.receipt-scanner" title="Receipt Scanner" data-sidebar-nav-item>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
                        <span data-sidebar-label>Receipt Scanner</span>
                    </x-nav-link>

                    <x-nav-link route="student.forecast" title="Spending Forecast" data-sidebar-nav-item>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        <span data-sidebar-label>Spending Forecast</span>
                    </x-nav-link>

                    <x-nav-link route="student.goals" title="Savings Goal" data-sidebar-nav-item>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span data-sidebar-label>Savings Goal</span>
                    </x-nav-link>

                    <x-nav-link route="student.simulation" title="Purchase Simulator" data-sidebar-nav-item>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span data-sidebar-label>Purchase Simulator</span>
                    </x-nav-link>

                    <x-nav-link route="student.notifications" title="Notifications" data-sidebar-nav-item class="justify-between">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/></svg>
                            <span data-sidebar-label>Notifications</span>
                        </div>
                        <span data-sidebar-label>
                            <livewire:student.notification-badge />
                        </span>
                    </x-nav-link>

                    <x-nav-link route="student.profile" title="Profile" data-sidebar-nav-item>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span data-sidebar-label>Profile</span>
                    </x-nav-link>

                    <x-nav-link route="student.settings" title="Settings" data-sidebar-nav-item>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span data-sidebar-label>Settings</span>
                    </x-nav-link>
                </nav>

                <button id="sidebar-toggle-btn" type="button"
                    class="hidden lg:flex items-center justify-center gap-2 w-full py-2.5 rounded-2xl text-xs font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 transition-all">
                    <svg id="sidebar-toggle-icon" class="w-4 h-4 shrink-0 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                    </svg>
                    <span data-sidebar-label>Collapse</span>
                </button>
            </div>

            <div class="pt-4 border-t border-slate-100 mt-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    {{-- FIX: added data-sidebar-nav-item — this button was
                         missing the attribute the collapse JS uses to apply
                         lg:justify-center + lg:px-0. Every other sidebar
                         item has it, so when collapsed, this was the only
                         icon left un-centered (still carrying its original
                         px-4 + default left-justified flex layout). --}}
                    <button type="submit" title="Logout" data-sidebar-nav-item class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl font-semibold text-sm text-rose-600 hover:bg-rose-50 transition-all">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                        <span data-sidebar-label>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <div id="main-wrap" class="flex-1 flex flex-col min-w-0 w-full lg:pl-72 transition-all duration-300 ease-in-out">

            <header class="lg:hidden bg-white border-b border-slate-200/80 px-4 py-3 flex items-center justify-between sticky top-0 z-30 shadow-sm w-full">
                <div class="flex items-center gap-3">
                    <button id="open-sidebar-btn" type="button" class="p-2 text-slate-600 hover:text-[var(--brand)] rounded-xl focus:outline-none bg-slate-100/80">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2">
                        <div class="h-8 w-8 rounded-xl flex items-center justify-center text-white shrink-0 overflow-hidden bg-[var(--brand)] shadow-md shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.3)]">
                            @if($appSettings->logo_path)
                                <img src="{{ Storage::url($appSettings->logo_path) }}" alt="{{ $appSettings->application_name }}" class="h-full w-full object-cover">
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                            @endif
                        </div>
                        <span class="font-extrabold text-slate-900 text-lg tracking-tight">{{ $appSettings->application_name }}</span>
                    </a>
                </div>
                <div class="flex items-center gap-2">
                    <livewire:student.notification-center />
                </div>
            </header>

            <main class="flex-1 min-w-0 w-full p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
                @include('partials.test-fast-forward-banner')
                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar    = document.getElementById('sidebar');
            const overlay    = document.getElementById('sidebar-overlay');
            const openBtn    = document.getElementById('open-sidebar-btn');
            const closeBtn   = document.getElementById('close-sidebar-btn');
            const mainWrap   = document.getElementById('main-wrap');
            const toggleBtn  = document.getElementById('sidebar-toggle-btn');
            const toggleIcon = document.getElementById('sidebar-toggle-icon');
            const brandRow   = document.getElementById('brand-row');
            const brandLink  = document.getElementById('brand-link');
            const COLLAPSED_KEY = 'studentSidebarCollapsed';

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

            function setCollapsed(collapsed) {
                sidebar.classList.toggle('lg:w-20', collapsed);
                sidebar.classList.toggle('lg:w-64', !collapsed);
                mainWrap.classList.toggle('lg:pl-28', collapsed);
                mainWrap.classList.toggle('lg:pl-72', !collapsed);
                brandRow.classList.toggle('lg:justify-center', collapsed);
                brandRow.classList.toggle('lg:px-0', collapsed);
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
            if (window.matchMedia('(min-width: 1024px)').matches) {
                setCollapsed(localStorage.getItem(COLLAPSED_KEY) === '1');
            }
        });
    </script>
    @livewireScripts
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>