<div class="min-h-screen py-10 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-6xl mx-auto space-y-8">
       
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight">Savings Goals</h1>
                <p class="text-xs text-slate-500 font-medium mt-1">Give every dream a place in your budget. Small steps add up.</p>
            </div>
            <div>
                <button wire:click="openCreateModal" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] text-white font-extrabold text-xs px-5 py-3 rounded-2xl shadow-lg shadow-indigo-200 transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Add Goal</span>
                </button>
            </div>
        </div>
 
        @if (session()->has('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif
 
        @if (session()->has('error'))
            <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-800 text-xs font-bold flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l18 18"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
 
        <!-- Main Goals Grid Area -->
        <div class="space-y-6">
            <div class="flex border-b border-slate-200/80 gap-6">
                <button wire:click="$set('activeTab', 'active')" class="pb-3 text-xs font-extrabold uppercase tracking-wider transition-all relative {{ $activeTab === 'active' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">Active Targets</button>
                <button wire:click="$set('activeTab', 'achieved')" class="pb-3 text-xs font-extrabold uppercase tracking-wider transition-all relative {{ $activeTab === 'achieved' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">Achieved</button>
                <button wire:click="$set('activeTab', 'abandoned')" class="pb-3 text-xs font-extrabold uppercase tracking-wider transition-all relative {{ $activeTab === 'abandoned' ? 'text-indigo-600 border-b-2 border-indigo-600' : 'text-slate-400 hover:text-slate-600' }}">Archived</button>
            </div>
 
            @if($goals->isEmpty())
                <div class="bg-white rounded-3xl border border-dashed border-slate-200 text-center py-16 px-4 text-xs font-bold text-slate-400 space-y-2">
                    <p>No records found in this category.</p>
                    <p class="text-[11px] font-medium text-slate-400">Click "+ Add Goal" above to create a new milestone!</p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($goals as $goal)
                        <x-savings-card :goal="$goal" type="manager" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    
    <!-- MODAL 0: CREATE SAVINGS GOAL -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[9999] flex items-center justify-center p-4 transition-all">
            <div class="bg-white w-full max-w-md rounded-[28px] shadow-2xl border border-slate-100 p-6 sm:p-8 space-y-6 relative animate-in fade-in zoom-in duration-150">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-xl font-black text-slate-900 tracking-tight">Create a savings goal</h3>
                        <p class="text-xs text-slate-500 font-medium mt-1">Name the win you're working toward.</p>
                    </div>
                    <button wire:click="closeCreateModal" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <!-- Form Fields -->
                <form wire:submit.prevent="storeGoal" class="space-y-5">
                    <!-- Goal Name -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Goal name</label>
                        <input type="text" wire:model.defer="target_name" placeholder="e.g. Concert tickets" 
                            class="w-full px-4 py-3 bg-slate-100/70 border border-slate-200/80 rounded-2xl text-slate-900 font-semibold text-xs placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                        @error('target_name') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Amount Grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Goal Amount -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Goal amount</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₱</span>
                                <input type="number" step="0.01" min="0" wire:model.defer="target_amount" placeholder="0.00" 
                                    class="w-full pl-8 pr-3 py-3 bg-slate-100/70 border border-slate-200/80 rounded-2xl text-slate-900 font-bold text-xs placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                            </div>
                            @error('target_amount') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                        </div>

                        <!-- Already Saved -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Already saved</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₱</span>
                                <input type="number" step="0.01" min="0" wire:model.defer="already_saved" placeholder="0.00" 
                                    class="w-full pl-8 pr-3 py-3 bg-slate-100/70 border border-slate-200/80 rounded-2xl text-slate-900 font-bold text-xs placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                            </div>
                            @error('already_saved') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <!-- Target Date -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Target date (optional)</label>
                        <input type="date" wire:model.defer="target_date" 
                            class="w-full px-4 py-3 bg-slate-100/70 border border-slate-200/80 rounded-2xl text-slate-900 font-semibold text-xs focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                        @error('target_date') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button type="button" wire:click="closeCreateModal" 
                            class="px-5 py-2.5 rounded-2xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-extrabold text-xs shadow-md shadow-indigo-200 transition-all active:scale-[0.98]">
                            Create Goal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 1: ADD FUNDS INTO A GOAL -->
    @if($fundingGoalId)
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transform-gpu z-50 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl border border-slate-100 p-6 space-y-5">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-sm font-black text-slate-900 tracking-tight">Add Savings to Goal</h3>
                        <p class="text-[11px] text-slate-400 font-medium">Transfer funds from allowance.</p>
                    </div>
                    <button wire:click="$set('fundingGoalId', null)" class="text-slate-400 hover:text-slate-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                </div>
                <form wire:submit.prevent="addFunds" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">Savings Amount (₱)</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs">₱</span>
                            <input type="number" step="0.01" wire:model.defer="fund_amount" autofocus placeholder="0.00" class="block w-full pl-8 pr-3.5 py-2.5 border border-slate-200 bg-slate-50/50 text-xs font-bold rounded-2xl text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        </div>
                        @error('fund_amount') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" wire:click="$set('fundingGoalId', null)" class="px-4 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">Go Back</button>
                        <button type="submit" class="flex-1 px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-200 transition-all text-center">Confirm Savings</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 2: ARCHIVE GOAL -->
    @if($confirmingAbandonId)
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transform-gpu z-50 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl border border-slate-100 p-6 space-y-4">
                <h3 class="text-sm font-black text-slate-900 tracking-tight">Archive Savings Goal?</h3>
                <p class="text-xs text-slate-500 font-medium leading-relaxed">Are you sure you want to archive this goal? You can still view or restore it inside your Archived folder later.</p>
                <div class="flex items-center gap-2 pt-2">
                    <button type="button" wire:click="$set('confirmingAbandonId', null)" class="px-4 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">Cancel</button>
                    <button type="button" wire:click="executeAbandon" class="flex-1 px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-amber-500 hover:bg-amber-600 shadow-md shadow-amber-200 transition-all text-center">Archive Goal</button>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL 3: CONFIRM PERMANENT DELETION -->
    @if($confirmingDeleteId)
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transform-gpu z-50 flex items-center justify-center p-4">
            <div class="bg-white w-full max-w-sm rounded-3xl shadow-2xl border border-slate-100 p-6 space-y-4">
                <h3 class="text-sm font-black text-rose-600 tracking-tight flex items-center gap-1.5">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Permanently Delete Goal?
                </h3>
                <p class="text-xs text-slate-500 font-medium leading-relaxed">Warning! This action cannot be undone. All matching expense allocation records associated with this specific milestone will be permanently removed to keep ledger balances clean.</p>
                <div class="flex items-center gap-2 pt-2">
                    <button type="button" wire:click="$set('confirmingDeleteId', null)" class="px-4 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors">Cancel</button>
                    <button type="button" wire:click="executeDelete" class="flex-1 px-4 py-2.5 rounded-2xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-200 transition-all text-center">Permanently Delete</button>
                </div>
            </div>
        </div>
    @endif
 </div>