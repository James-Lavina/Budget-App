<div class="min-h-screen bg-slate-100/80 flex items-center justify-center p-3.5 sm:p-6 lg:p-8 font-sans antialiased text-slate-800">
  
    <!-- Split Card Container -->
    <div class="max-w-5xl w-full bg-white rounded-[2rem] sm:rounded-[2.5rem] shadow-2xl shadow-indigo-950/10 border border-slate-100 grid grid-cols-1 lg:grid-cols-12 overflow-hidden">
       
        <!-- LEFT COLUMN: Brand Hero Side -->
        <div class="lg:col-span-6 bg-gradient-to-br from-indigo-600 via-indigo-600 to-indigo-700 p-6 sm:p-10 text-white flex flex-col justify-between space-y-6 sm:space-y-8 relative overflow-hidden">
           
            <!-- Ambient Glow Effects -->
            <div class="absolute -top-12 -right-12 w-64 h-64 bg-indigo-400/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            
            <div class="space-y-5 sm:space-y-6 relative z-10">
                <!-- Brand Header -->
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-white text-indigo-600 rounded-2xl flex items-center justify-center shadow-md font-black">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">
                        <a href="/" class="hover:opacity-90 transition-opacity">SampleName</a>
                    </span>
                </div>

                <!-- Pill Tag -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-xs font-semibold">
                    <span>✨ Simple spending for students</span>
                </div>

                <!-- Main Left Title & Subtitle -->
                <div class="space-y-2 sm:space-y-3">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight text-white">
                        Financial confidence starts with one small habit.
                    </h1>
                    <p class="text-xs sm:text-sm text-indigo-100 font-normal leading-relaxed">
                        SampleName turns your weekly allowance into clear, encouraging next steps.
                    </p>
                </div>

                <!-- Mascot & Safe-To-Spend Card -->
                <div class="relative bg-white/95 rounded-3xl p-5 sm:p-6 shadow-xl border border-white/20 flex flex-col items-center justify-center min-h-[180px] sm:min-h-[220px]">
                    <img
                        src="{{ asset('images/undraw_budgeting_klon.svg') }}"
                        alt="Student illustration"
                        class="w-32 h-32 sm:w-40 sm:h-40 object-contain drop-shadow-sm"
                    >
                   
                    <!-- Floating Badge -->
                    <div class="absolute bottom-3 right-3 bg-white border border-slate-100 rounded-2xl p-2.5 shadow-lg flex flex-col text-left">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Today's safe-to-spend</span>
                        <span class="text-xs sm:text-sm font-black text-emerald-600">₱280</span>
                    </div>
                </div>
            </div>

            <!-- Footer Features -->
            <div class="flex items-center gap-6 text-xs font-semibold text-indigo-100/90 pt-4 relative z-10 border-t border-white/10">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span>Built for students</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span>Free to start</span>
                </div>
            </div>
        </div>
 
        <!-- RIGHT COLUMN: Login Form Side -->
        <div class="lg:col-span-6 p-6 sm:p-10 lg:p-12 flex flex-col justify-center bg-white">
            <div class="max-w-md w-full mx-auto space-y-6">
               
                <!-- Form Header -->
                <div class="space-y-1">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Welcome Back
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 font-medium">
                        Pick up where you left off — your budget is waiting.
                    </p>
                </div>

                <!-- Livewire Global Authentication Error Banner -->
                @error('auth_failed')
                    <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-sm text-rose-800 rounded-r-2xl flex items-start gap-3 shadow-sm">
                        <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <span class="font-bold block text-xs sm:text-sm">Couldn't sign you in</span>
                            <span class="text-rose-700/90 text-xs block mt-0.5">{{ $message }}</span>
                        </div>
                    </div>
                @enderror

                <!-- Login Form -->
                <form wire:submit.prevent="loginUser" class="space-y-4 sm:space-y-5">
                   
                    <!-- Email Field -->
                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-bold text-slate-700">
                            Email address
                        </label>
                        <div>
                            <input id="email" type="email" wire:model.lazy="email" placeholder="you@school.edu"
                                class="block w-full rounded-2xl px-4 py-3 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 sm:text-sm transition-all duration-200
                                @error('email') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-rose-500/20 bg-rose-50/30 @else border-slate-200 focus:border-indigo-600 focus:ring-indigo-600/20 text-slate-900 @enderror">
                        </div>
                        @error('email')
                            <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-xs font-bold text-slate-700">
                                Password
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 transition-colors">
                                    Forgot password?
                                </a>
                            @endif
                        </div>
                        <div>
                            <input id="password" type="password" wire:model.lazy="password" placeholder="Enter your password"
                                class="block w-full rounded-2xl px-4 py-3 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 sm:text-sm transition-all duration-200
                                @error('password') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-rose-500/20 bg-rose-50/30 @else border-slate-200 focus:border-indigo-600 focus:ring-indigo-600/20 text-slate-900 @enderror">
                        </div>
                        @error('password')
                            <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Remember Me Option -->
                    {{-- <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" wire:model="remember"
                                class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 transition-colors">
                            <span class="text-xs font-semibold text-slate-600">Remember me on this device</span>
                        </label>
                    </div> --}}

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit"
                                {{ $lockoutSeconds > 0 ? 'disabled' : '' }}
                                wire:loading.attr="disabled"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] transition-all duration-200 shadow-lg shadow-indigo-600/25 disabled:opacity-50 disabled:cursor-not-allowed">
                           
                            @if($lockoutSeconds > 0)
                                <span>Too many attempts. Try again in {{ $lockoutSeconds }}s</span>
                            @else
                                <span wire:loading.remove class="inline-flex items-center gap-2">
                                    <span>Sign In</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                </span>
                                <span wire:loading class="inline-flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Signing in...</span>
                                </span>
                            @endif
                        </button>
                    </div>
                </form>

                <!-- Footer Link -->
                <p class="text-center text-xs text-slate-500 pt-2">
                    New to SampleName?
                    <a href="{{ route('register') }}" class="font-bold text-indigo-600 hover:text-indigo-700 transition-colors">
                        Sign up
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>