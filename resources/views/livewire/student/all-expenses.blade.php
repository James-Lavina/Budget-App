<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Main Ledger Card Container -->
        <div class="bg-white rounded-2xl sm:rounded-3xl shadow-sm border border-slate-100 overflow-hidden">

            <!-- Ledger Header -->
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="space-y-1">
                    <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Expense Records</h1>
                    <p class="text-xs text-slate-500 font-medium">Complete historical breakdown of your tracked budget items.</p>
                </div>
                <div class="flex items-center gap-2 self-start sm:self-center">
                    <span class="inline-flex items-center text-xs font-bold px-3 py-1.5 bg-slate-100 text-slate-700 rounded-xl">
                        {{ $allExpenses->total() }} {{ Str::plural('Entry', $allExpenses->total()) }}
                    </span>
                    <span class="inline-flex items-center text-xs font-bold px-3 py-1.5 bg-indigo-50 text-indigo-700 border border-indigo-100/80 rounded-xl">
                        ₱{{ number_format($totalSpent, 2) }} Total
                    </span>
                </div>
            </div>

            <!-- Search & Filter Control Toolbar -->
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex flex-col sm:flex-row items-center gap-3">
                <!-- Search Input -->
                <div class="relative w-full sm:flex-1">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" wire:model.debounce.300ms="search" placeholder="Search purchase or merchant..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                </div>

                <!-- Category Dropdown -->
                <div class="w-full sm:w-48">
                    <select wire:model="selectedCategory"
                        class="w-full px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Select Page Toggle -->
                <label class="flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-600 cursor-pointer whitespace-nowrap shrink-0 w-full sm:w-auto justify-center sm:justify-start">
                    <input type="checkbox" wire:model="selectAll" class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Select page</span>
                </label>

                <!-- Reset Filters Button -->
                @if($search || $selectedCategory)
                    <button wire:click="clearFilters"
                        class="w-full sm:w-auto px-3.5 py-2.5 text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100/70 rounded-xl transition-all whitespace-nowrap">
                        Clear Filters
                    </button>
                @endif
            </div>

            <!-- Active Filter Indicator -->
            @if($search || $selectedCategory)
                <div class="px-6 py-2.5 bg-indigo-50/50 border-b border-indigo-100 flex items-center gap-2 text-[11px] font-bold text-indigo-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                    </svg>
                    <span>Filtered: {{ $allExpenses->total() }} {{ Str::plural('result', $allExpenses->total()) }}
                        @if($search) for "{{ $search }}" @endif
                        @if($selectedCategory) in {{ $categories->firstWhere('id', $selectedCategory)->name ?? '' }} @endif
                    </span>
                </div>
            @endif

            <!-- Bulk Action Bar -->
            @if(count($selected) > 0)
                <div class="mx-6 mt-4 p-3 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-between gap-3">
                    <span class="text-xs font-bold text-indigo-700">{{ count($selected) }} {{ Str::plural('item', count($selected)) }} selected</span>
                    <div class="flex items-center gap-2">
                        <button wire:click="$set('selected', [])" class="text-xs font-bold text-slate-500 hover:text-slate-700 px-2">
                            Clear
                        </button>
                        <button wire:click="confirmBulkDelete" class="px-3 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors">
                            Delete Selected
                        </button>
                    </div>
                </div>
            @endif

            <!-- Flash Notifications Alerts -->
            @if (session()->has('success'))
                <div class="mx-6 mt-4 p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ session('success') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="mx-6 mt-4 p-3 bg-rose-50 border border-rose-100 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                    {{ session('error') }}
                </div>
            @endif

            @if($allExpenses->isEmpty())
                <!-- Empty State Panel -->
                <div class="p-12 sm:p-16 text-center max-w-sm mx-auto space-y-3">
                    <div class="h-12 w-12 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto text-slate-400 border border-slate-100 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                    @if($search || $selectedCategory)
                        <h3 class="text-sm font-bold text-slate-800">No matching records</h3>
                        <p class="text-xs text-slate-500 leading-normal">
                            No expenses matched your active search or category filters.
                        </p>
                        <button wire:click="clearFilters" class="mt-2 text-xs font-bold text-indigo-600 hover:text-indigo-700">
                            Reset all filters
                        </button>
                    @else
                        <h3 class="text-sm font-bold text-slate-800">Your ledger is empty</h3>
                        <p class="text-xs text-slate-400 leading-normal">
                            There are no logged expenses in your account yet.
                        </p>
                    @endif
                </div>
            @else
                <!-- Expense List Container -->
                <div class="p-4 sm:p-6 divide-y divide-slate-100">
                    @foreach($allExpenses as $expense)
                        <div class="flex items-start gap-3">
                            <input type="checkbox" wire:model="selected" value="{{ $expense->id }}"
                                   class="mt-4 sm:mt-5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 shrink-0">
                            <div class="flex-1 min-w-0">
                                <x-expense-row :expense="$expense" :show-merchant="true" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination Nav System Box -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $allExpenses->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Single-Delete Confirmation Modal -->
    @if($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transform-gpu" wire:click.self="$set('confirmingDeleteId', null)">
            <div class="bg-white rounded-3xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <div class="h-11 w-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Remove this expense?</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">This will refund the amount back to your remaining budget and can't be undone.</p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button wire:click="$set('confirmingDeleteId', null)" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button wire:click="deleteExpense({{ $confirmingDeleteId }})" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Bulk-Delete Confirmation Modal -->
    @if($confirmingBulkDelete)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transform-gpu" wire:click.self="cancelBulkDelete">
            <div class="bg-white rounded-3xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <div class="h-11 w-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Delete {{ count($selected) }} {{ Str::plural('expense', count($selected)) }}?</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">This refunds ₱{{ number_format($bulkDeleteTotal, 2) }} back to your remaining budget and can't be undone.</p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button wire:click="cancelBulkDelete" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">
                        Cancel
                    </button>
                    <button wire:click="bulkDelete" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors">
                        Delete All
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>