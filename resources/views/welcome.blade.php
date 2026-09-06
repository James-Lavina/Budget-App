<?php
    $appSettings = \App\Models\AppSetting::current();

    $primary = $appSettings->primary_color ?: '#4f39fa';

    // Simple hex shade/tint helpers — no JS, no external package.
    // darken/lighten by mixing toward black/white by $pct (0-1).
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

    $primaryDark   = bw_shade($primary, 0.15, false); // hover states
    $primaryLight  = bw_shade($primary, 0.92, true);  // pill/badge backgrounds
    $primaryLight2 = bw_shade($primary, 0.85, true);  // slightly stronger tint

    // --- Recolor the hero illustration ---
    // <img src="*.svg"> can't be recolored with CSS (browsers block cross-file
    // style bleed). The only way is to inline the SVG's raw markup and swap its
    // fill hex directly. This export's theme color is #4F46E5 (Tailwind
    // indigo-600) — every accent shape (laptop, bars, dots, badge) uses it,
    // while skin tones (#ed9da0), dark navy (#090814), and neutral grays
    // (#e6e6e6/#d6d6e3/#f0f0f0/#3f3d56) are left alone on purpose.
    $heroSvgMarkup = null;
    $heroSvgPath = public_path('images/undraw_budgeting_klon.svg');
    if (is_file($heroSvgPath)) {
        $svg = file_get_contents($heroSvgPath);
        $heroThemeHexCandidates = ['#4F46E5', '#4f46e5'];
        $svg = str_replace($heroThemeHexCandidates, $primary, $svg);
        // make the inlined <svg> fill its wrapper the same way the old <img> did
        $svg = preg_replace('/<svg([^>]*)>/', '<svg$1 preserveAspectRatio="xMidYMid meet" style="width:100%;height:100%;">', $svg, 1);
        $heroSvgMarkup = $svg;
    }
?>
<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $appSettings->application_name }}</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <style>
      :root {
          --brand: {{ $primary }};
          --brand-dark: {{ $primaryDark }};
          --brand-light: {{ $primaryLight }};
          --brand-light-2: {{ $primaryLight2 }};
      }
      .brand-bg { background-color: var(--brand); }
      .brand-btn { background-color: var(--brand); }
      .brand-btn:hover { background-color: var(--brand-dark); }
      .brand-text { color: var(--brand); }
      .brand-border { border-color: var(--brand); }
      .brand-bg-light { background-color: var(--brand-light); }
      .brand-bg-light-2 { background-color: var(--brand-light-2); }
      .brand-shadow { box-shadow: 0 10px 25px -5px color-mix(in srgb, var(--brand) 30%, transparent); }
      .brand-shadow-lg { box-shadow: 0 20px 40px -10px color-mix(in srgb, var(--brand) 35%, transparent); }
      .brand-ring-glow { background-color: color-mix(in srgb, var(--brand) 22%, transparent); }
      .brand-ring-glow-2 { background-color: color-mix(in srgb, var(--brand) 18%, transparent); }
  </style>
