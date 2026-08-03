<div class="max-w-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Add Budget Funds</h1>
        <p class="text-sm text-slate-500 mt-1">Top up your remaining allowance for the current cycle.</p>
    </div>

    <!-- Current Balance Card -->
    <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-4 flex items-center justify-between">
        <div>
            <span class="text-xs font-semibold text-indigo-600 block">Current Remaining Budget</span>
            <span class="text-2xl font-black text-indigo-950 font-mono">
                ₱{{ number_format($currentBudget->remaining_allowance ?? 0, 2) }}
            </span>
        </div>
        <div class="h-10 w-10 bg-indigo-600 text-white rounded-xl flex items-center justify-center font-bold text-lg">
            ₱
        </div>
    </div>

    <!-- Top Up Form -->
    <form wire:submit.prevent="addFunds" class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm space-y-5">
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Amount to Add (PHP)
            </label>
            <div class="relative rounded-xl shadow-xs">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-extrabold font-mono text-base">
                    ₱
                </div>
                <input type="number" step="0.01" wire:model.defer="amount" placeholder="0.00" 
                    class="block w-full pl-8 pr-4 py-3 border border-slate-200 rounded-2xl text-slate-900 font-mono font-bold text-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            </div>
            @error('amount') 
                <span class="text-xs text-rose-500 font-semibold mt-1 block">{{ $message }}</span> 
            @enderror
        </div>

        <!-- Quick Top Up Options -->
        <div class="space-y-1.5">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quick Select</span>
            <div class="flex flex-wrap gap-2">
                @foreach([50, 100, 200, 500] as $preset)
                    <button type="button" wire:click="$set('amount', {{ $preset }})" 
                        class="px-3 py-1.5 text-xs font-bold font-mono rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors">
                        +₱{{ $preset }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-3 flex items-center gap-3">
            <button type="submit" 
                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold py-3 px-5 rounded-2xl shadow-md shadow-indigo-500/20 transition-all transform active:scale-95 text-sm">
                Add to Budget
            </button>
            <a href="{{ route('student.dashboard') }}" 
                class="px-5 py-3 border border-slate-200 text-slate-600 font-bold rounded-2xl hover:bg-slate-50 transition-colors text-sm text-center">
                Cancel
            </a>
        </div>
    </form>
</div>