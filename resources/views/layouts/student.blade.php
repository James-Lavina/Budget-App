<!DOCTYPE html>
<html lang="en" class="min-h-screen bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Behavioral Budget System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/chart.min.js') }}"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
                    
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button @click="open = !open" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200/80 hover:bg-slate-100 transition-all select-none">
                            <span class="truncate max-w-[100px] sm:max-w-[140px]">{{ auth()->user()->name }}</span>
                            <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform text-slate-400" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" 
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white border border-slate-200 rounded-xl shadow-xl z-50 py-1"
                             style="display: none;">
                            
                            <a href="{{ route('student.profile') }}" class="flex items-center px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                                User Profile
                            </a>
                            
                            <a href="{{ route('student.settings') }}" class="flex items-center px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-indigo-600 transition-colors">
                                Budget Settings
                            </a>

                            <hr class="border-slate-100 my-1">

                            <form method="POST" action="{{ route('logout') }}" class="block w-full">
                                @csrf
                                <button type="submit" class="flex w-full items-center px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50/50 transition-colors">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>

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
                <a href="{{ route('student.profile') }}" class="px-3 py-1.5 rounded-lg text-xs font-extrabold whitespace-nowrap transition-colors {{ request()->routeIs('student.profile') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500' }}">
                    Profile
                </a>
                <a href="{{ route('student.settings') }}" class="px-3 py-1.5 rounded-lg text-xs font-extrabold whitespace-nowrap transition-colors {{ request()->routeIs('student.settings') ? 'bg-indigo-50 text-indigo-600' : 'text-slate-500' }}">
                    Settings
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