<div class="min-h-screen py-10 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Add Expense</h1>
            <p class="text-xs font-medium text-slate-500 mt-1">
                Log your transaction details to update your current budget.
            </p>
        </div>

        <!-- Single Expense Form Card -->
        <form wire:submit.prevent="storeExpense" class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">
            
            <!-- Row 1: Store/Merchant & Item Name -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Store / Merchant (optional) -->
                <div class="space-y-1.5">
                    <label for="merchant_name" class="block text-xs font-bold text-slate-700">
                        Store / Merchant <span class="font-normal text-slate-400">(optional)</span>
                    </label>
                    <input id="merchant_name" type="text" wire:model.defer="merchant_name" placeholder="e.g., Jollibee"
                        class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('merchant_name')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Item Name -->
                <div class="space-y-1.5">
                    <label for="item_name" class="block text-xs font-bold text-slate-700">
                        Item Name
                    </label>
                    <input id="item_name" type="text" wire:model.defer="item_name" placeholder="e.g., Chickenjoy Meal"
                        class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('item_name')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Row 2: Amount & Date -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Amount -->
                <div class="space-y-1.5">
                    <label for="amount" class="block text-xs font-bold text-slate-700">
                        Amount
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-800 font-extrabold text-base">
                            ₱
                        </span>
                        <input
                        id="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model.defer="amount"
                        placeholder="0.00"
                        onblur="formatAmount(this)"
                        class="w-full pl-9 pr-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-extrabold text-base placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    @error('amount')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Date -->
                <div class="space-y-1.5">
                    <label for="transaction_date" class="block text-xs font-bold text-slate-700">
                        Date
                    </label>
                    <input id="transaction_date" type="date" wire:model.defer="transaction_date"
                        class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-800 font-semibold text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('transaction_date')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Row 3: Category (Dynamic Pills) -->
            <div class="space-y-2 pt-1">
                <label class="block text-xs font-bold text-slate-700">
                    Category
                </label>
                
                <div class="flex flex-wrap gap-2 items-center">
                    @foreach($categories as $category)
                        @php
                            $isSelected = $expense_category_id == $category->id;
                        @endphp
                        <button type="button" 
                            wire:click="$set('expense_category_id', {{ $category->id }})"
                            class="px-4 py-2 rounded-full text-xs font-bold flex items-center gap-2 transition-all duration-150 transform active:scale-95 {{ $isSelected ? 'bg-indigo-600 text-white shadow-md shadow-indigo-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200/80' }}">
                            <x-category-icon :type="$category->icon" />
                            <span>{{ $category->name }}</span>
                        </button>
                    @endforeach
                </div>
                
                @error('expense_category_id')
                    <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- Session Ledger: only shows once something's been logged --}}
            @if(count($sessionLog) > 0)
            <div class="bg-emerald-50/60 border border-emerald-100 rounded-2xl p-4 space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-emerald-800">
                    <span>Logged this session ({{ count($sessionLog) }})</span>
                    <span>₱{{ number_format(array_sum(array_column($sessionLog, 'amount')), 2) }} total</span>
                </div>
                <div class="space-y-1.5">
                    @foreach($sessionLog as $entry)
                        <div class="flex items-center justify-between bg-white/70 rounded-xl px-3 py-2 text-xs gap-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="h-6 w-6 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                                    <x-category-icon :type="$entry['category_icon']" />
                                </div>
                                <div class="min-w-0">
                                    <span class="font-semibold text-slate-700 truncate block">{{ $entry['item_name'] }}</span>
                                    <span class="text-[10px] font-medium text-slate-400">{{ $entry['category_name'] }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="font-bold text-slate-900 font-mono">₱{{ number_format($entry['amount'], 2) }}</span>
                                <button type="button" wire:click="removeFromSessionLog({{ $entry['id'] }})"
                                    class="text-slate-400 hover:text-rose-600 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Footer buttons --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}"
                class="px-5 py-2.5 rounded-full text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all">
                Cancel
                </a>

                <button type="button" wire:click="storeAndAddAnother" wire:loading.attr="disabled"
                    class="px-6 py-3 bg-white border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-50 rounded-full font-bold text-xs transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="storeAndAddAnother">+ Save & Add Another</span>
                    <span wire:loading wire:target="storeAndAddAnother">Saving...</span>
                </button>

                <button type="submit" wire:loading.attr="disabled"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-bold text-xs shadow-lg shadow-indigo-200 transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="storeExpense">Save & Finish</span>
                    <span wire:loading wire:target="storeExpense">Saving...</span>
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    function formatAmount(input) {
        if (input.value !== '') {
            input.value = parseFloat(input.value).toFixed(2);

            // Keep Livewire updated
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    document.addEventListener('expense-added', () => {
        document.getElementById('item_name')?.focus();
    });
</script>