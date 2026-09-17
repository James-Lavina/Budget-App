<div class="bg-white rounded-3xl border border-slate-200/70 shadow-sm p-6 w-full h-full flex flex-col">
    
    <div class="border-b border-slate-100 pb-4 mb-5 flex items-center justify-between shrink-0">
        <div>
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-widest">
                Active Savings Goal
            </h3>
            <p class="text-[11px] text-slate-400 font-medium mt-0.5">Real-time progress toward your active milestones.</p>
        </div>
        
        <a href="{{ route('student.goals') }}" class="text-[10px] font-extrabold uppercase text-[var(--brand)] hover:opacity-80 tracking-wider flex items-center gap-0.5 shrink-0 transition-colors">
            Manage All
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>

    @if($topGoals->isEmpty())
        <div class="flex-1 flex flex-col items-center justify-center text-center py-8 space-y-3 min-h-[168px]">
            <div class="h-10 w-10 bg-slate-50 border border-slate-100 text-slate-400 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <p class="text-xs text-slate-400 font-semibold max-w-[220px]">No active tracking targets initialized yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @foreach($topGoals as $goal)
                <div class="p-1 bg-slate-50/30 rounded-2xl border border-slate-100/50 hover:bg-slate-50/80 transition-colors">
                    <x-savings-card :goal="$goal" type="dashboard" />
                </div>
            @endforeach
        </div>
    @endif

    @if($fundingGoalId)
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transform-gpu z-50 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl border border-slate-100 p-6 space-y-5">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 tracking-tight">Add Savings to Goal</h3>
                        <p class="text-[11px] text-slate-400 font-medium">Transfer funds from allowance.</p>
                    </div>
                    <button wire:click="closeFundingModal" class="text-slate-400 hover:text-slate-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
                <form wire:submit.prevent="addFunds" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Savings Amount (₱)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₱</span>
                            <input type="number" step="1" wire:model.defer="fund_amount" autofocus placeholder="0.00"
                                class="block w-full pl-8 pr-3.5 py-2.5 border border-slate-200 bg-slate-50/50 text-xs font-bold rounded-2xl text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:border-[var(--brand)] focus:ring-[rgba(var(--brand-rgb),0.2)] transition-all">
                        </div>
                        @error('fund_amount') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" wire:click="closeFundingModal" class="px-4 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">Go Back</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-[var(--brand)] hover:opacity-90 shadow-md transition-all text-center">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>