</head>
<body class="min-h-screen bg-slate-50/60 font-sans antialiased text-slate-800">
  <!-- NAVBAR -->
  <header class="sticky top-0 z-50 bg-slate-50/80 backdrop-blur-md border-b border-slate-200/50">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex items-center justify-between h-20">

              <!-- Brand Logo -->
              <a href="/" class="flex items-center gap-3 group min-w-0">
                  @if($appSettings->logo_path)
                      <img src="{{ \Illuminate\Support\Facades\Storage::url($appSettings->logo_path) }}"
                           alt="{{ $appSettings->application_name }}"
                           class="h-10 w-10 rounded-2xl object-cover shadow-md group-hover:scale-105 transition-transform shrink-0">
                  @else
                      <div class="h-10 w-10 brand-bg text-white rounded-2xl flex items-center justify-center shadow-md group-hover:scale-105 transition-transform shrink-0">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 100 4 2 2 0 000-4z" />
                          </svg>
                      </div>
                  @endif
                  <span class="text-xl font-bold text-slate-900 tracking-tight truncate">
                      {{ $appSettings->application_name }}
                  </span>
              </a>
              <!-- Navigation Links -->
              <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                  <a href="#features" class="hover:brand-text transition-colors">Features</a>
                  <a href="#how-it-works" class="hover:brand-text transition-colors">How It Works</a>
                  <a href="{{ route('login') }}" class="hover:brand-text transition-colors">Log in</a>
              </nav>
              <!-- CTA Button -->
              <div>
                  <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-2.5 rounded-full text-sm font-semibold text-white brand-btn active:scale-95 transition-all brand-shadow">
                      Get Started
                  </a>
              </div>
          </div>
      </div>
  </header>

  <!-- HERO SECTION -->
  <section class="pt-8 pb-16 sm:py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
      <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">

          <!-- Hero Left Content -->
          <div class="lg:col-span-6 space-y-6 text-center lg:text-left" data-aos="fade-right" data-aos-delay="100">

              <!-- Pill Tag -->
              <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full brand-bg-light border brand-border/20 text-xs font-semibold" style="color: var(--brand-dark); border-color: color-mix(in srgb, var(--brand) 25%, transparent);">
                  <svg class="w-3.5 h-3.5" style="color: var(--brand);" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.57l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.57l7-10a1 1 0 011.12-.384z"/>
                  </svg>
                  Made for weekly allowances, not monthly paychecks
              </div>
              <!-- Main Heading -->
              <h1 class="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.12]">
                  Manage Your Weekly Allowance <span class="brand-text">Smarter.</span>
              </h1>
              <!-- Subtitle -->
              <p class="text-base sm:text-lg text-slate-500 font-normal leading-relaxed max-w-lg mx-auto lg:mx-0">
                  Track expenses, predict spending, and build better financial habits—all in one friendly place.
              </p>
              <!-- Dual CTA Buttons -->
              <div class="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3">
                  <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl text-sm font-semibold text-white brand-btn active:scale-95 brand-shadow-lg transition">
                      <span>Get Started</span>
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                  </a>
                  <a href="#features" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 rounded-2xl text-sm font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200/80 transition">
                      Learn More
                  </a>
              </div>
          </div>

          <!-- Hero Right Graphic -->
          <div class="lg:col-span-6 relative flex items-center justify-center" data-aos="fade-left" data-aos-delay="200">

              <!-- Decorative Ambient Glows -->
              <div class="absolute -top-6 -right-6 w-64 h-64 brand-ring-glow rounded-full blur-3xl pointer-events-none"></div>
              <div class="absolute -bottom-6 -left-6 w-64 h-64 brand-ring-glow-2 rounded-full blur-3xl pointer-events-none"></div>
              <!-- Card Container -->
              <div class="w-full brand-bg-light p-6 sm:p-10 rounded-[2.5rem] border border-white shadow-2xl relative">

                  <div class="bg-white/90 backdrop-blur-md rounded-3xl p-8 shadow-sm border border-slate-100/80 relative flex flex-col items-center justify-center min-h-[360px] space-y-6">

                      <!-- Top Pop-up Speech Bubble -->
                      <div class="absolute -top-5 brand-bg text-white px-5 py-2.5 rounded-2xl brand-shadow-lg text-xs sm:text-sm font-bold flex items-center gap-2 border-2 border-white z-20" data-aos="zoom-in" data-aos-delay="400">
                          <span>✨ "₱300 safe to spend today!"</span>
                      </div>
                      <!-- Local Student SVG Graphic (inlined + recolored to brand) -->
                      <div class="relative flex items-center justify-center my-2">
                          @if($heroSvgMarkup)
                              <div class="w-52 h-52 sm:w-60 sm:h-60 drop-shadow-md">
                                  {!! $heroSvgMarkup !!}
                              </div>
                          @else
                              <img
                                  src="{{ asset('images/undraw_budgeting_klon.svg') }}"
                                  alt="Student graphic"
                                  class="w-52 h-52 sm:w-60 sm:h-60 object-contain drop-shadow-md"
                              >
                          @endif
                      </div>
                      <!-- Floating Expense Chip 1: Coffee -->
                      <div class="absolute top-12 -left-2 sm:-left-4 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-2xl shadow-md border border-slate-100 flex items-center gap-2.5 transform -rotate-3 hover:rotate-0 transition-transform z-10" data-aos="fade-right" data-aos-delay="500">
                          <span class="text-lg">☕</span>
                          <div>
                              <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Expense</p>
                              <p class="text-xs font-black text-slate-800">₱120 Coffee</p>
                          </div>
                      </div>
                      <!-- Floating Expense Chip 2: Books -->
                      <div class="absolute bottom-12 -right-2 sm:-right-4 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-2xl shadow-md border border-slate-100 flex items-center gap-2.5 transform rotate-3 hover:rotate-0 transition-transform z-10" data-aos="fade-left" data-aos-delay="600">
                          <span class="text-lg">📚</span>
                          <div>
                              <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">School</p>
                              <p class="text-xs font-black text-slate-800">₱450 Books</p>
                          </div>
                      </div>
                      <!-- Floating Status Badge: Savings Goal -->
                      <div class="absolute -bottom-4 bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-1.5 rounded-full text-xs font-extrabold shadow-sm flex items-center gap-1.5 z-20" data-aos="zoom-in" data-aos-delay="700">
                          <span>🎯 Weekly Goal: 70% Saved</span>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </section>

  <!-- FEATURES GRID SECTION -->
  <section id="features" class="py-16 sm:py-24 px-4 sm:px-6 lg:px-8">
      <div class="max-w-6xl mx-auto space-y-12">

          <!-- Section Header -->
          <div class="text-center max-w-2xl mx-auto space-y-3" data-aos="fade-up">
              <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">
                  Everything you need to feel in control
              </h2>
              <p class="text-sm sm:text-base text-slate-500 font-medium">
                  Powerful tools wrapped in an interface that never makes money feel scary.
              </p>
          </div>

          <!-- 5-Card Feature Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

              <!-- Card 1 -->
              <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm hover:shadow-md transition-all duration-300 space-y-4" data-aos="fade-up" data-aos-delay="100">
                  <div class="h-12 w-12 brand-bg-light rounded-2xl flex items-center justify-center font-bold" style="color: var(--brand);">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  </div>
                  <h3 class="text-lg font-bold text-slate-900">AI Receipt Scanner</h3>
                  <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
                      Snap a receipt and we auto-fill the store, date, amount, and category effortlessly.
                  </p>
              </div>
              <!-- Card 2 -->
              <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm hover:shadow-md transition-all duration-300 space-y-4" data-aos="fade-up" data-aos-delay="200">
                  <div class="h-12 w-12 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center font-bold">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                  </div>
                  <h3 class="text-lg font-bold text-slate-900">Spending Forecast</h3>
                  <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
                      See if you'll make it to the weekend before you overspend using simple predictive insights.
                  </p>
              </div>
              <!-- Card 3 -->
              <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm hover:shadow-md transition-all duration-300 space-y-4" data-aos="fade-up" data-aos-delay="300">
                  <div class="h-12 w-12 bg-emerald-50 text-emerald-500 rounded-2xl flex items-center justify-center font-bold">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  </div>
                  <h3 class="text-lg font-bold text-slate-900">Savings Goals</h3>
                  <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
                      Set a goal, save a little daily, and celebrate every financial milestone without stress.
                  </p>
              </div>
              <!-- Card 4 -->
              <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm hover:shadow-md transition-all duration-300 space-y-4" data-aos="fade-up" data-aos-delay="100">
                  <div class="h-12 w-12 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center font-bold">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                  </div>
                  <h3 class="text-lg font-bold text-slate-900">Purchase Simulator</h3>
                  <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
                      Wondering if you can afford it? Simulate what-if scenarios before making a real purchase.
                  </p>
              </div>
              <!-- Card 5 -->
              <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm hover:shadow-md transition-all duration-300 space-y-4" data-aos="fade-up" data-aos-delay="200">
                  <div class="h-12 w-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center font-bold">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                  </div>
                  <h3 class="text-lg font-bold text-slate-900">Smart Nudges</h3>
                  <p class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal">
                      Gentle behavioral notifications that keep your spending balanced, right when it matters.
                  </p>
              </div>
          </div>
      </div>
  </section>

  <!-- HOW IT WORKS SECTION -->
  <section id="how-it-works" class="py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-6xl mx-auto">
          <div class="brand-bg rounded-[2.5rem] p-8 sm:p-14 text-white brand-shadow-lg space-y-10" data-aos="fade-up">

              <div class="text-center max-w-xl mx-auto">
                  <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                      Up and running in 3 easy steps
                  </h2>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                  <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-3xl p-6 sm:p-8 space-y-4" data-aos="zoom-in-up" data-aos-delay="100">
                      <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center font-extrabold text-lg" style="color: var(--brand);">
                          1
                      </div>
                      <h3 class="text-lg font-bold">Set your allowance</h3>
                      <p class="text-xs sm:text-sm text-white/80 leading-relaxed font-normal">
                          Tell us your weekly budget. Takes 10 seconds, no bank connections needed.
                      </p>
                  </div>
                  <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-3xl p-6 sm:p-8 space-y-4" data-aos="zoom-in-up" data-aos-delay="200">
                      <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center font-extrabold text-lg" style="color: var(--brand);">
                          2
                      </div>
                      <h3 class="text-lg font-bold">Log expenses fast</h3>
                      <p class="text-xs sm:text-sm text-white/80 leading-relaxed font-normal">
                        Snap a receipt or tap a category — logging takes seconds, not minutes.
                      </p>
                  </div>
                  <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-3xl p-6 sm:p-8 space-y-4" data-aos="zoom-in-up" data-aos-delay="300">
                      <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center font-extrabold text-lg" style="color: var(--brand);">
                          3
                      </div>
                      <h3 class="text-lg font-bold">Stay on track</h3>
                      <p class="text-xs sm:text-sm text-white/80 leading-relaxed font-normal">
                          See your daily safe-to-spend number and build the habit.
                      </p>
                  </div>
              </div>
          </div>
      </div>
  </section>

    <!-- BOTTOM CTA BANNER -->
    <section class="pb-16 sm:pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-white border border-slate-100 rounded-[2.5rem] p-10 sm:p-16 shadow-xl space-y-6"
                data-aos="zoom-in-up"
                data-aos-offset="0"
                data-aos-anchor-placement="top-bottom">
                <h2 class="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight">
                    Ready to take control of your allowance?
                </h2>
                <p class="text-sm sm:text-base text-slate-500 font-medium max-w-xl mx-auto">
                    Set your allowance, track what matters, and always know what's safe to spend — one week at a time.
                </p>
                <div class="pt-2">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl text-sm font-semibold text-white brand-btn active:scale-95 brand-shadow transition">
                        <span>Get Started Free</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </section>

  <!-- FOOTER -->
  <footer class="bg-slate-50 border-t border-slate-200/60 py-10 px-4 sm:px-6 lg:px-8">
      <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-6">

          <!-- Logo -->
          <div class="flex items-center gap-3 min-w-0">
              @if($appSettings->logo_path)
                  <img src="{{ \Illuminate\Support\Facades\Storage::url($appSettings->logo_path) }}"
                       alt="{{ $appSettings->application_name }}"
                       class="h-8 w-8 rounded-xl object-cover shrink-0">
              @else
                  <div class="h-8 w-8 brand-bg text-white rounded-xl flex items-center justify-center font-black text-sm shrink-0">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 100 4 2 2 0 000-4z" />
                      </svg>
                  </div>
              @endif
              <span class="text-lg font-bold text-slate-900 tracking-tight truncate">
                  {{ $appSettings->application_name }}
              </span>
          </div>
          <!-- Clean Footer Links -->
          <nav class="flex items-center gap-6 text-xs font-medium text-slate-500">
              <a href="#features" class="hover:text-slate-900 transition-colors">Features</a>
              <a href="#how-it-works" class="hover:text-slate-900 transition-colors">How It Works</a>
          </nav>
      </div>
  </footer>

  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>