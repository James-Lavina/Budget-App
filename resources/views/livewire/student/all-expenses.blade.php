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

                <!-- Reset Filters Button -->
                @if($search || $selectedCategory)
                    <button wire:click="clearFilters"
                        class="w-full sm:w-auto px-3.5 py-2.5 text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100/70 rounded-xl transition-all whitespace-nowrap">
                        Clear Filters
                    </button>
                @endif
            </div>

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
                        @php
                            $catName = strtolower($expense->category->name ?? $expense->category ?? '');
                            $itemName = strtolower($expense->item_name);
                         
                            if (str_contains($catName, 'food') || str_contains($catName, 'snack') || str_contains($itemName, 'lunch') || str_contains($itemName, 'tea') || str_contains($itemName, 'coffee')) {
                                $bgColor = 'bg-amber-50 text-amber-600';
                                $iconType = 'food';
                            } elseif (str_contains($catName, 'transport') || str_contains($catName, 'fare') || str_contains($itemName, 'jeep') || str_contains($itemName, 'bus') || str_contains($itemName, 'grab')) {
                                $bgColor = 'bg-blue-50 text-blue-600';
                                $iconType = 'transport';
                            } elseif (str_contains($catName, 'school') || str_contains($catName, 'acad') || str_contains($itemName, 'book') || str_contains($itemName, 'pen') || str_contains($itemName, 'print')) {
                                $bgColor = 'bg-emerald-50 text-emerald-600';
                                $iconType = 'school';
                            } elseif (str_contains($catName, 'entertain') || str_contains($catName, 'game') || str_contains($itemName, 'game') || str_contains($itemName, 'steam') || str_contains($itemName, 'movie')) {
                                $bgColor = 'bg-purple-50 text-purple-600';
                                $iconType = 'entertainment';
                            } elseif (str_contains($catName, 'person') || str_contains($catName, 'shop') || str_contains($itemName, 'shampoo') || str_contains($itemName, 'soap') || str_contains($itemName, 'clothes')) {
                                $bgColor = 'bg-pink-50 text-pink-600';
                                $iconType = 'shopping';
                            } else {
                                $bgColor = 'bg-slate-100 text-slate-600';
                                $iconType = 'default';
                            }

                            $date = \Carbon\Carbon::parse($expense->transaction_date);
                            if ($date->isToday()) {
                                $formattedDate = 'Today, ' . $date->format('g:i A');
                            } elseif ($date->isYesterday()) {
                                $formattedDate = 'Yesterday, ' . $date->format('g:i A');
                            } elseif ($date->greaterThan(now()->subDays(7))) {
                                $formattedDate = $date->format('D, g:i A');
                            } else {
                                $formattedDate = $date->format('M d, Y • g:i A');
                            }
                            $merchant = !empty($expense->merchant_name) ? ' · ' . $expense->merchant_name : '';
                        @endphp
                        
                        <div class="py-3.5 sm:py-4 flex items-center justify-between gap-3 group hover:bg-slate-50/70 -mx-2 px-2 rounded-2xl transition-all">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-2xl {{ $bgColor }} flex items-center justify-center shrink-0 shadow-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        @if($iconType === 'food')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v-1.5m0 1.5c-1.355 0-2.697.056-4.024.166C6.845 8.51 6 9.473 6 10.608v2.513m6-4.871c1.355 0 2.697.056 4.024.166C17.155 8.51 18 9.473 18 10.608v2.513M15 8.25v-1.5m-6 1.5v-1.5m12 9.75-1.5.75m-15-.75 1.5.75m12-3.75h3m-18 0h3m0 0v2.25c0 .414.336.75.75.75h10.5a.75.75 0 0 0 .75-.75V12M6 12a2.25 2.25 0 0 0-2.25 2.25v.75c0 .414.336.75.75.75h15a.75.75 0 0 0 .75-.75v-.75A2.25 2.25 0 0 0 18 12H6Z" />
                                        @elseif($iconType === 'transport')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                        @elseif($iconType === 'school')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18c-2.305 0-4.408.867-6 2.292m0-14.25v14.25" />
                                        @elseif($iconType === 'entertainment')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                                        @elseif($iconType === 'shopping')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119.993Z" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5a1.5 1.5 0 0 1 1.5 1.5v9.75a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5Zm13.5 3h.008v.008h-.008V7.5Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z" />
                                        @endif
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-slate-900 text-xs sm:text-sm truncate">
                                        {{ $expense->item_name }}
                                    </h4>
                                    <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate mt-0.5">
                                        {{ ucfirst($expense->category->name ?? $expense->category ?? 'General') }}{{ $merchant }} · {{ $formattedDate }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                <span class="font-bold text-slate-900 text-xs sm:text-sm font-mono tracking-tight">
                                    -₱{{ number_format($expense->amount, 2) }}
                                </span>
                                
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('student.expenses.edit', $expense->id) }}" 
                                       class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 transition-colors" 
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                                        </svg>
                                    </a>
                                    <button wire:click="deleteExpense({{ $expense->id }})" 
                                            onclick="confirm('Are you sure you want to remove this transaction?') || event.stopImmediatePropagation()" 
                                            class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 transition-colors" 
                                            title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
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
</div>