<div class="min-h-screen py-10 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Edit Expense</h1>
            <p class="text-xs font-medium text-slate-500 mt-1">
                Modify your transaction details to update your current budget.
            </p>
        </div>

        <!-- Edit Expense Form Card -->
        <form wire:submit.prevent="updateExpense" class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">
            
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
                        <input id="amount" type="number" step="0.01" wire:model.defer="amount" placeholder="0.00"
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

            <!-- Footer Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}"
                   class="px-5 py-2.5 rounded-full text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all">
                   Cancel
                </a>

                <button type="submit" wire:loading.attr="disabled"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-bold text-xs shadow-lg shadow-indigo-200 transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="updateExpense">Update Expense</span>
                    <span wire:loading wire:target="updateExpense">Saving...</span>
                </button>
            </div>
        </form>

    </div>
</div>