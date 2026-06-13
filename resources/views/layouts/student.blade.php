<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Behavioral Budget System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @livewireStyles
</head>
<body class="h-full font-sans antialiased text-slate-900">

    <!-- Sticky Navigation Navbar Header -->
    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-50 shadow-[0_2px_8px_-4px_rgba(0,0,0,0.05)]">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                
                <!-- Brand Identity and Links -->
                <div class="flex items-center gap-6">
                    <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2.5 group">
                        <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 transition-transform group-hover:scale-95">
                            <!-- Geometric Financial Nodes SVG Icon -->
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <span class="font-black tracking-tight text-slate-900 text-sm sm:text-base group-hover:text-indigo-600 transition-colors">BehavioralBudget</span>
                    </a>
                    
                    <!-- Main Navigation Links (Active Tab Tracking) -->
                    <nav class="hidden sm:flex space-x-1">
                        <a href="{{ route('student.dashboard') }}" 
                           class="px-3 py-2 rounded-xl text-xs font-extrabold transition-all {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50/80 hover:text-slate-900' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('student.forecast') }}" 
                           class="px-3 py-2 rounded-xl text-xs font-extrabold transition-all {{ request()->routeIs('student.forecast') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50/80 hover:text-slate-900' }}">
                            Spending Forecast
                        </a>
                    </nav>
                </div>

                <!-- Session Context Options -->
                <div class="flex items-center gap-3">
                    <span class="hidden md:inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 uppercase tracking-wider border border-slate-200/40">
                        Student Account
                    </span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200/80 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200/60 transition-all">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Mobile View Navigation Row -->
            <div class="flex sm:hidden pb-3 pt-0.5 gap-1.5 overflow-x-auto scrollbar-none border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}" class="px-3 py-1.5 rounded-lg text-xs font-extrabold whitespace-nowrap transition-colors {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500' }}">
                    Dashboard
                </a>
                <a href="{{ route('student.forecast') }}" class="px-3 py-1.5 rounded-lg text-xs font-extrabold whitespace-nowrap transition-colors {{ request()->routeIs('student.forecast') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500' }}">
                    Spending Forecast
                </a>
            </div>
        </div>
    </header>

    <!-- Content Slot Injected Here -->
    <main>
        {{ $slot }}
    </main>

    @livewireScripts
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>