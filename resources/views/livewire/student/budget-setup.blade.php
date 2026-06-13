<div class="min-h-screen bg-slate-50/50 flex flex-col justify-center items-center px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 p-8 space-y-6">
        
        <div class="text-center">
            <div class="h-12 w-12 bg-indigo-50 border border-indigo-100 text-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Set Up Your Weekly Budget</h1>
            <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
                Configure your financial baseline parameters to initialize the behavioral pacing analytical engine.
            </p>
        </div>

        <form wire:submit.prevent="initializeEngine" class="space-y-5">
            
            <div class="space-y-1">
                <label for="total_allowance" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                    Weekly School Allowance
                </label>
                <p class="text-[11px] text-slate-400 leading-normal">
                    Enter the total recurring fund limit you have allocated for food, transportation, and structural academic needs each cycle.
                </p>
                <div class="relative rounded-xl shadow-sm pt-1">
                    <div class="absolute inset-y-0 left-0 pl-4 pt-1 flex items-center pointer-events-none">
                        <span class="text-slate-400 font-bold text-sm">₱</span>
                    </div>
                    <input id="total_allowance" type="number" step="0.01" placeholder="0.00" wire:model.debounce.500ms="total_allowance"
                        class="block w-full pl-9 pr-4 py-3 border border-slate-200 bg-slate-50/30 font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all text-sm">
                </div>
                @error('total_allowance')
                    <div class="p-2.5 bg-rose-50 border border-rose-100 rounded-lg text-rose-800 text-[11px] font-semibold mt-2 flex items-center gap-1.5">
                        <span class="h-1 w-1 rounded-full bg-rose-500"></span>
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="reset_day" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                    Cycle Reset Anchor Day
                </label>
                <p class="text-[11px] text-slate-400 leading-normal">
                    Select your primary funding day. On this specific morning, your monitoring metrics and available balances will automatically clear and refresh.
                </p>
                <div class="pt-1">
                    <select id="reset_day" wire:model="reset_day"
                        class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        <option value="Monday">Monday Morning (Academic Week Open)</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday (Weekend Transition Focus)</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday Night</option>
                    </select>
                </div>
            </div>

            <div class="bg-indigo-50/40 rounded-xl p-4 border border-indigo-100/60 space-y-1 text-[11px] leading-relaxed">
                <span class="font-bold block text-xs text-indigo-900 uppercase tracking-wider">Initial Interval Timeline</span>
                <p class="text-indigo-700/80 font-medium">System tracking and database audit log triggers will deploy immediately.</p>
                <p class="font-bold text-indigo-900 pt-0.5">
                    Current Execution Block: <span class="bg-white/90 border border-indigo-100 px-1.5 py-0.5 rounded text-indigo-700 font-extrabold">{{ \Carbon\Carbon::today()->format('M d') }}</span> to <span class="bg-white/90 border border-indigo-100 px-1.5 py-0.5 rounded text-indigo-700 font-extrabold">{{ \Carbon\Carbon::today()->addDays(6)->format('M d, Y') }}</span>
                </p>
            </div>

            <button type="submit" wire:loading.attr="disabled"
                class="w-full flex justify-center items-center gap-1.5 py-3 px-4 rounded-xl shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all disabled:opacity-50">
                <span wire:loading.remove class="inline-flex items-center gap-1.5">
                    <span>Initialize System Tracking</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </span>
                <span wire:loading class="inline-flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Configuring Database Architecture...</span>
                </span>
            </button>
        </form>
    </div>
</div>