<div class="bg-white p-6 rounded-3xl border border-slate-200/70 shadow-sm flex flex-col justify-between h-full group hover:border-slate-300 transition-all duration-300">
    <div class="space-y-2">
        <!-- Section Tagline Header -->
        <div class="flex items-center gap-2 text-indigo-600">
            <div class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg group-hover:bg-indigo-100 transition duration-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"></path>
                </svg>
            </div>
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-900">What-If Quick Test</h3>
        </div>
        
        <!-- Student Friendly Context Copy -->
        <p class="text-xs text-slate-400 font-medium leading-relaxed">
            Eyeballing something expensive? Type in the price tag below to instantly see how it changes your daily safe-to-spend allowance limit.
        </p>
    </div>

    <!-- Interactive Interactive Input Form -->
    <form wire:submit.prevent="calculateImpact" class="mt-5 space-y-3">
        <div class="space-y-1">
            <div class="relative rounded-xl shadow-sm group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <span class="text-xs font-bold text-slate-400 font-mono">₱</span>
                </div>
                <input type="number" 
                       min="0"
                       wire:model.defer="quickAmount" 
                       class="block w-full pl-8 pr-4 py-3 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50/30 text-slate-800 placeholder-slate-400 font-mono focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 focus:outline-none transition-all duration-200" 
                       placeholder="0.00">
            </div>
            
            <!-- Dynamic Validation Messaging Core -->
            @error('quickAmount') 
                <span class="text-[10px] font-bold text-rose-500 block px-1 animate-fade-in">
                    {{ $message }}
                </span> 
            @enderror
        </div>
        
        <!-- Call To Action Submission Trigger -->
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 active:scale-[0.99] text-white text-[11px] font-black py-3 px-4 rounded-xl transition-all duration-200 uppercase tracking-wider shadow-sm flex items-center justify-center gap-1">
            <span>Analyze Impact</span>
            <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>
    </form>
</div>