<div class="min-h-screen py-10 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Add Expense</h1>
            <p class="text-xs font-medium text-slate-500 mt-1">
                Log your transaction details to update your current budget.
            </p>
        </div>

        @if (session()->has('error'))
            <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-800 text-xs font-bold">
                {{ session('error') }}
            </div>
        @endif

        <!-- Single Expense Form Card -->
        <form wire:submit.prevent="storeExpense" class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">

            {{-- Quick add: tap the label to fill the form, tap + to log it again right now --}}
            @if($frequentItems->isNotEmpty())
                <div class="space-y-1.5">
                    <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider">Quick add</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach($frequentItems as $freq)
                            <div wire:key="freq-{{ $freq['id'] }}" class="inline-flex items-stretch bg-white border border-slate-200 rounded-xl overflow-hidden">
                                <button type="button" wire:click="useFrequent({{ $freq['id'] }})"
                                    class="px-3 py-1.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-50 transition-colors">
                                    {{ $freq['item_name'] }}
                                    <span class="font-mono text-slate-400 ml-1">₱{{ number_format($freq['amount'], 2) }}</span>
                                </button>
                                <button type="button" wire:click="repeatExpense({{ $freq['id'] }})" wire:loading.attr="disabled"
                                    title="Log this again now"
                                    class="px-2.5 border-l border-slate-200 text-indigo-600 font-black text-sm hover:bg-indigo-50 transition-colors disabled:opacity-50">
                                    +
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Row 1: Item Name (drives category detection) --}}
            <div class="space-y-1.5">
                <label for="item_name" class="block text-xs font-bold text-slate-700">Item Name</label>
                <input id="item_name" type="text" autofocus autocomplete="off"
                    wire:model.debounce.400ms="item_name" placeholder="e.g., Chickenjoy Meal"
                    class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                @error('item_name')
                    <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                @enderror

                @if($expense_category_id && $recentItems->isNotEmpty())
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        @foreach($recentItems as $recent)
                            <button type="button"
                                wire:click='pickRecentItem(@json($recent, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG))'
                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-50 hover:bg-slate-100 text-slate-500 border border-slate-200 rounded-lg transition-colors">
                                {{ $recent }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Row 2: Category --}}
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-bold text-slate-700">Category</label>
                    @if($categoryAutoPicked)
                        <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wide">Auto-selected — tap another to change</span>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2 items-center">
                    @foreach($categories as $category)
                        @php $isSelected = $expense_category_id == $category->id; @endphp
                        <button type="button"
                            wire:key="cat-{{ $category->id }}"
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

                {{-- Mismatch nudge --}}
                @if($suggestedCategoryId)
                    <div class="mt-2 p-3 bg-amber-50 border border-amber-100 rounded-2xl flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                        <div class="flex-1 space-y-1.5">
                            <p class="text-[11px] font-semibold text-amber-800 leading-snug">
                                "{{ $item_name }}" looks like <span class="font-bold">{{ $suggestedCategoryName }}</span>. Switch category?
                            </p>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="acceptSuggestedCategory"
                                    class="px-2.5 py-1 text-[10px] font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-lg transition-colors">
                                    Use {{ $suggestedCategoryName }}
                                </button>
                                <button type="button" wire:click="dismissSuggestion"
                                    class="px-2.5 py-1 text-[10px] font-bold text-slate-500 hover:text-slate-700 transition-colors">
                                    Keep current
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Row 3: Amount & Date --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label for="amount" class="block text-xs font-bold text-slate-700">Amount</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-800 font-extrabold text-base">₱</span>
                        <input id="amount" type="number" step="0.01" min="0" wire:model.defer="amount" placeholder="0.00"
                            onblur="formatAmount(this)"
                            class="w-full pl-9 pr-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-extrabold text-base placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    @error('amount')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>
                <div class="space-y-1.5">
                    <label for="transaction_date" class="block text-xs font-bold text-slate-700">Date</label>
                    <input id="transaction_date" type="date" wire:model.defer="transaction_date"
                        class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-800 font-semibold text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('transaction_date')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>
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

    // Livewire 2's dispatchBrowserEvent fires on window, not document.
    window.addEventListener('expense-added', () => {
        document.getElementById('item_name')?.focus();
    });

    window.addEventListener('focus-amount', () => {
        const el = document.getElementById('amount');
        if (el) { el.focus(); el.select(); }
    });
</script>