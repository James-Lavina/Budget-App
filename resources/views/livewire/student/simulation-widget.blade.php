<div class="bg-white rounded-3xl border border-slate-200/70 shadow-sm p-6 w-full h-full flex flex-col justify-between group hover:border-slate-300 transition-all duration-300">
    
    <div class="flex-1 flex flex-col">
        <div class="border-b border-slate-100 pb-4 mb-5 shrink-0">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-widest flex items-center gap-2">
                <span class="w-1.5 h-3 bg-amber-500 rounded-full animate-pulse"></span>
                What-If Quick Test
            </h3>
            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Eyeballing something expensive? Check how it alters your allowance instantly.</p>
        </div>
        
        <form wire:submit.prevent="calculateImpact" class="flex-1 flex flex-col justify-between">
            <div class="space-y-3 flex-1">
                <div class="space-y-1">
                    <div class="relative rounded-xl shadow-sm group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <span class="text-xs font-bold text-slate-400 font-mono">₱</span>
                        </div>
                        <input type="number" 
                               min="0"
                               wire:model.defer="quickAmount" 
                               class="block w-full pl-8 pr-4 py-3 text-xs font-bold rounded-xl border border-slate-200 bg-slate-50/30 text-slate-800 placeholder-slate-400 font-mono focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/10 focus:outline-none transition-all duration-200" 
                               placeholder="0.00">
                    </div>
                    
                    @error('quickAmount') 
                        <span class="text-[10px] font-bold text-rose-500 block px-1 animate-fade-in">
                            {{ $message }}
                        </span> 
                    @enderror
                </div>
            </div>
            
            <button type="submit" class="w-full mt-5 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white text-[11px] font-bold py-3 px-4 rounded-xl shadow-sm shadow-indigo-600/10 transition-all duration-200 uppercase tracking-wider flex items-center justify-center gap-1.5 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <span>Analyze Impact</span>
                <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </form>
    </div>

</div>