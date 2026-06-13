<div class="min-h-screen bg-slate-50/50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl mx-auto">

        <!-- Form Card Container -->
        <div class="bg-white rounded-2xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 p-8 space-y-6">
            
            <!-- Context Header with Integrated Breadcrumb -->
            <div class="border-b border-slate-100 pb-5">
                <nav class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">
                    <a href="{{ route('student.dashboard') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
                    <svg class="w-2.5 h-2.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="text-slate-500">Modify Entry</span>
                </nav>
                
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Modify Transaction</h1>
                <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                    Adjust historical ledger parameters. The predictive engine updates running calculations and pacing velocity graphs automatically.
                </p>
            </div>

            <!-- Operational Input Fields Form -->
            <form wire:submit.prevent="updateExpense" class="space-y-5">
                
                <!-- Input: Category Selection -->
                <div class="space-y-1.5">
                    <label for="expense_category_id" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                        Expense Category
                    </label>
                    <select id="expense_category_id" wire:model="expense_category_id"
                        class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('expense_category_id')
                        <div class="p-2.5 bg-rose-50 border border-rose-100 rounded-lg text-rose-800 text-[11px] font-semibold mt-2 flex items-center gap-1.5">
                            <span class="h-1 w-1 rounded-full bg-rose-500"></span>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Input Row: Item Name & Amount -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="item_name" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                            Item Name / Description
                        </label>
                        <input id="item_name" type="text" wire:model.defer="item_name"
                            class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        @error('item_name')
                            <div class="p-2.5 bg-rose-50 border border-rose-100 rounded-lg text-rose-800 text-[11px] font-semibold mt-2 flex items-center gap-1.5">
                                <span class="h-1 w-1 rounded-full bg-rose-500"></span>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="amount" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                            Amount Spent
                        </label>
                        <div class="relative rounded-xl shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <span class="text-slate-400 font-bold text-sm">₱</span>
                            </div>
                            <input id="amount" type="number" step="0.01" wire:model.defer="amount"
                                class="block w-full pl-9 pr-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-bold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        </div>
                        @error('amount')
                            <div class="p-2.5 bg-rose-50 border border-rose-100 rounded-lg text-rose-800 text-[11px] font-semibold mt-2 flex items-center gap-1.5">
                                <span class="h-1 w-1 rounded-full bg-rose-500"></span>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- Input Row: Merchant & Date Selection -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="merchant_name" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                            Store / Merchant <span class="text-[10px] font-bold text-slate-400 lowercase tracking-normal">(optional)</span>
                        </label>
                        <input id="merchant_name" type="text" wire:model.defer="merchant_name"
                            class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label for="transaction_date" class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                            Transaction Date
                        </label>
                        <input id="transaction_date" type="date" wire:model.defer="transaction_date"
                            class="block w-full px-4 py-3 border border-slate-200 bg-slate-50/30 text-sm font-semibold rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition-all">
                        @error('transaction_date')
                            <div class="p-2.5 bg-rose-50 border border-rose-100 rounded-lg text-rose-800 text-[11px] font-semibold mt-2 flex items-center gap-1.5">
                                <span class="h-1 w-1 rounded-full bg-rose-500"></span>
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <!-- Action Confirmation Triggers Footer -->
                <div class="pt-4 flex items-center justify-end gap-2.5">
                    <a href="{{ route('student.dashboard') }}"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-xl shadow-sm text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all disabled:opacity-50">
                        <span wire:loading.remove class="inline-flex items-center gap-1.5">
                            <span>Update Changes</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </span>
                        <span wire:loading class="inline-flex items-center gap-2">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Saving adjustments...</span>
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>