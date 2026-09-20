@php
    // Status pill + banner copy/color/icon driven by $dashboardState, so the
    // header pill and the "safe to spend" banner never disagree with each
    // other — both read off the same single ranked state the component
    // already computes.
    $statusPillMap = [
        'depleted'       => ['label' => 'Budget exhausted', 'class' => 'bg-rose-50 text-rose-600'],
        'pace_critical'  => ['label' => 'Spending too fast', 'class' => 'bg-rose-50 text-rose-600'],
        'savings_locked' => ['label' => 'Saved today', 'class' => 'bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)]'],
        'quota_hit'      => ['label' => "Today's budget met", 'class' => 'bg-amber-50 text-amber-600'],
        'fresh_start'    => ['label' => 'Fresh start this week', 'class' => 'bg-slate-100 text-slate-500'],
        'on_track'       => ['label' => 'On track this week', 'class' => 'bg-emerald-50 text-emerald-600'],
    ];
    $pill = $statusPillMap[$dashboardState] ?? $statusPillMap['on_track'];

    $bannerMap = [
        'depleted'       => ['class' => 'bg-rose-50 border-rose-100 text-rose-800', 'icon' => 'exclamation-circle'],
        'pace_critical'  => ['class' => 'bg-rose-50 border-rose-100 text-rose-800', 'icon' => 'trending-up'],
        'savings_locked' => ['class' => 'bg-[rgba(var(--brand-rgb),0.06)] border-[rgba(var(--brand-rgb),0.15)] text-[var(--brand)]', 'icon' => 'check-circle'],
        'quota_hit'      => ['class' => 'bg-amber-50 border-amber-100 text-amber-800', 'icon' => 'clock'],
        'fresh_start'    => ['class' => 'bg-slate-50 border-slate-200 text-slate-600', 'icon' => 'sparkles'],
        'on_track'       => ['class' => 'bg-emerald-50 border-emerald-100 text-emerald-800', 'icon' => 'check-circle'],
    ];
    $banner = $bannerMap[$dashboardState] ?? $bannerMap['on_track'];
@endphp

