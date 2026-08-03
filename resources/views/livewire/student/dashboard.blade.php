<div class="min-h-screen py-3 sm:py-6 md:py-8 px-2.5 sm:px-6 lg:px-8 text-slate-800 antialiased relative pb-28 sm:pb-24 w-full max-w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto space-y-3 sm:space-y-6 w-full min-w-0">
        
        <!-- DYNAMIC CALCULATIONS & METRICS PREPARATION -->
        @php
            $startDate = \Carbon\Carbon::parse($currentBudget->cycle_start_date);
            $daysElapsed = max(1, $startDate->diffInDays(\Carbon\Carbon::now()) + 1);
            $totalSpent = \App\Models\Expense::where('user_id', auth()->id())
                ->where('transaction_date', '>=', $currentBudget->cycle_start_date)
                ->sum('amount');

            $dailyVelocity = $totalSpent / $daysElapsed;
            $projectedDaysLeft = $dailyVelocity > 0 ? ($currentBudget->remaining_allowance / $dailyVelocity) : $daysRemaining;

            // Triggers critical state if weekly velocity is unsafe OR today's daily safe-to-spend is depleted
            $isCriticalState = ($projectedDaysLeft < $daysRemaining) || ($safeToSpend <= 0);

            $totalAllowance = max(1, $currentBudget->total_allowance);
            $remainingPercentage = round(($currentBudget->remaining_allowance / $totalAllowance) * 100);
        @endphp
 
        <!-- HEADER SECTION: GREETING & STATUS -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 sm:gap-4 w-full min-w-0">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5 truncate">
                    Good {{ \Carbon\Carbon::now()->format('H') < 12 ? 'Morning' : (\Carbon\Carbon::now()->format('H') < 18 ? 'Afternoon' : 'Evening') }}, {{ auth()->user()->name }} 👋
                </h1>
                <p class="text-[11px] sm:text-sm font-medium text-slate-500 mt-0.5 truncate">
                    You're doing great! Keep tracking your spending.
                </p>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
                @if(!$isCriticalState)
                    <span class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs bg-emerald-50 border border-emerald-200/80 text-emerald-700 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full font-bold">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        On track this week
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs bg-rose-50 border border-rose-200/80 text-rose-700 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full font-bold">
                        <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                        High Spending Pace
                    </span>
                @endif
            </div>
        </div>
 
        <!-- TOP METRICS (RESPONSIVE 3-CARD GRID) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-5 w-full min-w-0">
            <!-- 1. Weekly Allowance -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 border border-slate-100 shadow-sm flex items-center justify-between relative overflow-hidden min-w-0">
                <div class="space-y-0.5 min-w-0">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500 block truncate">Weekly Allowance</span>
                    <div class="text-lg sm:text-2xl font-black text-slate-900 font-mono truncate">
                        ₱{{ number_format($currentBudget->total_allowance, 0) }}
                    </div>
                    <span class="text-[10px] sm:text-[11px] font-medium text-slate-400 block truncate">
                        Resets {{ $currentBudget->reset_day }} · <a href="{{ route('student.settings') }}" class="text-slate-500 hover:text-indigo-600 font-semibold underline decoration-slate-300 transition-colors">Edit</a>
                    </span>
                </div>
                <div class="h-9 w-9 sm:h-12 sm:w-12 rounded-xl sm:rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0 ml-2">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
                    </svg>
                </div>
            </div>
 
            <!-- 2. Remaining Budget -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 border border-slate-100 shadow-sm flex items-center justify-between relative overflow-hidden min-w-0">
                <div class="space-y-0.5 min-w-0">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500 block truncate">Remaining Budget</span>
                    <div class="text-lg sm:text-2xl font-black text-slate-900 font-mono truncate">
                        ₱{{ number_format($currentBudget->remaining_allowance, 0) }}
                    </div>
                    <span class="text-[10px] sm:text-[11px] font-medium text-slate-400 block truncate">
                        {{ $remainingPercentage }}% left · <a href="{{ route('student.budget.add') }}" class="text-slate-500 hover:text-indigo-600 font-semibold underline decoration-slate-300 transition-colors">Add</a>
                    </span>
                </div>
                <div class="h-9 w-9 sm:h-12 sm:w-12 rounded-xl sm:rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center shrink-0 ml-2">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-6h6M6 12h12M9 17h6M9 7h6" />
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" />
                    </svg>
                </div>
            </div>
 
            <!-- 3. Daily Safe-to-Spend -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 border border-slate-100 shadow-sm flex items-center justify-between sm:col-span-2 lg:col-span-1 relative overflow-hidden min-w-0">
                <div class="space-y-0.5 min-w-0">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500 block truncate">Daily Safe-to-Spend</span>
                    <div class="text-lg sm:text-2xl font-black text-slate-900 font-mono truncate">
                        ₱{{ number_format($safeToSpend, 0) }}
                    </div>
                    <span class="text-[10px] sm:text-[11px] font-medium text-slate-400 block truncate">
                        For next {{ $daysRemaining }} {{ Str::plural('day', $daysRemaining) }}
                    </span>
                </div>
                <div class="h-9 w-9 sm:h-12 sm:w-12 rounded-xl sm:rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 ml-2">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
        </div>
 
        <!-- SPENDING FORECAST BANNER -->
        <div class="rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 border shadow-sm transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5 sm:gap-4 w-full min-w-0 {{ $isCriticalState ? 'bg-rose-50/70 border-rose-200/80' : 'bg-emerald-50/60 border-emerald-100' }}">
            <div class="flex items-center gap-3 min-w-0">
                <div class="h-9 w-9 sm:h-11 sm:w-11 rounded-xl sm:rounded-2xl flex items-center justify-center shrink-0 {{ $isCriticalState ? 'bg-rose-500 text-white' : 'bg-emerald-500 text-white' }}">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 flex items-center gap-1 truncate">
                        @if($isCriticalState)
                            Spending Anomaly Detected ✨
                        @else
                            You're on track ✨
                        @endif
                    </h3>
                    <p class="text-[11px] sm:text-xs text-slate-600 font-medium mt-0.5 leading-tight">
                        @if($isCriticalState)
                            Pace: <span class="font-bold">₱{{ number_format($dailyVelocity, 2) }}/day</span>. Lasts ~<span class="font-bold">{{ round($projectedDaysLeft, 1) }} days</span>.
                        @else
                            Current pace leaves ~<span class="font-bold">₱{{ number_format(max(0, $currentBudget->remaining_allowance - ($dailyVelocity * $daysRemaining)), 0) }}</span> spare.
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('student.forecast') }}" class="text-xs font-bold {{ $isCriticalState ? 'text-rose-700 hover:text-rose-800' : 'text-emerald-700 hover:text-emerald-800' }} flex items-center gap-1 whitespace-nowrap self-end sm:self-auto transition-colors shrink-0">
                <span>View forecast</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
 
        <!-- MAIN WORKSPACE: SPENDING CHART + CATEGORY WIDGET -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full min-w-0">
            
            <!-- WEEKLY SPENDING BAR CHART (8 COLS) -->
            <div class="lg:col-span-7 bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 border border-slate-100 shadow-sm flex flex-col justify-between w-full min-w-0 overflow-hidden">
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 truncate">Weekly Spending</h3>
                    <span class="text-[10px] sm:text-xs text-slate-400 font-medium shrink-0">
                        Safe: <span class="font-bold text-slate-600">₱{{ number_format($safeToSpend, 0) }}/d</span>
                    </span>
                </div>
 
                @php
                    $daysOfWeek = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    $currentDayName = \Carbon\Carbon::now()->format('D');
                   
                    $dailyTotals = [];
                    foreach($daysOfWeek as $day) {
                        $dailyTotals[$day] = \App\Models\Expense::where('user_id', auth()->id())
                            ->where('transaction_date', '>=', $currentBudget->cycle_start_date)
                            ->whereRaw("DAYNAME(transaction_date) LIKE ?", [\Carbon\Carbon::parse($day)->format('l')])
                            ->sum('amount');
                    }
                    $maxDaily = max(array_merge([$safeToSpend * 1.5, 100], $dailyTotals));
                    $step = $maxDaily / 4;
                @endphp
 
                <div class="pt-5 sm:pt-8 pb-1 w-full min-w-0">
                    <div class="flex gap-1.5 sm:gap-3 items-stretch h-40 sm:h-56 w-full min-w-0">
                        <!-- Y-AXIS NUMERICAL LABELS -->
                        <div class="flex flex-col justify-between text-[8px] sm:text-[10px] font-mono font-semibold text-slate-400 select-none pb-6 text-right w-6 sm:w-10 shrink-0">
                            <span>{{ number_format($maxDaily, 0) }}</span>
                            <span>{{ number_format($step * 3, 0) }}</span>
                            <span>{{ number_format($step * 2, 0) }}</span>
                            <span>{{ number_format($step * 1, 0) }}</span>
                            <span>0</span>
                        </div>
                        
                        <!-- CHART GRID AREA & BARS -->
                        <div class="flex-1 flex flex-col justify-between relative min-w-0">
                            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none pb-6">
                                <div class="border-b border-slate-100 w-full"></div>
                                <div class="border-b border-slate-100 w-full"></div>
                                <div class="border-b border-slate-100 w-full"></div>
                                <div class="border-b border-slate-100 w-full"></div>
                                <div class="border-b border-slate-200 w-full"></div>
                            </div>
                            <div class="flex-1 flex items-end justify-between gap-1 relative z-10 px-0.5 sm:px-2 pb-1 min-w-0">
                                @foreach($daysOfWeek as $day)
                                    @php
                                        $amount = $dailyTotals[$day] ?? 0;
                                        $heightPct = $maxDaily > 0 ? min(100, max(0, round(($amount / $maxDaily) * 100))) : 0;
                                        $isToday = $day === $currentDayName;
                                        $isOverSafe = $amount > $safeToSpend && $safeToSpend > 0;
                                    @endphp
                                   
                                    <div class="relative flex-1 flex flex-col items-center h-full justify-end group cursor-pointer min-w-0">
                                        <!-- HOVER TOOLTIP -->
                                        <div class="absolute -top-8 opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none z-30 bg-slate-900 text-white text-[9px] sm:text-[11px] font-extrabold px-1.5 py-0.5 rounded shadow-xl font-mono whitespace-nowrap transform -translate-y-1 group-hover:translate-y-0">
                                            ₱{{ number_format($amount, 0) }}
                                        </div>
                                        <!-- BAR SLOTS -->
                                        <div class="w-full flex items-end justify-center h-full">
                                            @if($amount > 0)
                                                <div style="height: {{ max(8, $heightPct) }}%;"
                                                     class="w-2.5 sm:w-7 rounded-t-sm sm:rounded-full transition-all duration-300 {{ $isOverSafe ? 'bg-[#ff3b60]' : 'bg-[#5b46f6]' }} group-hover:brightness-110">
                                                </div>
                                            @else
                                                <div class="w-2.5 sm:w-7 h-1 rounded-full bg-slate-100 opacity-60"></div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- X-AXIS DAY LABELS -->
                            <div class="flex justify-between items-center px-0.5 sm:px-2 pt-2 h-5 min-w-0">
                                @foreach($daysOfWeek as $day)
                                    @php $isToday = $day === $currentDayName; @endphp
                                    <span class="flex-1 text-center text-[9px] sm:text-[11px] font-semibold truncate {{ $isToday ? 'text-slate-900 font-black' : 'text-slate-400' }}">
                                        {{ substr($day, 0, 1) }}<span class="hidden sm:inline">{{ substr($day, 1) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
 
            <!-- EXPENSE CATEGORIES WIDGET (5 COLS) -->
            <div class="lg:col-span-5 flex flex-col w-full min-w-0 overflow-hidden">
                <livewire:student.expense-category-widget />
            </div>
        </div>
 
        <!-- RECENT EXPENSES SECTION -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-slate-100 shadow-sm w-full min-w-0 overflow-hidden">
            <!-- HEADER -->
            <div class="pb-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <h3 class="text-base sm:text-lg font-bold text-slate-900 truncate">Recent Expenses</h3>
                <a href="{{ route('student.expenses.index') }}" class="text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition-colors flex items-center gap-1 shrink-0">
                    <span>Show expenses</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>

            <!-- SESSION STATE ALERT TOASTS -->
            @if (session()->has('success'))
                <div class="mt-3 p-2.5 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                    <span class="truncate">{{ session('success') }}</span>
                </div>
            @endif
            @if (session()->has('error'))
                <div class="mt-3 p-2.5 bg-rose-50 border border-rose-100 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                    <span class="truncate">{{ session('error') }}</span>
                </div>
            @endif

            <!-- EXPENSE LIST FEED -->
            @if($recentExpenses->isEmpty())
                <div class="py-10 text-center text-slate-400 max-w-sm mx-auto space-y-2">
                    <div class="h-10 w-10 bg-slate-50 rounded-full flex items-center justify-center mx-auto text-slate-400 border border-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wide">No expenses logged yet</p>
                </div>
            @else
                <div class="divide-y divide-slate-100/80 mt-1">
                    @foreach($recentExpenses as $expense)
                        @php
                            $catName = strtolower($expense->category->name ?? $expense->category ?? '');
                            $itemName = strtolower($expense->item_name);
                            
                            // Dynamic Category Emoji & Badge Colors
                            if (str_contains($catName, 'food') || str_contains($catName, 'snack') || str_contains($itemName, 'lunch') || str_contains($itemName, 'tea') || str_contains($itemName, 'coffee')) {
                                $emoji = '🍔';
                                $bgColor = 'bg-amber-50 text-amber-600';
                            } elseif (str_contains($catName, 'transport') || str_contains($catName, 'fare') || str_contains($itemName, 'jeep') || str_contains($itemName, 'bus') || str_contains($itemName, 'grab')) {
                                $emoji = '🚌';
                                $bgColor = 'bg-blue-50 text-blue-600';
                            } elseif (str_contains($catName, 'school') || str_contains($catName, 'acad') || str_contains($itemName, 'book') || str_contains($itemName, 'pen') || str_contains($itemName, 'print')) {
                                $emoji = '📚';
                                $bgColor = 'bg-emerald-50 text-emerald-600';
                            } elseif (str_contains($catName, 'entertain') || str_contains($catName, 'game') || str_contains($itemName, 'game') || str_contains($itemName, 'steam') || str_contains($itemName, 'movie')) {
                                $emoji = '🎮';
                                $bgColor = 'bg-purple-50 text-purple-600';
                            } elseif (str_contains($catName, 'person') || str_contains($catName, 'shop') || str_contains($itemName, 'shampoo') || str_contains($itemName, 'soap') || str_contains($itemName, 'clothes')) {
                                $emoji = '🛍️';
                                $bgColor = 'bg-pink-50 text-pink-600';
                            } else {
                                $emoji = '💸';
                                $bgColor = 'bg-slate-100 text-slate-600';
                            }

                            // Relative Date Formatting
                            $date = \Carbon\Carbon::parse($expense->transaction_date);
                            if ($date->isToday()) {
                                $formattedDate = 'Today, ' . $date->format('g:i A');
                            } elseif ($date->isYesterday()) {
                                $formattedDate = 'Yesterday';
                            } elseif ($date->greaterThan(now()->subDays(7))) {
                                $formattedDate = $date->format('D');
                            } else {
                                $formattedDate = $date->format('M d');
                            }
                        @endphp

                        <div class="py-3.5 sm:py-4 flex items-center justify-between gap-3 group hover:bg-slate-50/60 -mx-2 px-2 rounded-2xl transition-all">
                            <!-- Left: Circle Icon + Details -->
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="h-10 w-10 sm:h-11 sm:w-11 rounded-full {{ $bgColor }} flex items-center justify-center shrink-0 text-base sm:text-lg shadow-xs">
                                    {{ $emoji }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="font-bold text-slate-900 text-xs sm:text-sm truncate">
                                        {{ $expense->item_name }}
                                    </h4>
                                    <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate mt-0.5">
                                        {{ ucfirst($expense->category->name ?? $expense->category ?? 'General') }} · {{ $formattedDate }}
                                    </p>
                                </div>
                            </div>

                            <!-- Right: Amount & Action Buttons -->
                            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                                <span class="font-bold text-slate-900 text-xs sm:text-sm font-mono tracking-tight">
                                    -₱{{ number_format($expense->amount, 0) }}
                                </span>

                                <!-- Action Buttons: Always visible on mobile, hover-revealed on desktop -->
                                <div class="flex items-center gap-0.5 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity">
                                    <a href="{{ route('student.expenses.edit', $expense->id) }}" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-slate-100 transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
                                        </svg>
                                    </a>
                                    <button wire:click="deleteExpense({{ $expense->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-slate-100 transition-colors" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
 
    <!-- FLOATING "+ ADD EXPENSE" BUTTON -->
    <a href="{{ route('student.expenses.create') }}"
       class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 inline-flex items-center gap-1.5 bg-[#ff6542] hover:bg-[#e85331] text-white font-extrabold px-3.5 sm:px-5 py-2.5 sm:py-3.5 rounded-2xl shadow-lg shadow-orange-500/30 transition-all transform hover:scale-105 active:scale-95 text-xs sm:text-sm">
        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        <span>Add Expense</span>
    </a>
 </div>