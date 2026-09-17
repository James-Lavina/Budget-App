<?php
    $appSettings = \App\Models\AppSetting::current();

    $primary = $appSettings->primary_color ?: '#4f39fa';

    if (!function_exists('bw_shade')) {
        function bw_shade(string $hex, float $pct, bool $lighten): string
        {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $mix = $lighten ? 255 : 0;
            $r = (int) round($r + ($mix - $r) * $pct);
            $g = (int) round($g + ($mix - $g) * $pct);
            $b = (int) round($b + ($mix - $b) * $pct);
            return sprintf('#%02x%02x%02x', $r, $g, $b);
        }
    }

    $primaryDark   = bw_shade($primary, 0.15, false);
    $primaryDarker = bw_shade($primary, 0.28, false);
    $primaryLight  = bw_shade($primary, 0.92, true);

    $heroSvgMarkup = null;
    $heroSvgPath = public_path('images/undraw_budgeting_klon.svg');
    if (is_file($heroSvgPath)) {
        $svg = file_get_contents($heroSvgPath);
        $heroThemeHexCandidates = ['#4F46E5', '#4f46e5'];
        $svg = str_replace($heroThemeHexCandidates, $primary, $svg);
        $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 preserveAspectRatio="xMidYMid meet" style="width:100%;height:100%;">', $svg, 1);
        $heroSvgMarkup = $svg;
    }
?>

