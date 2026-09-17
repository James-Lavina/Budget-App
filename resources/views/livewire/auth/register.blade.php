<div>
    <style>
        :root {
            --brand: {{ $primary }};
            --brand-dark: {{ $primaryDark }};
            --brand-darker: {{ $primaryDarker }};
            --brand-light: {{ $primaryLight }};
        }
        .brand-gradient { background-image: linear-gradient(135deg, var(--brand), var(--brand), var(--brand-darker)); }
        .brand-icon-text { color: var(--brand); }
        .brand-bg { background-color: var(--brand); }
        .brand-btn { background-color: var(--brand); }
        .brand-btn:hover { background-color: var(--brand-dark); }
        .brand-text { color: var(--brand); }
        .brand-link { color: var(--brand); }
        .brand-link:hover { color: var(--brand-dark); }
        .brand-shadow { box-shadow: 0 10px 25px -5px color-mix(in srgb, var(--brand) 30%, transparent); }
        .brand-glow-1 { background-color: color-mix(in srgb, var(--brand) 22%, transparent); }
        .brand-glow-2 { background-color: color-mix(in srgb, var(--brand-darker) 18%, transparent); }
        .brand-focus:focus {
            border-color: var(--brand) !important;
            outline: none;
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand) 18%, transparent);
        }
    </style>

    <div class="min-h-screen bg-slate-100/80 flex items-center justify-center p-3.5 sm:p-6 lg:p-8 font-sans antialiased text-slate-800">

        <!-- Split Card Container -->
        <div class="max-w-5xl w-full bg-white rounded-[2rem] sm:rounded-[2.5rem] shadow-2xl shadow-indigo-950/10 border border-slate-100 grid grid-cols-1 lg:grid-cols-12 overflow-hidden">

            <!-- LEFT COLUMN: Brand Hero Side -->
            <div class="lg:col-span-6 brand-gradient p-6 sm:p-10 text-white flex flex-col justify-between space-y-6 sm:space-y-8 relative overflow-hidden">

                <!-- Ambient Glow Effects -->
                <div class="absolute -top-12 -right-12 w-64 h-64 brand-glow-1 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-64 h-64 brand-glow-2 rounded-full blur-3xl pointer-events-none"></div>

                <div class="space-y-5 sm:space-y-6 relative z-10">
                    <!-- Brand Header -->
                    <div class="flex items-center gap-3 min-w-0">
                        @if($appSettings->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($appSettings->logo_path) }}"
                                 alt="{{ $appSettings->application_name }}"
                                 class="h-10 w-10 rounded-2xl object-cover shadow-md shrink-0">
                        @else
                            <div class="h-10 w-10 bg-white brand-icon-text rounded-2xl flex items-center justify-center shadow-md font-black shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z" />
                                </svg>
                            </div>
                        @endif
                        <span class="text-xl font-bold tracking-tight text-white truncate">
                            <a href="/" class="hover:opacity-90 transition-opacity">{{ $appSettings->application_name }}</a>
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
                        <p class="text-xs sm:text-sm text-white/85 font-normal leading-relaxed">
                            {{ $appSettings->application_name }} turns your weekly allowance into clear, encouraging next steps.
                        </p>
                    </div>

                    <!-- Mascot & Safe-To-Spend Card -->
                    <div class="relative bg-white/95 rounded-3xl p-5 sm:p-6 shadow-xl border border-white/20 flex flex-col items-center justify-center min-h-[180px] sm:min-h-[220px]">
                        @if($heroSvgMarkup)
                            <div class="w-32 h-32 sm:w-40 sm:h-40 drop-shadow-sm">
                                {!! $heroSvgMarkup !!}
                            </div>
                        @else
                            <img
                                src="{{ asset('images/undraw_budgeting_klon.svg') }}"
                                alt="Student illustration"
                                class="w-32 h-32 sm:w-40 sm:h-40 object-contain drop-shadow-sm"
                            >
                        @endif

                        <!-- Floating Badge -->
                        <div class="absolute bottom-3 right-3 bg-white border border-slate-100 rounded-2xl p-2.5 shadow-lg flex flex-col text-left">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Today's safe-to-spend</span>
                            <span class="text-xs sm:text-sm font-black text-emerald-600">₱280</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Features -->
                <div class="flex items-center gap-6 text-xs font-semibold text-white/90 pt-4 relative z-10 border-t border-white/10">
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

            <!-- RIGHT COLUMN: Signup Form Side -->
            <div class="lg:col-span-6 p-6 sm:p-10 lg:p-12 flex flex-col justify-center bg-white">
                <div class="max-w-md w-full mx-auto space-y-6">

                    <!-- Form Header -->
                    <div class="space-y-1">
                        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                            Create your account
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-500 font-medium">
                            Start tracking your student budget in under a minute.
                        </p>
                    </div>

                    <!-- Signup Form -->
                    <form wire:submit.prevent="registerUser" class="space-y-4">

                        <!-- Full Name Field -->
                        <div class="space-y-1.5">
                            <label for="name" class="block text-xs font-bold text-slate-700">
                                Full name
                            </label>
                            <div>
                                <input id="name" type="text" wire:model.defer="name" placeholder="John Doe"
                                    class="block w-full rounded-2xl px-4 py-3 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none sm:text-sm transition-all duration-200
                                    @error('name') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/30 @else border-slate-200 brand-focus text-slate-900 @enderror">
                            </div>
                            @error('name')
                                <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <!-- Email Field -->
                        <div class="space-y-1.5">
                            <label for="email" class="block text-xs font-bold text-slate-700">
                                Email address
                            </label>
                            <div>
                                <input id="email" type="email" wire:model.defer="email" placeholder="you@school.edu"
                                    class="block w-full rounded-2xl px-4 py-3 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none sm:text-sm transition-all duration-200
                                    @error('email') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/30 @else border-slate-200 brand-focus text-slate-900 @enderror">
                            </div>
                            @error('email')
                                <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <!-- School Field -->
                        <div class="space-y-1.5">
                            <label for="school" class="block text-xs font-bold text-slate-700">
                                School <span class="font-normal text-slate-400">(optional)</span>
                            </label>
                            <div>
                                <input id="school" type="text" wire:model.defer="school" placeholder="e.g., University of Santo Tomas"
                                    class="block w-full rounded-2xl px-4 py-3 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none sm:text-sm transition-all duration-200
                                    @error('school') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/30 @else border-slate-200 brand-focus text-slate-900 @enderror">
                            </div>
                            @error('school')
                                <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <!-- Password and Confirm Password Row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">

                            <!-- Password Field -->
                            <div class="space-y-1.5">
                                <label for="password" class="block text-xs font-bold text-slate-700">
                                    Password
                                </label>
                                <div class="relative">
                                    <input
                                        id="password"
                                        type="password"
                                        wire:model.defer="password"
                                        placeholder="8+ characters"
                                        class="block w-full rounded-2xl px-4 py-3 pr-11 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none sm:text-sm transition-all duration-200
                                        @error('password')
                                            border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/30
                                        @else
                                            border-slate-200 brand-focus text-slate-900
                                        @enderror"
                                    >

                                    <button
                                        type="button"
                                        onclick="togglePassword('password', 'password-eye-open', 'password-eye-closed')"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:brand-text transition-colors"
                                        aria-label="Show password"
                                    >
                                        <svg id="password-eye-open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6-9.75-6-9.75-6z"/>
                                            <circle cx="12" cy="12" r="2.5"/>
                                        </svg>

                                        <svg id="password-eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 3l18 18M10.58 10.58a2 2 0 102.83 2.83M9.88 4.24A10.2 10.2 0 0112 4c6 0 10 8 10 8a18.2 18.2 0 01-3.17 4.33M6.61 6.61C3.93 8.63 2 12 2 12s4 8 10 8a9.83 9.83 0 004.39-1.03"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <!-- Confirm Password Field -->
                            <div class="space-y-1.5">
                                <label for="password_confirmation" class="block text-xs font-bold text-slate-700">
                                    Confirm password
                                </label>
                                <div class="relative">
                                    <input
                                        id="password_confirmation"
                                        type="password"
                                        wire:model.defer="password_confirmation"
                                        placeholder="Repeat password"
                                        class="block w-full rounded-2xl px-4 py-3 pr-11 bg-slate-50 border border-slate-200 placeholder-slate-400 focus:bg-white focus:outline-none brand-focus sm:text-sm text-slate-900 transition-all duration-200"
                                    >

                                    <button
                                        type="button"
                                        onclick="togglePassword('password_confirmation', 'confirm-eye-open', 'confirm-eye-closed')"
                                        class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:brand-text transition-colors"
                                        aria-label="Show confirm password"
                                    >
                                        <svg id="confirm-eye-open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6-9.75-6-9.75-6z"/>
                                            <circle cx="12" cy="12" r="2.5"/>
                                        </svg>

                                        <svg id="confirm-eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 3l18 18M10.58 10.58a2 2 0 102.83 2.83M9.88 4.24A10.2 10.2 0 0112 4c6 0 10 8 10 8a18.2 18.2 0 004.39-1.03"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit"
                                    {{ $lockoutSeconds > 0 ? 'disabled' : '' }}
                                    wire:loading.attr="disabled"
                                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-bold text-white brand-btn active:scale-[0.99] transition-all duration-200 brand-shadow disabled:opacity-50 disabled:cursor-not-allowed">

                                @if($lockoutSeconds > 0)
                                    <span>Too many attempts. Try again in {{ $lockoutSeconds }}s</span>
                                @else
                                    <span wire:loading.remove class="inline-flex items-center gap-2">
                                        <span>Create Account</span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                                    </span>
                                    <span wire:loading class="inline-flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Creating account...</span>
                                    </span>
                                @endif
                            </button>
                        </div>
                    </form>

                    <!-- Footer Link -->
                    <p class="text-center text-xs text-slate-500 pt-2">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-bold brand-link transition-colors">
                            Log in
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, openIconId, closedIconId) {
            const input = document.getElementById(inputId);
            const openIcon = document.getElementById(openIconId);
            const closedIcon = document.getElementById(closedIconId);

            if (input.type === 'password') {
                input.type = 'text';

                openIcon.classList.add('hidden');
                closedIcon.classList.remove('hidden');
            } else {
                input.type = 'password';

                openIcon.classList.remove('hidden');
                closedIcon.classList.add('hidden');
            }
        }
    </script>
</div>