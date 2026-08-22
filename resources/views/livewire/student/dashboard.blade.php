<div class="min-h-screen py-3 sm:py-6 md:py-8 px-2.5 sm:px-6 lg:px-8 text-slate-800 antialiased relative pb-28 sm:pb-24 w-full max-w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto space-y-3 sm:space-y-6 w-full min-w-0">

        <!-- HEADER SECTION: GREETING & STATUS -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 sm:gap-4 w-full min-w-0">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5 truncate">
                    Good {{ \Carbon\Carbon::now()->format('H') < 12 ? 'Morning' : (\Carbon\Carbon::now()->format('H') < 18 ? 'Afternoon' : 'Evening') }}, {{ auth()->user()->name }}
                </h1>
            </div>
            <div class="flex items-center gap-2 self-start md:self-auto shrink-0">
                @if($isSavingsLocked)
                    <span class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs bg-indigo-50 border border-indigo-200 text-indigo-700 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full font-bold">
                        <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                        Saved today
                    </span>
                @elseif($isDailyQuotaHit)
                    <span class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs bg-amber-50 border border-amber-200 text-amber-700 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full font-bold">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        Today's budget used
                    </span>
                @elseif(!$isCriticalState)
                    <span class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs bg-emerald-50 border border-emerald-200/80 text-emerald-700 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full font-bold">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        On track this week
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs bg-rose-50 border border-rose-200/80 text-rose-700 px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full font-bold">
                        <span class="h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
                        Spending Quickly
                    </span>
                @endif
            </div>
        </div>

        <!-- TOP METRICS (RESPONSIVE 3-CARD GRID) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-5 w-full min-w-0">
            <!-- 1. Weekly Allowance -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 border border-slate-100 shadow-sm flex items-center justify-between relative overflow-hidden min-w-0">
                <div class="space-y-1 min-w-0">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500 block truncate">Weekly Allowance</span>
                    <div class="text-lg sm:text-2xl font-black text-slate-900 font-mono truncate">
                        ₱{{ number_format($currentBudget->total_allowance, 2) }}
                    </div>
                    <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-medium text-slate-400 truncate">
                        <span>Resets {{ $currentBudget->reset_day }}</span>
                        <span>·</span>
                        <a href="{{ route('student.settings') }}" class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 font-semibold transition-colors">Edit</a>
                    </div>
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
                <div class="space-y-1 min-w-0">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500 block truncate">Remaining Budget</span>
                    <div class="text-lg sm:text-2xl font-black text-slate-900 font-mono truncate">
                        ₱{{ number_format($currentBudget->remaining_allowance, 2) }}
                    </div>
                    <div class="flex items-center gap-1.5 text-[10px] sm:text-[11px] font-medium text-slate-400 truncate">
                        <span>{{ $remainingPercentage }}% left</span>
                        <span>·</span>
                        <a href="{{ route('student.budget.add') }}" class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 font-semibold transition-colors">Add</a>
                    </div>
                </div>
                <div class="h-9 w-9 sm:h-12 sm:w-12 rounded-xl sm:rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center shrink-0 ml-2">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                </div>
            </div>

            <!-- 3. Daily Safe-to-Spend -->
            <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 border border-slate-100 shadow-sm flex items-center justify-between sm:col-span-2 lg:col-span-1 relative overflow-hidden min-w-0">
                <div class="space-y-1 min-w-0">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500 block truncate">Daily Safe-to-Spend</span>
                    <div class="text-lg sm:text-2xl font-black text-slate-900 font-mono truncate">
                        ₱{{ number_format($safeToSpend, 2) }}
                    </div>
                    <span class="text-[10px] sm:text-[11px] font-medium text-slate-400 block truncate">
                        Per day for the next {{ $daysRemaining }} {{ Str::plural('day', $daysRemaining) }}
                    </span>
                </div>
                <div class="h-9 w-9 sm:h-12 sm:w-12 rounded-xl sm:rounded-2xl {{ $isSavingsLocked ? 'bg-indigo-50 text-indigo-600' : 'bg-emerald-50 text-emerald-600' }} flex items-center justify-center shrink-0 ml-2">
                    <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- SPENDING FORECAST BANNER -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 border border-l-4 shadow-sm transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2.5 sm:gap-4 w-full min-w-0 {{ $isDepleted || $isPaceCritical ? 'border-l-rose-500 border-slate-100' : ($isSavingsLocked ? 'border-l-indigo-500 border-slate-100' : ($isDailyQuotaHit ? 'border-l-amber-500 border-slate-100' : 'border-l-emerald-500 border-slate-100')) }}">
            <div class="flex items-center gap-3 min-w-0">
                <div class="h-9 w-9 sm:h-11 sm:w-11 rounded-xl sm:rounded-2xl flex items-center justify-center shrink-0 {{ $isDepleted || $isPaceCritical ? 'bg-rose-500 text-white' : ($isSavingsLocked ? 'bg-indigo-600 text-white' : ($isDailyQuotaHit ? 'bg-amber-500 text-white' : 'bg-emerald-500 text-white')) }}">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        @if($isSavingsLocked)
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                        @endif
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 flex items-center gap-1 truncate">
                        @if($isDepleted)
                            Budget Exhausted
                        @elseif($isPaceCritical)
                            Spending Warning
                        @elseif($isSavingsLocked)
                            Money Saved Today
                        @elseif($isDailyQuotaHit)
                            Today's Budget Met
                        @else
                            On Track This Week
                        @endif
                    </h3>
                    <p class="text-[11px] sm:text-xs text-slate-600 font-medium mt-0.5 leading-tight">
                        @if($isDepleted)
                            You have ₱0.00 left for the next {{ $daysRemaining }} {{ Str::plural('day', $daysRemaining) }}.
                        @elseif($isPaceCritical)
                            At <span class="font-bold">₱{{ number_format($dailyVelocity, 2) }}/day</span>, your money will only last <span class="font-bold">{{ max(1, round($projectedDaysLeft)) }} {{ Str::plural('day', max(1, round($projectedDaysLeft))) }}</span>.
                        @elseif($isSavingsLocked)
                            Great job! You saved <span class="font-bold">₱{{ number_format($todaySavingsTotal, 2) }}</span> today. Remaining for today: <span class="font-bold">₱{{ number_format($safeToSpend, 2) }}</span>.
                        @elseif($isDailyQuotaHit)
                            Daily budget met. Total remaining: <span class="font-bold">₱{{ number_format($currentBudget->remaining_allowance, 2) }}</span> (<span class="font-bold">₱{{ number_format($remainingDailyRate, 2) }}/day</span> for the next {{ $futureDaysRemaining }} {{ Str::plural('day', $futureDaysRemaining) }}).
                        @else
                            On track to finish the week with ~<span class="font-bold">₱{{ number_format($projectedRemaining, 2) }}</span> remaining.
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('student.forecast') }}" class="text-xs font-bold text-slate-700 hover:text-indigo-600 flex items-center gap-1 whitespace-nowrap self-end sm:self-auto transition-colors shrink-0">
                <span>View forecast</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>

        <!-- MAIN WORKSPACE: SPENDING CHART + CATEGORY WIDGET -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full min-w-0">
            <!-- WEEKLY SPENDING BAR CHART (7 COLS) -->
            <div class="lg:col-span-7 bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 border border-slate-100 shadow-sm flex flex-col justify-between w-full min-w-0 overflow-hidden">
                <!-- HEADING -->
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                    <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 truncate">Weekly Spending</h3>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-[10px] sm:text-xs text-slate-400 font-medium">
                            Spent: <span class="font-bold text-slate-700">₱{{ number_format($totalSpent, 2) }}</span>
                        </span>
                        <span class="text-[10px] sm:text-xs text-slate-400 font-medium">
                            Safe: <span class="font-bold text-slate-600">₱{{ number_format($safeToSpend, 2) }} / day</span>
                        </span>
                    </div>
                </div>

                <!-- WEEKLY SPENDING CATEGORY LEGEND (HORIZONTALLY SCROLLABLE ON MOBILE) -->
                <div class="mt-3 flex overflow-x-auto gap-2 pb-1 sm:pb-0 sm:grid sm:grid-cols-2 max-h-[110px] sm:overflow-y-auto no-scrollbar">
                    @foreach($chartCategories as $index => $catName)
                        @php
                            $catColor = $chartColors[$index] ?? '#5b46f6';
                            $catTotal = $categoryTotalsMap[$catName] ?? 0;
                        @endphp
                        <div class="flex items-center justify-between text-xs bg-slate-50 sm:bg-transparent px-2.5 py-1 sm:p-0 rounded-lg shrink-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $catColor }};"></span>
                                <span class="font-semibold text-slate-600 truncate">{{ $catName }}</span>
                            </div>
                            <span class="font-black text-slate-900 font-mono shrink-0 pl-2">
                                ₱{{ number_format($catTotal, 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                @php
                    $chartLabels = array_values($daysOfWeek);
                    $chartMatrix = [];
                    foreach ($chartCategories as $cat) {
                        $row = [];
                        foreach (array_keys($daysOfWeek) as $dateKey) {
                            $row[] = round($dailyCategoryBreakdown[$dateKey][$cat] ?? 0, 2);
                        }
                        $chartMatrix[$cat] = $row;
                    }
                    $weeklyChartPayload = [
                        'labels'     => $chartLabels,
                        'categories' => $chartCategories,
                        'colors'     => $chartColors,
                        'matrix'     => $chartMatrix,
                    ];
                @endphp

                <script type="application/json" id="weeklySpendingData">{!! json_encode($weeklyChartPayload) !!}</script>

                <div class="pt-4 sm:pt-6 pb-1 w-full min-w-0" wire:ignore>
                    <div class="relative h-40 sm:h-52 w-full">
                        <canvas id="weeklySpendingChart"></canvas>
                    </div>
                </div>

                <p class="text-[10px] text-slate-400 font-medium text-center pt-1 sm:hidden">
                    Tap a bar for category breakdown
                </p>
            </div>

            <!-- EXPENSE CATEGORIES WIDGET (5 COLS) -->
            <div class="lg:col-span-5 flex flex-col w-full min-w-0 overflow-hidden">
                <livewire:student.expense-category-widget />
            </div>
        </div>

        <!-- RECENT EXPENSES SECTION -->
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-slate-100 shadow-sm w-full min-w-0 overflow-hidden">
            <div class="pb-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 truncate">Recent Expenses</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Your last 5 transactions</p>
                </div>
                <a href="{{ route('student.expenses.index') }}" class="text-xs sm:text-sm font-semibold text-indigo-600 hover:text-indigo-700 transition-colors flex items-center gap-1 shrink-0">
                    <span>View all</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>

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
                        <x-expense-row :expense="$expense" />
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

    <!-- DELETE CONFIRMATION MODAL -->
    @if($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="$set('confirmingDeleteId', null)">
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
</div>

<script>
    (function () {
        let weeklySpendingChart = null;

        function readPayload() {
            const el = document.getElementById('weeklySpendingData');
            return el ? JSON.parse(el.textContent) : null;
        }

        function buildDatasets(payload) {
            return payload.categories.map((cat, i) => ({
                label: cat,
                backgroundColor: payload.colors[i],
                hoverBackgroundColor: payload.colors[i],
                data: payload.matrix[cat],
                borderRadius: 4,
                borderSkipped: false,
                maxBarThickness: 28,
            }));
        }

        function initWeeklySpendingChart() {
            const canvas = document.getElementById('weeklySpendingChart');
            const payload = readPayload();
            if (!canvas || !payload) return;

            if (weeklySpendingChart) {
                weeklySpendingChart.destroy();
            }

            weeklySpendingChart = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: payload.labels,
                    datasets: buildDatasets(payload),
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            filter: (item) => item.raw > 0,
                            padding: 10,
                            bodyFont: { size: 11, weight: 'bold' },
                            footerFont: { size: 11, weight: 'bold' },
                            footerMarginTop: 6,
                            callbacks: {
                                label: (ctx) =>
                                    ` ${ctx.dataset.label}: ₱${ctx.raw.toLocaleString(undefined, { minimumFractionDigits: 2 })}`,
                                footer: (items) => {
                                    const total = items.reduce((sum, item) => sum + item.raw, 0);
                                    return `Total: ₱${total.toLocaleString(undefined, { minimumFractionDigits: 2 })}`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: { font: { size: 10 }, color: '#94a3b8' },
                        },
                        y: {
                            stacked: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { size: 9 },
                                color: '#94a3b8',
                                callback: (val) => '₱' + val,
                            },
                        },
                    },
                },
            });
        }

        document.addEventListener('livewire:load', initWeeklySpendingChart);
        document.addEventListener('livewire:update', initWeeklySpendingChart);
    })();
</script>