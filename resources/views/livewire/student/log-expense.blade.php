<div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto space-y-6">
        
        <!-- Header Controls matching Scanner View tokens -->
        <div class="flex items-center justify-between">
            <a href="{{ route('student.dashboard') }}" class="text-xs font-bold text-slate-500 hover:text-indigo-600 transition">
                ← Back to Dashboard
            </a>
            <span class="text-xs text-indigo-600 font-bold bg-indigo-50 px-3 py-1 rounded-full">
                Manual Entry Mode
            </span>
        </div>

        <!-- Main Form Container -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8 space-y-6">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Log Manual Expense</h1>
                <p class="text-xs text-slate-500 mt-1">Input your transaction details manually to adjust your budget frameworks.</p>
            </div>

            <form wire:submit.prevent="storeExpense" class="space-y-5">
                
                <!-- ROW 1: Merchant & Description (Unified 2-Column Grid) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="merchant_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Store / Merchant <span class="text-[10px] font-normal text-slate-400 lowercase">(optional)</span>
                        </label>
                        <input id="merchant_name" type="text" wire:model.defer="merchant_name" placeholder="e.g., Cafeteria"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-medium text-slate-800 placeholder-slate-400">
                    </div>
                    
                    <div>
                        <label for="item_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Item Name / Description
                        </label>
                        <input id="item_name" type="text" wire:model.defer="item_name" placeholder="e.g., Lunch meal"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-medium text-slate-800 placeholder-slate-400">
                        @error('item_name') 
                            <span class="text-xs text-rose-600 mt-1 block font-medium">⚠️ {{ $message }}</span> 
                        @enderror
                    </div>
                </div>

                <!-- ROW 2: Date, Amount, & Category (Unified 3-Column Grid) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    
                    <!-- Date Input Component -->
                    <div>
                        <label for="transaction_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Transaction Date
                        </label>
                        <input id="transaction_date" type="date" wire:model.defer="transaction_date"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-bold text-slate-900 bg-white cursor-pointer">
                        @error('transaction_date') 
                            <span class="text-xs text-rose-600 mt-1 block font-medium">⚠️ {{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Currency/Amount Input Component -->
                    <div>
                        <label for="amount" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Total Amount (₱)
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-slate-500 text-sm font-bold">₱</span>
                            </div>
                            <input id="amount" type="number" step="0.01" wire:model.defer="amount" placeholder="0.00"
                                class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-bold text-slate-900 placeholder-slate-400">
                        </div>
                        @error('amount') 
                            <span class="text-xs text-rose-600 mt-1 block font-medium">⚠️ {{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Category Selector Component -->
                    <div>
                        <label for="expense_category_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Expense Category
                        </label>
                        <select id="expense_category_id" wire:model="expense_category_id" 
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-indigo-500 text-sm font-bold text-slate-800 bg-white cursor-pointer">
                            <option value="">-- Select --</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}"> {{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('expense_category_id') 
                            <span class="text-xs text-rose-600 mt-1 block font-medium">⚠️ {{ $message }}</span> 
                        @enderror
                    </div>
                </div>

                <!-- Unified Action Button Controls -->
                <div class="flex gap-3 pt-2">
                    <a href="{{ route('student.dashboard') }}" 
                        class="w-1/3 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs text-center transition uppercase tracking-wider block shadow-sm">
                        Cancel
                    </a>
                    
                    <button type="submit" wire:loading.attr="disabled" 
                        class="w-2/3 py-3 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-bold text-xs transition shadow-md uppercase tracking-wider">
                        <span wire:loading.remove wire:target="storeExpense">Save Manual Entry ✓</span>
                        <span wire:loading wire:target="storeExpense" class="inline-flex items-center gap-2">
                            <div class="animate-spin rounded-full h-3 w-3 border-2 border-white border-t-transparent"></div>
                            Logging expense...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>