<div class="min-h-screen bg-slate-100/80 flex items-start sm:items-center justify-center p-2 sm:p-4 lg:p-6 font-sans antialiased text-slate-800 overflow-y-auto">

    <style>
        :root {
            --brand: {{ $primary }};
            --brand-dark: {{ $primaryDark }};
            --brand-darker: {{ $primaryDarker }};
            --brand-light: {{ $primaryLight }};
        }
        .brand-gradient { background-image: linear-gradient(135deg, var(--brand), var(--brand), var(--brand-darker)); }
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

    <!-- Split Card Container -->
    <div class="max-w-5xl w-full bg-white rounded-[1.75rem] sm:rounded-[2rem] shadow-2xl shadow-indigo-950/10 border border-slate-100 grid grid-cols-1 lg:grid-cols-12 lg:overflow-hidden lg:max-h-[92vh]">

        <!-- LEFT COLUMN: Brand Hero Side -->
        <div class="lg:col-span-6 brand-gradient p-5 sm:p-7 text-white flex flex-col justify-between space-y-4 sm:space-y-5 relative overflow-hidden">

            <!-- Ambient Glow Effects -->
            <div class="absolute -top-12 -right-12 w-64 h-64 brand-glow-1 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-64 h-64 brand-glow-2 rounded-full blur-3xl pointer-events-none"></div>

            <div class="space-y-3.5 sm:space-y-4 relative z-10">
                <!-- Brand Header — same icon/logo treatment as the admin sidebar -->
                <div class="flex items-center gap-3 min-w-0">
                    <div class="h-9 w-9 rounded-xl flex items-center justify-center text-white shadow-md shrink-0 overflow-hidden brand-bg">
                        @if($appSettings->logo_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($appSettings->logo_path) }}"
                                alt="{{ $appSettings->application_name }}"
                                class="h-full w-full object-cover">
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 100 4 2 2 0 000-4z"/>
                            </svg>
                        @endif
                    </div>
                    <span class="text-lg font-bold tracking-tight text-white truncate">
                        <a href="/" class="hover:opacity-90 transition-opacity">{{ $appSettings->application_name }}</a>
                    </span>
                </div>

                <!-- Pill Tag -->
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-[11px] font-semibold">
                    <span>✨ Simple spending for students</span>
                </div>

                <!-- Main Left Title & Subtitle -->
                <div class="space-y-1.5">
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold tracking-tight leading-tight text-white">
                        Financial confidence starts with one small habit.
                    </h1>
                    <p class="text-xs text-white/85 font-normal leading-relaxed">
                        {{ $appSettings->application_name }} turns your weekly allowance into clear, encouraging next steps.
                    </p>
                </div>

                <!-- Mascot & Safe-To-Spend Card -->
                <div class="relative bg-white/95 rounded-2xl p-4 sm:p-5 shadow-xl border border-white/20 flex flex-col items-center justify-center min-h-[130px] sm:min-h-[150px]">
                    @if($heroSvgMarkup)
                        <div class="w-20 h-20 sm:w-24 sm:h-24 drop-shadow-sm">
                            {!! $heroSvgMarkup !!}
                        </div>
                    @else
                        <img
                            src="{{ asset('images/undraw_budgeting_klon.svg') }}"
                            alt="Student illustration"
                            class="w-20 h-20 sm:w-24 sm:h-24 object-contain drop-shadow-sm"
                        >
                    @endif

                    <!-- Floating Badge -->
                    <div class="absolute bottom-2 right-2 bg-white border border-slate-100 rounded-xl p-2 shadow-lg flex flex-col text-left">
                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">Today's safe-to-spend</span>
                        <span class="text-xs font-black text-emerald-600">₱280</span>
                    </div>
                </div>
            </div>

            <!-- Footer Features -->
            <div class="flex items-center gap-5 text-[11px] font-semibold text-white/90 pt-3 relative z-10 border-t border-white/10">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span>Built for students</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span>Free to start</span>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Login Form Side -->
        <div class="lg:col-span-6 p-5 sm:p-7 lg:p-8 flex flex-col justify-center bg-white">
            <div class="max-w-md w-full mx-auto space-y-4">

                <!-- Form Header -->
                <div class="space-y-0.5">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                        Welcome Back
                    </h2>
                    <p class="text-xs text-slate-500 font-medium">
                        Pick up where you left off — your budget is waiting.
                    </p>
                </div>

                <!-- Livewire Global Authentication Error Banner -->
                @error('auth_failed')
                    <div class="p-3 bg-rose-50 border-l-4 border-rose-500 text-sm text-rose-800 rounded-r-xl flex items-start gap-2.5 shadow-sm">
                        <svg class="h-4 w-4 text-rose-500 shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <span class="font-bold block text-xs">Couldn't sign you in</span>
                            <span class="text-rose-700/90 text-xs block mt-0.5">{{ $message }}</span>
                        </div>
                    </div>
                @enderror

                <!-- Login Form -->
                <form wire:submit.prevent="loginUser" class="space-y-3">

                    <!-- Email Field -->
                    <div class="space-y-1">
                        <label for="email" class="block text-xs font-bold text-slate-700">
                            Email address
                        </label>
                        <div>
                            <input id="email" type="email" wire:model.lazy="email" placeholder="you@school.edu"
                                class="block w-full rounded-xl px-4 py-2.5 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none sm:text-sm transition-all duration-200
                                @error('email') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/30 @else border-slate-200 brand-focus text-slate-900 @enderror">
                        </div>
                        @error('email')
                            <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-xs font-bold text-slate-700">
                                Password
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-xs font-bold brand-link transition-colors">
                                    Forgot password?
                                </a>
                            @endif
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                type="password"
                                wire:model.lazy="password"
                                placeholder="Enter your password"
                                class="block w-full rounded-xl px-4 py-2.5 pr-12 bg-slate-50 border placeholder-slate-400 focus:bg-white focus:outline-none sm:text-sm transition-all duration-200
                                @error('password')
                                    border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 bg-rose-50/30
                                @else
                                    border-slate-200 brand-focus text-slate-900
                                @enderror"
                            >

                            <button
                                type="button"
                                onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 flex items-center px-4 text-slate-400 hover:brand-text transition-colors"
                                aria-label="Show password"
                            >
                                <svg id="eye-open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.25 12s3.75-6 9.75-6 9.75 6 9.75 6-3.75 6-9.75 6-9.75-6-9.75-6z" />
                                    <circle cx="12" cy="12" r="2.5" />
                                </svg>

                                <svg id="eye-closed" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M3 3l18 18M10.58 10.58a2 2 0 102.83 2.83M9.88 4.24A10.2 10.2 0 0112 4c6 0 10 8 10 8a18.2 18.2 0 01-3.17 4.33M6.61 6.61C3.93 8.63 2 12 2 12s4 8 10 8a9.83 9.83 0 004.39-1.03" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <span class="text-xs text-rose-600 mt-1 font-medium flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-1.5">
                        <button type="submit"
                                {{ $lockoutSeconds > 0 ? 'disabled' : '' }}
                                wire:loading.attr="disabled"
                                class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white brand-btn active:scale-[0.99] transition-all duration-200 brand-shadow disabled:opacity-50 disabled:cursor-not-allowed">

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
                <p class="text-center text-xs text-slate-500 pt-1">
                    New to {{ $appSettings->application_name }}?
                    <a href="{{ route('register') }}" class="font-bold brand-link transition-colors">
                        Sign up
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const password = document.getElementById('password');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        if (password.type === 'password') {
            password.type = 'text';

            eyeOpen.classList.add('hidden');
            eyeClosed.classList.remove('hidden');
        } else {
            password.type = 'password';

            eyeOpen.classList.remove('hidden');
            eyeClosed.classList.add('hidden');
        }
    }
</script>