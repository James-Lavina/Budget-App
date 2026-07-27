<div class="min-h-screen bg-slate-100/80 flex items-center justify-center p-4 sm:p-6 lg:p-8 font-sans antialiased text-slate-800">
  
    <!-- Split Card Container -->
    <div class="max-w-5xl w-full bg-white rounded-[2.5rem] shadow-2xl shadow-indigo-950/10 border border-slate-100 grid grid-cols-1 lg:grid-cols-12">
       
        <!-- LEFT COLUMN: Brand Hero Side -->
        <div class="lg:col-span-6 bg-gradient-to-br from-indigo-600 via-indigo-600 to-indigo-700 p-8 sm:p-10 text-white flex flex-col justify-between space-y-8 relative overflow-hidden rounded-t-[2.5rem] lg:rounded-tr-none lg:rounded-l-[2.5rem]">
           
            <!-- Ambient Glow Effects -->
            <div class="absolute -top-12 -right-12 w-64 h-64 bg-indigo-400/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-12 -left-12 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="space-y-6 relative z-10">
                <!-- Brand Header -->
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-white text-indigo-600 rounded-2xl flex items-center justify-center shadow-md font-black">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a1 1 0 11-2 0 1 1 0 012 0z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight text-white">
                        SampleName
                    </span>
                </div>
                <!-- Step Badge -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-white/90 text-xs font-semibold">
                    <span>✨ Step 1 of 1: Quick Allowance Setup</span>
                </div>
                <!-- Main Left Title & Subtitle -->
                <div class="space-y-3">
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight text-white">
                        Let's set your weekly pace.
                    </h1>
                    <p class="text-xs sm:text-sm text-indigo-100 font-normal leading-relaxed">
                        Tell us your weekly allowance so SampleName can calculate your daily safe-to-spend limit automatically.
                    </p>
                </div>
                <!-- Mascot & Floating Safe-To-Spend Preview Card -->
                <div class="relative bg-white/95 rounded-3xl p-6 shadow-xl border border-white/20 flex flex-col items-center justify-center min-h-[200px]">
                    <img
                        src="{{ asset('images/undraw_budgeting_klon.svg') }}"
                        alt="Student illustration"
                        class="w-36 h-36 object-contain drop-shadow-sm"
                    >
                   
                    <!-- Floating Badge -->
                    <div class="absolute bottom-3 right-3 bg-white border border-slate-100 rounded-2xl p-2.5 shadow-lg flex flex-col text-left">
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Calculated Daily</span>
                        <span class="text-sm font-black text-emerald-600">Safe-To-Spend</span>
                    </div>
                </div>
            </div>
            <!-- Footer Features -->
            <div class="flex items-center gap-6 text-xs font-semibold text-indigo-100/90 pt-4 relative z-10 border-t border-white/10">
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span>Takes under 10 seconds</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    <span>Change anytime</span>
                </div>
            </div>
        </div>
 
        <!-- RIGHT COLUMN: Setup Form Side -->
        <div class="lg:col-span-6 p-8 sm:p-12 flex flex-col justify-center bg-white rounded-b-[2.5rem] lg:rounded-bl-none lg:rounded-r-[2.5rem]">
            <div class="max-w-md w-full mx-auto space-y-6">
               
                <!-- Form Header -->
                <div class="space-y-1.5">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                        Set Up Your Weekly Budget
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 font-normal">
                        Configure your baseline allowance to unlock your financial tracking dashboard.
                    </p>
                </div>
                <!-- Setup Form -->
                <form wire:submit.prevent="initializeEngine" class="space-y-5">
                   
                    <!-- Weekly Allowance Field -->
                    <div class="space-y-1.5">
                        <label for="total_allowance" class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-6h6"/></svg>
                            Weekly Allowance
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-slate-400 font-bold text-sm">₱</span>
                            </div>
                            <input id="total_allowance" type="number" step="0.01" placeholder="0.00" wire:model.debounce.500ms="total_allowance"
                                class="block w-full rounded-2xl pl-9 pr-4 py-3 bg-slate-50 border placeholder-slate-400 font-semibold focus:bg-white focus:outline-none focus:ring-2 sm:text-sm transition-all duration-200
                                @error('total_allowance') border-rose-300 text-rose-900 placeholder-rose-300 focus:border-rose-500 focus:ring-rose-500/20 bg-rose-50/30 @else border-slate-200 focus:border-indigo-600 focus:ring-indigo-600/20 text-slate-900 @enderror">
                        </div>
                        @error('total_allowance')
                            <span class="text-xs text-rose-600 mt-1 block font-medium flex items-center gap-1">
                                <span class="w-1 h-1 rounded-full bg-rose-600 inline-block"></span> {{ $message }}
                            </span>
                        @enderror
                    </div>
                    <!-- Reset Anchor Day Field -->
                    <div class="space-y-1.5">
                        <label for="reset_day" class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            Weekly Reset Day
                        </label>
                        <div>
                            <select id="reset_day" wire:model="reset_day"
                                class="block w-full rounded-2xl px-4 py-3 bg-slate-50 border border-slate-200 text-slate-900 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:border-indigo-600 focus:ring-indigo-600/20 sm:text-sm transition-all duration-200">
                                <option value="Monday">Monday Morning (School Week Start)</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday (Weekend Transition)</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday Night</option>
                            </select>
                        </div>
                    </div>
                    <!-- Initial Interval Timeline Banner -->
                    <div class="bg-indigo-50/60 rounded-2xl p-4 border border-indigo-100 space-y-1 text-xs">
                        <span class="font-bold block text-slate-800 uppercase tracking-wider text-[11px]">First Tracking Cycle</span>
                        <p class="text-slate-600 leading-relaxed">
                            Your allowance cycle will run from
                            <span class="font-bold text-indigo-700 bg-white px-2 py-0.5 rounded-lg border border-indigo-100 inline-block shadow-sm">
                                {{ \Carbon\Carbon::today()->format('M d') }}
                            </span>
                            to
                            <span class="font-bold text-indigo-700 bg-white px-2 py-0.5 rounded-lg border border-indigo-100 inline-block shadow-sm">
                                {{ \Carbon\Carbon::today()->addDays(6)->format('M d, Y') }}
                            </span>
                        </p>
                    </div>
                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] transition-all duration-200 shadow-lg shadow-indigo-600/25 disabled:opacity-50 disabled:cursor-not-allowed">
                           
                            <span wire:loading.remove class="inline-flex items-center gap-2">
                                <span>Start Tracking Allowance</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
                            </span>
                            <span wire:loading class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Setting Up Your Allowance...</span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
 </div>