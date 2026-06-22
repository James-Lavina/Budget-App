<div class="max-w-xl mx-auto px-4 sm:px-6 py-12">
    <div class="mb-10 text-center sm:text-left">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight sm:text-3xl">Budget Configuration</h1>
        <p class="text-slate-500 text-sm font-medium mt-2">Adjust your allowance limits and internal rule parameters below.</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-sm">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200/70 rounded-3xl shadow-[0_10px_30px_-10px_rgba(0,0,0,0.04)] overflow-hidden">
        <form wire:submit.prevent="updateSettings" class="p-6 sm:p-10 space-y-8">
            
            <div class="space-y-2">
                <label for="total_allowance" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">
                    Weekly Allowance
                </label>
                <div class="relative mt-1">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-5">
                        <span class="text-slate-400 font-extrabold text-lg select-none">₱</span>
                    </div>
                    <input type="number" step="0.01" wire:model.defer="total_allowance" id="total_allowance" 
                           placeholder="0.00"
                           class="block w-full h-14 rounded-2xl border-0 bg-slate-50 pl-11 pr-5 text-base font-bold text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all duration-150">
                </div>
                @error('total_allowance') <span class="text-rose-600 text-xs font-bold mt-1 block pl-1">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-2">
                <label for="reset_day" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">
                    Cycle Reset Day
                </label>
                <div class="relative mt-1">
                    <select wire:model.defer="reset_day" id="reset_day" 
                            class="block w-full h-14 rounded-2xl border-0 bg-slate-50 px-5 text-sm font-semibold text-slate-800 appearance-none focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all duration-150">
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                @error('reset_day') <span class="text-rose-600 text-xs font-bold mt-1 block pl-1">{{ $message }}</span> @enderror
            </div>

            <div class="p-5 bg-indigo-50/50 rounded-2xl border border-indigo-100/60 space-y-4">
                <div class="flex gap-3.5">
                    <div class="text-xs font-medium text-slate-600 leading-relaxed">
                        <span class="font-extrabold text-indigo-950 block mb-0.5">Calculation Rule Behavior</span>
                        By default, your modified baseline values automatically go live on the next scheduled system initialization cycle.
                    </div>
                </div>

                <label class="relative flex items-center gap-3 p-3 bg-white border border-slate-100 rounded-xl cursor-pointer select-none group transition-all hover:border-indigo-200">
                    <input type="checkbox" wire:model="update_current_week" 
                           class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 transition-colors">
                    <span class="text-xs font-bold text-slate-700 group-hover:text-indigo-950 transition-colors">
                        Recalculate active pool metrics right away
                    </span>
                </label>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full h-14 flex items-center justify-center rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-lg shadow-indigo-600/20 active:scale-[0.98] transition-all duration-150">
                    Save Changes
                </button>
            </div>

        </form>
    </div>
</div>