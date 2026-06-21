<div class="min-h-screen bg-slate-50/50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header Component Context -->
        <div class="mb-8">
            <nav class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">
                <a href="{{ route('student.dashboard') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
                <svg class="w-2.5 h-2.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-slate-500">Savings & Milestones</span>
            </nav>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">My Savings Goals</h1>
            <p class="text-xs text-slate-500 font-medium mt-1">Build up your funds and track your progress for upcoming school expenses and personal milestones.</p>
        </div>

        @if (session()->has('success'))
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-xl text-emerald-800 text-[11px] font-bold flex items-center gap-2 shadow-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
            <div class="bg-white rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 p-6 space-y-5">
                <h3 class="text-sm font-black text-slate-900 tracking-tight">Create Savings Goal</h3>

                <form wire:submit.prevent="storeGoal" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Goal Reference Name</label>
                        <input type="text" wire:model.defer="target_name" placeholder="e.g., Semi-Final Exam Fees" class="block w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50/30 text-xs font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        @error('target_name') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Target Amount (₱)</label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><span class="text-slate-400 font-bold text-xs">₱</span></div>
                            <input type="number" step="0.01" wire:model.defer="target_amount" placeholder="0.00" class="block w-full pl-8 pr-3.5 py-2.5 border border-slate-200 bg-slate-50/30 text-xs font-bold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        </div>
                        @error('target_amount') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Target Resolution Date</label>
                        <input type="date" wire:model.defer="target_date" class="block w-full px-3.5 py-2.5 border border-slate-200 bg-slate-50/30 text-xs font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        @error('target_date') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold text-xs shadow-sm transition-colors pt-3">Create</button>
                </form>
            </div>

            <div class="lg:col-span-2 space-y-6">
                <div class="flex border-b border-slate-200 gap-6">
                    <button wire:click="$set('activeTab', 'active')" class="pb-3 text-xs font-extrabold uppercase tracking-wider transition-all relative {{ $activeTab === 'active' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400' }}">Active Targets</button>
                    <button wire:click="$set('activeTab', 'achieved')" class="pb-3 text-xs font-extrabold uppercase tracking-wider transition-all relative {{ $activeTab === 'achieved' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400' }}">Achieved</button>
                    <button wire:click="$set('activeTab', 'abandoned')" class="pb-3 text-xs font-extrabold uppercase tracking-wider transition-all relative {{ $activeTab === 'abandoned' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400' }}">Archived</button>
                </div>

                @if($goals->isEmpty())
                    <div class="bg-white rounded-2xl border border-dashed border-slate-200 text-center py-12 px-4 text-xs font-bold text-slate-500">No records found.</div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($goals as $goal)
                            <x-savings-card :goal="$goal" type="manager" />
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- MODAL 1: ADD FUNDS INTO A TARGET GOAL -->
        @if($fundingGoalId)
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl border border-slate-200 p-6 space-y-5">
                    <div class="flex justify-between items-start">
                        <h3 class="text-sm font-black text-slate-900 tracking-tight">Add Savings to Goal</h3>
                        <button wire:click="$set('fundingGoalId', null)" class="text-slate-400 hover:text-slate-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg></button>
                    </div>

                    <form wire:submit.prevent="addFunds" class="space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Savings Amount (₱)</label>
                            <div class="relative rounded-xl shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none"><span class="text-slate-400 font-bold text-xs">₱</span></div>
                                <input type="number" step="0.01" wire:model.defer="fund_amount" autofocus placeholder="0.00" class="block w-full pl-8 pr-3.5 py-2.5 border border-slate-200 bg-slate-50/30 text-xs font-bold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                            </div>
                            @error('fund_amount') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <button type="button" wire:click="$set('fundingGoalId', null)" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">Go Back</button>
                            <button type="submit" class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 text-center pt-2.5">Confirm Savings</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- MODAL 2: CONFIRM ARCHIVING GOAL -->
        @if($confirmingAbandonId)
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl border border-slate-200 p-6 space-y-4">
                    <h3 class="text-sm font-black text-slate-900 tracking-tight">Archive Savings Goal?</h3>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">Are you sure you want to archive this goal? You can still view or restore it inside your Archived folder later.</p>
                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" wire:click="$set('confirmingAbandonId', null)" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="executeAbandon" class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 text-center pt-2.5">Archive Goal</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL 3: CONFIRM PERMANENT DELETION WITH CASCADE WARNING -->
        @if($confirmingDeleteId)
            <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
                <div class="bg-white w-full max-w-sm rounded-2xl shadow-xl border border-slate-200 p-6 space-y-4">
                    <h3 class="text-sm font-black text-rose-600 tracking-tight flex items-center gap-1.5">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        Permanently Delete Goal?
                    </h3>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed">Warning! This action cannot be undone. All matching expense allocation records associated with this specific milestone will be permanently removed to keep ledger balances clean.</p>
                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" wire:click="$set('confirmingDeleteId', null)" class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="executeDelete" class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 text-center pt-2.5">Permanently Delete</button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>