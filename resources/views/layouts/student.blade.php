<!DOCTYPE html>
<html lang="en" class="min-h-screen bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Behavioral Budget System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/chart.min.js') }}"></script>
    @livewireStyles
</head>
<body class="min-h-screen font-sans antialiased text-slate-900">

    <header class="bg-white border-b border-slate-200/80 sticky top-0 z-50 shadow-[0_2px_8px_-4px_rgba(0,0,0,0.05)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                
                <div class="flex items-center gap-6">
                    <a href="{{ route('student.dashboard') }}" class="flex items-center gap-2.5 group">
                        <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center text-white shadow-sm shadow-indigo-600/20 transition-transform group-hover:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 10a3 3 0 0 1 5-2.24A3 3 0 0 1 17 10"></path>
                                <circle cx="9" cy="10" r="0.75" fill="currentColor"></circle>
                                <circle cx="15" cy="10" r="0.75" fill="currentColor"></circle>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 12c0-1.5 1.5-3 4-3h8c2.5 0 4 1.5 4 3 0 3.5-3.5 6-8 6s-8-2.5-8-6z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l3-1.5 3 1.5"></path>
                            </svg>
                        </div>
                        <span class="font-black tracking-tight text-slate-900 text-sm sm:text-base group-hover:text-indigo-600 transition-colors">BehavioralBudget</span>
                    </a>
                    
                    <nav class="hidden sm:flex space-x-1">
                        <a href="{{ route('student.dashboard') }}" 
                           class="px-3 py-2 rounded-xl text-xs font-extrabold transition-all {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50/80 hover:text-slate-900' }}">
                            Dashboard
                        </a>
                        <a href="{{ route('student.forecast') }}" 
                           class="px-3 py-2 rounded-xl text-xs font-extrabold transition-all {{ request()->routeIs('student.forecast') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50/80 hover:text-slate-900' }}">
                            Spending Forecast
                        </a>
                        <a href="{{ route('student.simulation') }}" 
                           class="px-3 py-2 rounded-xl text-xs font-extrabold transition-all {{ request()->routeIs('student.simulation') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50/80 hover:text-slate-900' }}">
                            Simulator
                        </a>
                        <a href="{{ route('student.goals') }}" 
                           class="px-3 py-2 rounded-xl text-xs font-extrabold transition-all {{ request()->routeIs('student.goals') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500 hover:bg-slate-50/80 hover:text-slate-900' }}">
                            Goals
                        </a>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <span class="hidden md:inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 uppercase tracking-wider border border-slate-200/40">
                        Student Account
                    </span>
                    
                    <livewire:student.notification-center />
                    
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200/80 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200/60 transition-all">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="flex sm:hidden pb-3 pt-0.5 gap-1.5 overflow-x-auto scrollbar-none border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}" class="px-3 py-1.5 rounded-lg text-xs font-extrabold whitespace-nowrap transition-colors {{ request()->routeIs('student.dashboard') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500' }}">
                    Dashboard
                </a>
                <a href="{{ route('student.forecast') }}" class="px-3 py-1.5 rounded-lg text-xs font-extrabold whitespace-nowrap transition-colors {{ request()->routeIs('student.forecast') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500' }}">
                    Spending Forecast
                </a>
                <a href="{{ route('student.simulation') }}" class="px-3 py-1.5 rounded-lg text-xs font-extrabold whitespace-nowrap transition-colors {{ request()->routeIs('student.simulation') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500' }}">
                    Simulator
                </a>
                <a href="{{ route('student.goals') }}" class="px-3 py-1.5 rounded-lg text-xs font-extrabold whitespace-nowrap transition-colors {{ request()->routeIs('student.goals') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500' }}">
                    Goals
                </a>
            </div>
        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    @livewireScripts
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>