<!DOCTYPE html>
<html lang="en" class="h-full scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Web App</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="min-h-screen bg-slate-50/60 font-sans antialiased text-slate-800">
  <!-- NAVBAR -->
  <header class="sticky top-0 z-50 bg-slate-50/80 backdrop-blur-md border-b border-slate-200/50">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div class="flex items-center justify-between h-20">
            
              <!-- Brand Logo -->
              <a href="/" class="flex items-center gap-3 group">
                  <div class="h-10 w-10 bg-indigo-600 text-white rounded-2xl flex items-center justify-center shadow-md shadow-indigo-600/20 group-hover:scale-105 transition-transform">
                      <!-- Wallet Icon -->
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z" />
                      </svg>
                  </div>
                  <span class="text-xl font-bold text-slate-900 tracking-tight">
                      Sample<span class="text-indigo-600">Name</span>
                  </span>
              </a>
              <!-- Navigation Links -->
              <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
                  <a href="#features" class="hover:text-indigo-600 transition-colors">Features</a>
                  <a href="#how-it-works" class="hover:text-indigo-600 transition-colors">How It Works</a>
                  <a href="#comparison" class="hover:text-indigo-600 transition-colors">Why SampleName</a>
                  <a href="{{ route('login') }}" class="hover:text-indigo-600 transition-colors">Log in</a>
              </nav>
              <!-- CTA Button -->
              <div>
                  <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-6 py-2.5 rounded-full text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 transition-all shadow-md shadow-indigo-600/20">
                      Get Started
                  </a>
              </div>
          </div>
      </div>
  </header>

  <!-- HERO SECTION -->
  <section class="pt-8 pb-16 sm:py-20 px-4 sm:px-6 lg:px-8 overflow-hidden">
      <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
        
          <!-- Hero Left Content (Page-load Fade Right) -->
          <div class="lg:col-span-6 space-y-6 text-center lg:text-left" data-aos="fade-right" data-aos-delay="100">
            
              <!-- Pill Tag -->
              <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold">
                  <svg class="w-3.5 h-3.5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.57l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.57l7-10a1 1 0 011.12-.384z"/>
                  </svg>
                  Built for students, not bankers
              </div>
              <!-- Main Heading -->
              <h1 class="text-4xl sm:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.12]">
                  Manage Your Weekly Allowance <span class="text-indigo-600">Smarter.</span>
              </h1>
              <!-- Subtitle -->
              <p class="text-base sm:text-lg text-slate-500 font-normal leading-relaxed max-w-lg mx-auto lg:mx-0">
                  Track expenses, predict spending, and build better financial habits—all in one friendly place.
              </p>
              <!-- Dual CTA Buttons -->
              <div class="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3">
                  <a href="{{ route('register') }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 rounded-2xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-lg shadow-indigo-600/25 transition">
                      <span>Get Started</span>
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                  </a>
                  <a href="#features" class="w-full sm:w-auto inline-flex items-center justify-center px-7 py-3.5 rounded-2xl text-sm font-semibold text-slate-700 bg-white hover:bg-slate-100 border border-slate-200/80 transition">
                      Learn More
                  </a>
              </div>
          </div>

          <!-- Hero Right Graphic (Page-load Fade Left) -->
          <div class="lg:col-span-6 relative flex items-center justify-center" data-aos="fade-left" data-aos-delay="200">
             
              <!-- Decorative Ambient Glows -->
              <div class="absolute -top-6 -right-6 w-64 h-64 bg-indigo-300/30 rounded-full blur-3xl pointer-events-none"></div>
              <div class="absolute -bottom-6 -left-6 w-64 h-64 bg-purple-300/30 rounded-full blur-3xl pointer-events-none"></div>
              <!-- Card Container -->
              <div class="w-full bg-gradient-to-tr from-indigo-100/70 via-purple-50/50 to-indigo-50/80 p-6 sm:p-10 rounded-[2.5rem] border border-white shadow-2xl relative">
                
                  <div class="bg-white/90 backdrop-blur-md rounded-3xl p-8 shadow-sm border border-slate-100/80 relative flex flex-col items-center justify-center min-h-[360px] space-y-6">
                    
                      <!-- Top Pop-up Speech Bubble -->
                      <div class="absolute -top-5 bg-indigo-600 text-white px-5 py-2.5 rounded-2xl shadow-lg shadow-indigo-600/30 text-xs sm:text-sm font-bold flex items-center gap-2 border-2 border-white z-20" data-aos="zoom-in" data-aos-delay="400">
                          <span>✨ "₱300 safe to spend today!"</span>
                      </div>
                      <!-- Local Student SVG Graphic -->
                      <div class="relative flex items-center justify-center my-2">
                          <img
                              src="{{ asset('images/undraw_budgeting_klon.svg') }}"
                              alt="Student graphic"
                              class="w-52 h-52 sm:w-60 sm:h-60 object-contain drop-shadow-md"
                          >
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

          <!-- 5-Card Feature Grid (Staggered Scroll Fade Up) -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
              <!-- Card 1 -->
              <div class="bg-white border border-slate-100 rounded-[2rem] p-8 shadow-sm hover:shadow-md transition-all duration-300 space-y-4" data-aos="fade-up" data-aos-delay="100">
                  <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center font-bold">
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
                      Gentle behavioral notifications that keep your spending balanced without feeling stressful.
                  </p>
              </div>
          </div>
      </div>
  </section>

  <!-- HOW IT WORKS SECTION -->
  <section id="how-it-works" class="py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-6xl mx-auto">
          <div class="bg-indigo-600 rounded-[2.5rem] p-8 sm:p-14 text-white shadow-xl shadow-indigo-600/10 space-y-10" data-aos="fade-up">
            
              <div class="text-center max-w-xl mx-auto">
                  <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight">
                      Up and running in 3 easy steps
                  </h2>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                  <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-3xl p-6 sm:p-8 space-y-4" data-aos="zoom-in-up" data-aos-delay="100">
                      <div class="w-12 h-12 bg-white text-indigo-600 rounded-full flex items-center justify-center font-extrabold text-lg">
                          1
                      </div>
                      <h3 class="text-lg font-bold">Set your allowance</h3>
                      <p class="text-xs sm:text-sm text-indigo-100 leading-relaxed font-normal">
                          Tell us your weekly budget — it takes 10 seconds to establish your primary cycle.
                      </p>
                  </div>
                  <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-3xl p-6 sm:p-8 space-y-4" data-aos="zoom-in-up" data-aos-delay="200">
                      <div class="w-12 h-12 bg-white text-indigo-600 rounded-full flex items-center justify-center font-extrabold text-lg">
                          2
                      </div>
                      <h3 class="text-lg font-bold">Log expenses fast</h3>
                      <p class="text-xs sm:text-sm text-indigo-100 leading-relaxed font-normal">
                          Tap a category chip or scan a receipt with AI. No tedious typing marathons.
                      </p>
                  </div>
                  <div class="bg-white/10 backdrop-blur-md border border-white/10 rounded-3xl p-6 sm:p-8 space-y-4" data-aos="zoom-in-up" data-aos-delay="300">
                      <div class="w-12 h-12 bg-white text-indigo-600 rounded-full flex items-center justify-center font-extrabold text-lg">
                          3
                      </div>
                      <h3 class="text-lg font-bold">Stay on track</h3>
                      <p class="text-xs sm:text-sm text-indigo-100 leading-relaxed font-normal">
                          See your daily safe-to-spend limit and build better habits effortlessly.
                      </p>
                  </div>
              </div>
          </div>
      </div>
  </section>

  <!-- COMPARISON SECTION -->
  <section id="comparison" class="py-16 sm:py-20 px-4 sm:px-6 lg:px-8">
      <div class="max-w-5xl mx-auto space-y-10">
        
          <div class="text-center max-w-xl mx-auto space-y-2" data-aos="fade-up">
              <h2 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                  A smarter way to handle weekly money
              </h2>
              <p class="text-sm text-slate-500 font-medium">
                  Traditional budgeting feels like homework. Here is how SampleName changes the game.
              </p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
              <!-- Traditional Expense Trackers -->
              <div class="bg-rose-50/50 border border-rose-100 rounded-[2rem] p-8 space-y-5" data-aos="fade-right" data-aos-delay="100">
                  <div class="inline-flex items-center gap-2 text-xs font-bold text-rose-600 bg-rose-100/80 px-3 py-1 rounded-full">
                      Traditional Expense Trackers
                  </div>
                  <ul class="space-y-3.5 text-xs sm:text-sm text-slate-600 font-medium">
                      <li class="flex items-start gap-3">
                          <span class="text-rose-500 font-black text-base line-none">•</span>
                          <span>Constantly guessing if your remaining allowance will actually last until the end of the week.</span>
                      </li>
                      <li class="flex items-start gap-3">
                          <span class="text-rose-500 font-black text-base line-none">•</span>
                          <span>No clear visibility into how much you can safely spend each day.</span>
                      </li>
                      <li class="flex items-start gap-3">
                          <span class="text-rose-500 font-black text-base line-none">•</span>
                          <span>Overwhelming monthly charts that don't fit weekly allowance cycles.</span>
                      </li>
                  </ul>
              </div>
              <!-- SampleName Way -->
              <div class="bg-indigo-50/50 border border-indigo-100 rounded-[2rem] p-8 space-y-5 shadow-sm" data-aos="fade-left" data-aos-delay="200">
                  <div class="inline-flex items-center gap-2 text-xs font-bold text-indigo-600 bg-indigo-100/80 px-3 py-1 rounded-full">
                      The SampleName Approach
                  </div>
                  <ul class="space-y-3.5 text-xs sm:text-sm text-slate-700 font-medium">
                      <li class="flex items-start gap-3">
                          <span class="text-indigo-600 font-black text-base line-none">✓</span>
                          <span>Know your daily <strong>Safe-to-Spend</strong> limit before making a purchase.</span>
                      </li>
                      <li class="flex items-start gap-3">
                          <span class="text-indigo-600 font-black text-base line-none">✓</span>
                          <span>Instant AI receipt extraction with zero manual data entry.</span>
                      </li>
                      <li class="flex items-start gap-3">
                          <span class="text-indigo-600 font-black text-base line-none">✓</span>
                          <span>Real-time "What-If" purchase simulations to protect your savings goal.</span>
                      </li>
                  </ul>
              </div>
          </div>
      </div>
  </section>

    <!-- BOTTOM CTA BANNER -->
    <section class="pb-16 sm:pb-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-white border border-slate-100 rounded-[2.5rem] p-10 sm:p-16 shadow-xl shadow-indigo-500/5 space-y-6" 
                data-aos="zoom-in-up" 
                data-aos-offset="0" 
                data-aos-anchor-placement="top-bottom">
                <h2 class="text-3xl sm:text-5xl font-extrabold text-slate-900 tracking-tight">
                    Ready to take control of your allowance?
                </h2>
                <p class="text-sm sm:text-base text-slate-500 font-medium max-w-xl mx-auto">
                    Join thousands of students building better money habits — one week at a time.
                </p>
                <div class="pt-2">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-2xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-95 shadow-lg shadow-indigo-600/20 transition">
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
          <div class="flex items-center gap-3">
              <div class="h-8 w-8 bg-indigo-600 text-white rounded-xl flex items-center justify-center font-black text-sm">
                  <!-- Wallet Icon -->
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z" />
                  </svg>
              </div>
              <span class="text-lg font-bold text-slate-900 tracking-tight">
                  Sample<span class="text-indigo-600">Name</span>
              </span>
          </div>
          <!-- Clean Footer Links -->
          <nav class="flex items-center gap-6 text-xs font-medium text-slate-500">
              <a href="#features" class="hover:text-slate-900 transition-colors">Features</a>
              <a href="#how-it-works" class="hover:text-slate-900 transition-colors">How It Works</a>
              <a href="#comparison" class="hover:text-slate-900 transition-colors">Why SampleName</a>
          </nav>
      </div>
  </footer>

  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>