<div class="min-h-screen py-3 sm:py-6 md:py-8 px-2.5 sm:px-6 lg:px-8 text-slate-800 antialiased relative pb-28 sm:pb-24 w-full max-w-full overflow-x-hidden">
    <div class="max-w-7xl mx-auto space-y-3 sm:space-y-6 w-full min-w-0">

        {{-- HEADER: GREETING + STATUS PILL + NOTIFICATIONS --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 sm:gap-4 w-full min-w-0">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-1.5 truncate">
                    Good {{ \Carbon\Carbon::now()->format('H') < 12 ? 'Morning' : (\Carbon\Carbon::now()->format('H') < 18 ? 'Afternoon' : 'Evening') }}, {{ auth()->user()->name }} 👋
                </h1>
                <p class="text-[12px] sm:text-xs md:text-sm text-slate-500 font-medium mt-1">
                    Your allowance covers <span class="font-bold text-slate-700">{{ $cycleStart->format('M j') }} – {{ $cycleEnd->format('j, Y') }}</span>
                    — Resets {{ $currentBudget->reset_day }}, {{ $nextResetDate->format('M j') }}.
                </p>
            </div>
            <div class="flex items-center gap-3 self-start md:self-auto shrink-0">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold {{ $pill['class'] }}">
                    <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                    {{ $pill['label'] }}
                </span>
                <livewire:student.notification-center />
            </div>
        </div>

        {{-- HERO CARD: REMAINING THIS WEEK + STATS (RIGHT) + STATE BANNER (INSIDE CARD) --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl p-5 sm:p-6 border border-slate-100 shadow-sm w-full min-w-0">

            {{-- Date chip + Add Funds --}}
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-bold">
                    <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                    {{ $cycleStart->format('M j') }} – {{ $cycleEnd->format('j, Y') }}
                </span>

                <a href="{{ route('student.budget.add') }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] hover:bg-[rgba(var(--brand-rgb),0.14)] text-[11px] font-bold transition-colors">
                    <x-heroicon-o-plus-circle class="w-3.5 h-3.5" />
                    Add Funds
                </a>
            </div>

            {{-- Top row: remaining amount (left) + compact stats (right) --}}
            <div class="mt-4 flex flex-col lg:flex-row lg:items-start gap-4 lg:gap-6">

                {{-- LEFT: Remaining this week --}}
                <div class="min-w-0 lg:flex-1">
                    <span class="text-xs sm:text-sm font-semibold text-slate-500 block">Remaining this week</span>
                    <div class="text-3xl sm:text-4xl font-black text-slate-900 font-mono mt-1">
                        ₱{{ number_format($currentBudget->remaining_allowance, 2) }}
                    </div>
                    <p class="text-[11px] sm:text-xs font-medium text-slate-400 mt-1">
                        ₱{{ number_format($totalSpent, 2) }} spent of ₱{{ number_format($weeklyAllowanceDisplay, 2) }}
                        · <span class="font-bold text-slate-600">{{ $remainingPercentage }}% left</span>
                    </p>
                    @if($rolloverAmount > 0)
                        <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-bold">
                            <x-heroicon-o-trending-up class="w-3.5 h-3.5" />
                            +₱{{ number_format($rolloverAmount, 2) }} rolled over from last week
                        </div>
                    @endif
                </div>

                {{-- RIGHT: compact stats --}}
                <div class="flex items-start gap-5 sm:gap-6 pt-3 lg:pt-0.5 lg:pl-6 border-t border-slate-100 lg:border-t-0 lg:border-l lg:shrink-0">
                    <div class="min-w-0">
                        <span class="flex items-center gap-1 text-[9px] font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <x-heroicon-o-calendar class="w-3 h-3 shrink-0" />
                            Days Left
                        </span>
                        <span class="text-sm font-extrabold text-slate-900 block mt-0.5 whitespace-nowrap">
                            @if($daysRemaining === 1)
                                Last day · {{ $cycleEnd->format('M j') }}
                            @else
                                {{ $daysRemaining }} · to {{ $cycleEnd->format('M j') }}
                            @endif
                        </span>
                    </div>

                    <div class="min-w-0">
                        <span class="flex items-center gap-1 text-[9px] font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <x-heroicon-o-shield-check class="w-3 h-3 shrink-0" />
                            Safe per Day
                        </span>
                        <span class="text-sm font-extrabold text-slate-900 block mt-0.5 whitespace-nowrap">
                            ₱{{ number_format($dailyQuota, 2) }}
                        </span>
                    </div>

                    <div class="min-w-0">
                        <span class="flex items-center gap-1 text-[9px] font-bold text-slate-400 uppercase tracking-wider whitespace-nowrap">
                            <x-heroicon-o-trending-up class="w-3 h-3 shrink-0" />
                            Biggest Category
                        </span>
                        <span class="text-sm font-extrabold text-slate-900 block mt-0.5 leading-tight max-w-[13rem]">
                            {{ $biggestCategoryName ?? '—' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Progress bar --}}
            <div class="mt-5 space-y-1.5">
                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 {{ (100 - $remainingPercentage) >= 80 ? 'bg-rose-500' : ((100 - $remainingPercentage) >= 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                        style="width: {{ min(100, 100 - $remainingPercentage) }}%"></div>
                </div>
                <div class="flex justify-between text-[10px] font-semibold text-slate-400">
                    <span>₱0</span>
                    <span>₱{{ number_format($weeklyAllowanceDisplay, 0) }}</span>
                </div>
            </div>

            {{-- STATE BANNER (inside the card, below the progress bar) --}}
            <div class="mt-4 rounded-xl border p-3 sm:p-3.5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 {{ $banner['class'] }}">
                <div class="flex items-center gap-2.5">
                    <x-dynamic-component :component="'heroicon-o-' . $banner['icon']" class="w-4 h-4 shrink-0" />
                    <p class="text-xs sm:text-sm font-semibold leading-snug">
                        @if($isDepleted)
                            You've used your full budget. {{ $daysRemaining }} {{ Str::plural('day', $daysRemaining) }} left until reset.
                        @elseif($isPaceCritical)
                            At ₱{{ number_format($dailyVelocity, 2) }}/day, aim for ₱{{ number_format($remainingDailyRate, 2) }}/day to catch back up.
                        @elseif($isSavingsLocked)
                            You saved ₱{{ number_format($todaySavingsTotal, 2) }} today. ₱{{ number_format($safeToSpend, 2) }} left for today.
                        @elseif($isDailyQuotaHit)
                            Today's daily budget is used. ₱{{ number_format($remainingDailyRate, 2) }}/day left for the rest of the week.
                        @elseif($hasNoSpendingYet)
                            You haven't logged anything yet — about ₱{{ number_format($safeToSpend, 2) }}/day if spent evenly.
                        @else
                            You can spend up to ₱{{ number_format($dailyQuota, 2) }} a day and still finish the week on budget.
                        @endif
                    </p>
                </div>
                <a href="{{ route('student.simulation') }}" class="text-xs font-bold flex items-center gap-1 shrink-0 self-end sm:self-auto hover:opacity-75 transition-opacity">
                    <span>Plan a purchase</span>
                    <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
                </a>
            </div>
        </div>

        {{-- 4 METRIC CARDS --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 w-full min-w-0">
            {{-- Total Expenses --}}
            <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm flex flex-col justify-between min-w-0">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Total Expenses</span>
                    <div class="h-7 w-7 rounded-lg bg-rose-50 text-rose-500 flex items-center justify-center shrink-0">
                        <x-heroicon-o-document-report class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-4 whitespace-nowrap">
                    ₱{{ number_format($totalSpent, 2) }}
                </div>
                <div class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-2">
                    {{ 100 - $remainingPercentage }}% of allowance used
                </div>
            </div>

            {{-- Weekly Allowance --}}
            <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm flex flex-col justify-between min-w-0">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Weekly Allowance</span>
                    <div class="h-7 w-7 rounded-lg bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                        {{-- No peso-sign icon exists in the installed Heroicons v1 set —
                             kept as the original manual inline SVG rather than a mismatched icon. --}}
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-4 whitespace-nowrap">
                    ₱{{ number_format($weeklyAllowanceDisplay, 2) }}
                </div>
                <div class="flex items-center gap-1 text-[10px] sm:text-[11px] font-medium text-slate-400 mt-2 flex-wrap">
                    <span>Resets {{ $currentBudget->reset_day }}</span>
                    @if($rolloverAmount > 0)
                        <span>·</span>
                        <span class="text-emerald-600 font-semibold">₱{{ number_format($currentBudget->total_allowance, 2) }} base + ₱{{ number_format($rolloverAmount, 2) }} rollover</span>
                    @endif
                    <span>·</span>
                    <a href="{{ route('student.settings') }}" class="text-slate-600 hover:text-[var(--brand)] font-semibold">Edit</a>
                </div>
            </div>

            {{-- Daily Safe-to-Spend --}}
            <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm flex flex-col justify-between min-w-0">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Daily Safe-to-Spend</span>
                    @php
                        if ($dashboardState === 'depleted' || $dashboardState === 'pace_critical') {
                            $safeToSpendIconStyle = 'bg-rose-50 text-rose-600';
                        } elseif ($dashboardState === 'savings_locked') {
                            $safeToSpendIconStyle = 'bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)]';
                        } elseif ($dashboardState === 'quota_hit') {
                            $safeToSpendIconStyle = 'bg-amber-50 text-amber-600';
                        } else {
                            $safeToSpendIconStyle = 'bg-emerald-50 text-emerald-600';
                        }
                    @endphp
                    <div class="h-7 w-7 rounded-lg {{ $safeToSpendIconStyle }} flex items-center justify-center shrink-0">
                        <x-heroicon-o-shield-check class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="mt-4 flex items-baseline flex-wrap gap-x-2 gap-y-1">
                    <span class="text-xl sm:text-2xl font-black text-slate-900 font-mono whitespace-nowrap">
                        ₱{{ number_format($dailyQuota, 2) }}
                    </span>
                    <span class="text-slate-300 font-bold">/</span>
                    <span class="text-base sm:text-lg font-bold text-slate-500 font-mono whitespace-nowrap">
                        ₱{{ number_format($spentToday, 2) }}
                    </span>
                </div>
                <div class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-2">
                    ₱{{ number_format($safeToSpend, 2) }} left today
                </div>
            </div>

            {{-- Savings Progress --}}
            <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm flex flex-col justify-between min-w-0">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Savings Progress</span>
                    <div class="h-7 w-7 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center shrink-0">
                        <x-heroicon-o-save-as class="w-3.5 h-3.5" />
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-black text-slate-900 font-mono mt-4 whitespace-nowrap">
                    {{ $savingsProgressPercent }}%
                </div>
                <div class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-2">
                    {{ $savingsGoalsCount }} {{ Str::plural('goal', $savingsGoalsCount) }} · ₱{{ number_format($totalSavingsSaved, 2) }} saved
                </div>
            </div>
        </div>

        {{-- QUICK-ACTION WIDGETS: ADD EXPENSE / SCAN RECEIPT / PURCHASE-SIMULATOR --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 w-full min-w-0">
            <a href="{{ route('student.expenses.create') }}"
                class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm flex items-center gap-3.5 hover:border-slate-300 hover:shadow-md transition-all group">
                <div class="h-11 w-11 rounded-2xl bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <x-heroicon-o-plus-circle class="w-5 h-5" />
                </div>
                <div class="min-w-0">
                    <h4 class="text-sm font-extrabold text-slate-900 truncate">Add expenses</h4>
                    <p class="text-[11px] text-slate-400 font-medium truncate">Log what you spent today</p>
                </div>
            </a>

            <a href="{{ route('student.receipt-scanner') }}"
                class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm flex items-center gap-3.5 hover:border-slate-300 hover:shadow-md transition-all group">
                <div class="h-11 w-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <x-heroicon-o-camera class="w-5 h-5" />
                </div>
                <div class="min-w-0">
                    <h4 class="text-sm font-extrabold text-slate-900 truncate">Scan a receipt</h4>
                    <p class="text-[11px] text-slate-400 font-medium truncate">Fill a row automatically</p>
                </div>
            </a>

            <a href="{{ route('student.simulation') }}"
                class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm flex items-center gap-3.5 hover:border-slate-300 hover:shadow-md transition-all group">
                <div class="h-11 w-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                    <x-heroicon-o-calculator class="w-5 h-5" />
                </div>
                <div class="min-w-0">
                    <h4 class="text-sm font-extrabold text-slate-900 truncate">Purchase Simulator</h4>
                    <p class="text-[11px] text-slate-400 font-medium truncate">Check before you spend</p>
                </div>
            </a>
        </div>

        {{-- MAIN WORKSPACE: SPENDING CHART + CATEGORY WIDGET --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full min-w-0">
            {{-- SPENDING BY DAY --}}
            <div class="lg:col-span-7 bg-white rounded-2xl sm:rounded-3xl p-3.5 sm:p-6 border border-slate-100 shadow-sm flex flex-col justify-between w-full min-w-0 overflow-hidden">
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 truncate">Spending by day</h3>
                        <span class="text-[10px] text-slate-400 font-semibold">Red bars went over your daily safe amount</span>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span class="text-[10px] sm:text-xs text-slate-400 font-medium">
                            Safe line <span class="font-bold text-slate-600">₱{{ number_format($dailyQuota, 0) }}/day</span>
                        </span>
                        <a href="{{ route('student.forecast') }}" class="text-[11px] font-bold text-[var(--brand)] flex items-center gap-1 hover:opacity-75 transition-opacity">
                            <span>View forecast</span>
                            <x-heroicon-o-arrow-right class="w-3 h-3" />
                        </a>
                    </div>
                </div>

                <div class="mt-3 flex overflow-x-auto gap-2 pb-1 sm:pb-0 sm:grid sm:grid-cols-2 max-h-[110px] sm:overflow-y-auto no-scrollbar">
                    @foreach($chartCategories as $index => $catName)
                        @php
                            $catColor = $chartColors[$index] ?? $appSettings->primary_color;
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

                @if($totalSavedThisWeek > 0)
                    <div class="mt-3 pt-3 border-t border-dashed border-slate-200 flex items-center justify-between text-xs bg-[rgba(var(--brand-rgb),0.06)] px-2.5 py-2 rounded-lg">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0 bg-[var(--brand)]"></span>
                            <span class="font-semibold text-[var(--brand)]">Saved this week</span>
                        </div>
                        <span class="font-black text-[var(--brand)] font-mono shrink-0 pl-2">
                            ₱{{ number_format($totalSavedThisWeek, 2) }}
                        </span>
                    </div>
                @endif

                @php
                    $chartLabels = [];
                    foreach (array_keys($daysOfWeek) as $dateKey) {
                        $chartLabels[] = [
                            \Carbon\Carbon::parse($dateKey)->format('D'),
                            \Carbon\Carbon::parse($dateKey)->format('M j'),
                        ];
                    }
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

            {{-- WHERE YOUR MONEY WENT --}}
            <div class="lg:col-span-5 flex flex-col w-full min-w-0 overflow-hidden">
                <livewire:student.expense-category-widget />
            </div>
        </div>

        {{-- SAVINGS GOALS PREVIEW (FULL WIDTH) --}}
        <div class="w-full min-w-0">
            <livewire:student.savings-widget />
        </div>

        {{-- RECENT EXPENSES SECTION --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-6 border border-slate-100 shadow-sm w-full min-w-0 overflow-hidden">
            <div class="pb-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 truncate">Recent Transactions</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Your last 5 transactions</p>
                </div>
                <a href="{{ route('student.expenses.index') }}" class="text-xs sm:text-sm font-semibold text-[var(--brand)] hover:opacity-80 transition-colors flex items-center gap-1 shrink-0">
                    <span>View all</span>
                    <x-heroicon-o-arrow-right class="w-4 h-4" />
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
                        <x-heroicon-o-inbox class="w-5 h-5" />
                    </div>
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wide">No expenses logged yet</p>
                </div>
            @else
                <div class="divide-y divide-slate-100/80 mt-1">
                    @foreach($recentExpenses as $expense)
                        @php
                            $isLocked = $expense->transaction_date->lt($cycleStart) || $expense->transaction_date->gt($cycleEnd);
                        @endphp
                        <x-expense-row :expense="$expense" :locked="$isLocked" />
                    @endforeach
                </div>
            @endif
        </div>

        {{-- FLOATING "+ ADD EXPENSE" BUTTON WITH CHOICE MENU --}}
        <div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50" data-add-expense-widget>
            <div data-add-expense-menu class="hidden absolute bottom-full right-0 mb-3 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 p-2 space-y-1">
                <a href="{{ route('student.expenses.create') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <span class="h-8 w-8 rounded-lg bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                        <x-heroicon-o-plus-circle class="w-4 h-4" />
                    </span>
                    Add Manually
                </a>
                <a href="{{ route('student.receipt-scanner') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    <span class="h-8 w-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                        <x-heroicon-o-camera class="w-4 h-4" />
                    </span>
                    Scan Receipt
                </a>
            </div>

            <button type="button" data-add-expense-trigger
                class="inline-flex items-center gap-1.5 bg-[#ff6542] hover:bg-[#e85331] text-white font-extrabold px-3.5 sm:px-5 py-2.5 sm:py-3.5 rounded-2xl shadow-lg shadow-orange-500/30 transition-all transform hover:scale-105 active:scale-95 text-xs sm:text-sm">
                <svg data-add-expense-icon class="w-4 h-4 sm:w-5 sm:h-5 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Add Expense</span>
            </button>
        </div>
    </div>

    {{-- DELETE CONFIRMATION MODAL --}}
    @if($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="$set('confirmingDeleteId', null)">
            <div class="bg-white rounded-3xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <div class="h-11 w-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5" />
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
    if (!window.__addExpenseMenuBound) {
        window.__addExpenseMenuBound = true;
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-add-expense-trigger]');
            if (trigger) {
                event.stopPropagation();
                const widget = trigger.closest('[data-add-expense-widget]');
                const menu = widget.querySelector('[data-add-expense-menu]');
                const icon = widget.querySelector('[data-add-expense-icon]');
                menu.classList.toggle('hidden');
                icon.classList.toggle('rotate-45');
                return;
            }
            if (!event.target.closest('[data-add-expense-menu]')) {
                document.querySelectorAll('[data-add-expense-menu]').forEach(function (el) {
                    el.classList.add('hidden');
                });
                document.querySelectorAll('[data-add-expense-icon]').forEach(function (el) {
                    el.classList.remove('rotate-45');
                });
            }
        });
    }

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