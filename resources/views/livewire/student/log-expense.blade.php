<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl mx-auto">
        
        <div class="mb-6">
            <a href="{{ route('student.dashboard') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500 transition gap-1">
                ← Back to Dashboard
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
            <div class="border-b border-slate-100 pb-5 mb-6">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Log Manual Expense</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Input your receipt or transaction details to adjust your Safe-to-Spend framework.
                </p>
            </div>

            <form wire:submit.prevent="storeExpense" class="space-y-5">
                
                <div>
                    <label for="expense_category_id" class="block text-sm font-semibold text-slate-700 mb-2">
                        Expense Category
                    </label>
                    <select id="expense_category_id" wire:model="expense_category_id"
                        class="block w-full px-4 py-3 border border-slate-300 rounded-xl bg-white text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition">
                        <option value="">-- Select Category --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('expense_category_id')
                        <span class="text-xs text-red-600 mt-1.5 block font-medium">⚠️ {{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="item_name" class="block text-sm font-semibold text-slate-700 mb-2">
                            Item Name / Description
                        </label>
                        <input id="item_name" type="text" wire:model.defer="item_name" placeholder="e.g., Lunch meal"
                            class="block w-full px-4 py-3 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition">
                        @error('item_name')
                            <span class="text-xs text-red-600 mt-1.5 block font-medium">⚠️ {{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label for="amount" class="block text-sm font-semibold text-slate-700 mb-2">
                            Amount Spent
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-slate-400 font-medium sm:text-sm">₱</span>
                            </div>
                            <input id="amount" type="number" step="0.01" wire:model.defer="amount" placeholder="0.00"
                                class="block w-full pl-9 pr-4 py-3 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition">
                        </div>
                        @error('amount')
                            <span class="text-xs text-red-600 mt-1.5 block font-medium">⚠️ {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="merchant_name" class="block text-sm font-semibold text-slate-700 mb-2">
                            Store / Merchant <span class="text-xs font-normal text-slate-400">(Optional)</span>
                        </label>
                        <input id="merchant_name" type="text" wire:model.defer="merchant_name" placeholder="e.g., Cafeteria"
                            class="block w-full px-4 py-3 border border-slate-300 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition">
                    </div>

                    <div>
                        <label for="transaction_date" class="block text-sm font-semibold text-slate-700 mb-2">
                            Transaction Date
                        </label>
                        <input id="transaction_date" type="date" wire:model.defer="transaction_date"
                            class="block w-full px-4 py-3 border border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition">
                        @error('transaction_date')
                            <span class="text-xs text-red-600 mt-1.5 block font-medium">⚠️ {{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3">
                    <a href="{{ route('student.dashboard') }}"
                        class="px-5 py-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-6 py-3 rounded-xl shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <span wire:loading.remove>Save Expense</span>
                        <span wire:loading>Logging expense...</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>