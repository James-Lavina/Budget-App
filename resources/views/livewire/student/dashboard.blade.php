@php
    $theme = [
    'depleted'      => ['label' => 'Budget used up',        'pill' => 'bg-rose-100 text-rose-700',       'bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'num' => 'text-rose-600',    'ring' => 'text-rose-500',    'icon' => 'exclamation-circle'],
    'over_quota'    => ['label' => "Over today's limit",    'pill' => 'bg-rose-100 text-rose-700',       'bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'num' => 'text-rose-600',    'ring' => 'text-rose-500',    'icon' => 'exclamation'],
    'pace_critical' => ['label' => 'Spending too fast',     'pill' => 'bg-rose-100 text-rose-700',       'bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'num' => 'text-rose-600',    'ring' => 'text-rose-500',    'icon' => 'trending-up'],
    'quota_hit'     => ['label' => "Today's limit reached", 'pill' => 'bg-amber-100 text-amber-700',     'bg' => 'from-amber-50',   'glow' => 'bg-amber-300/40',   'num' => 'text-amber-600',   'ring' => 'text-amber-500',   'icon' => 'clock'],
    'fresh_start'   => ['label' => 'Fresh start',           'pill' => 'bg-slate-100 text-slate-600',     'bg' => 'from-slate-100',  'glow' => 'bg-slate-300/40',   'num' => 'text-slate-900',   'ring' => 'text-slate-500',   'icon' => 'sparkles'],
    'on_track'      => ['label' => 'On track',              'pill' => 'bg-emerald-100 text-emerald-700', 'bg' => 'from-emerald-50', 'glow' => 'bg-emerald-300/40', 'num' => 'text-emerald-600', 'ring' => 'text-emerald-500', 'icon' => 'check-circle'],
    ];
    $th = $theme[$dashboardState] ?? $theme['on_track'];

    $peso = fn ($n) => '₱' . number_format($n, 2);

    // Only floor the "can spend" figure so the page never promises more than is left.
    $pesoFloor = fn ($n) => '₱' . number_format(floor(round($n * 100, 4)) / 100, 2);

    [$canWhole, $canCents] = explode('.', number_format(floor(round($safeToSpend * 100, 4)) / 100, 2));

    $todayPct = $dailyQuota > 0
        ? min(100, (int) round(($spentToday / $dailyQuota) * 100))
        : ($spentToday > 0 ? 100 : 0);

    $ringC      = 2 * M_PI * 52;                       // circle circumference (r = 52)
    $ringOffset = $ringC * (1 - $todayPct / 100);

    $reset = $currentBudget->reset_day;

    if ($isDepleted) {
        $message = "Your budget is used up. {$daysRemaining} " . Str::plural('day', $daysRemaining) . " to go until {$reset}. Add funds or hold off on spending.";
    } elseif ($isOverQuota) {
        $message = "You're {$peso($overAmount)} over today's limit. Go easy for the rest of today."
            . ($daysAfterToday > 0 ? " From tomorrow you can spend about {$peso($remainingDailyRate)} a day." : '');
    } elseif ($isPaceCritical) {
        $message = "You're spending a bit fast. Keep to about {$peso($remainingDailyRate)} a day so your money lasts until {$reset}.";
    } elseif ($isDailyQuotaHit) {
        $message = "You've reached today's limit. Try not to spend more today."
            . ($daysAfterToday > 0 ? " Tomorrow you'll have about {$peso($remainingDailyRate)}." : '');
    } elseif ($hasNoSpendingYet) {
        $message = "Fresh week! Log your first expense and we'll start tracking your pace.";
    } else {
        $message = "You're doing great. Stay under this amount and your money will last until {$reset}.";
    }
@endphp

<div class="min-h-screen py-3 sm:py-6 md:py-8 px-2.5 sm:px-6 lg:px-8 text-slate-800 antialiased relative pb-28 sm:pb-24 w-full max-w-full overflow-x-hidden">
    <div class="max-w-5xl mx-auto space-y-4 sm:space-y-6 w-full min-w-0">

        {{-- HEADER --}}
        <div class="flex items-center justify-between gap-3 w-full min-w-0">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight truncate">
                    Good {{ \Carbon\Carbon::now()->format('H') < 12 ? 'Morning' : (\Carbon\Carbon::now()->format('H') < 18 ? 'Afternoon' : 'Evening') }}, {{ auth()->user()->name }} 👋
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">
                    {{ $cycleStart->format('M j') }} – {{ $cycleEnd->format('M j, Y') }}
                </p>
            </div>
            <div class="hidden lg:block shrink-0">
                <livewire:student.notification-center />
            </div>
        </div>

        {{-- OVERSPEND ALERT (only when over today's limit, dismissible) --}}
        @if($showOverspendAlert)
            <div wire:key="overspend-alert"
                 class="rounded-2xl sm:rounded-3xl bg-gradient-to-r from-rose-500 to-rose-600 text-white shadow-lg shadow-rose-500/25 p-4 sm:p-5 flex items-start gap-3.5">
                <div class="h-10 w-10 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                    <x-heroicon-o-exclamation class="w-5 h-5" />
                </div>
                <div class="min-w-0 flex-1 space-y-1">
                    <h3 class="text-sm sm:text-base font-extrabold leading-tight">
                        You're {{ $peso($overAmount) }} over today's limit
                    </h3>
                    <a href="{{ route('student.simulation') }}" class="inline-flex items-center gap-1 text-xs font-bold underline underline-offset-2 hover:opacity-80">
                        Check a purchase before you spend
                        <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
                    </a>
                </div>
                <button type="button" wire:click="dismissOverspendAlert" title="Dismiss"
                    class="shrink-0 p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/15 transition-colors">
                    <x-heroicon-o-x class="w-5 h-5" />
                </button>
            </div>
        @endif

        {{-- HERO: ONE QUESTION, ONE ANSWER, TWO ACTIONS --}}
        <div class="relative overflow-hidden rounded-[2rem] border border-slate-100 bg-gradient-to-br {{ $th['bg'] }} via-white to-white shadow-[0_20px_50px_-20px_rgba(15,23,42,0.14)] p-5 sm:p-8">

            {{-- Decorative glow --}}
            <div class="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full blur-3xl {{ $th['glow'] }}"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-20 h-56 w-56 rounded-full blur-3xl bg-[rgba(var(--brand-rgb),0.08)]"></div>

            <div class="relative space-y-6">

                {{-- Top row --}}
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold {{ $th['pill'] }}">
                        <x-dynamic-component :component="'heroicon-o-' . $th['icon']" class="w-3.5 h-3.5" />
                        {{ $th['label'] }}
                    </span>
                    <a href="{{ route('student.budget.add') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/80 backdrop-blur border border-slate-200 text-slate-600 hover:text-[var(--brand)] hover:border-[var(--brand)] text-[11px] font-bold transition-all">
                        <x-heroicon-o-plus-circle class="w-3.5 h-3.5" />
                        Add Funds
                    </a>
                </div>

                {{-- Number + ring --}}
                <div class="flex items-center justify-between gap-4 sm:gap-8">
                    <div class="min-w-0">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-[0.15em] block">You can spend today</span>

                        <div class="mt-2 flex items-start font-black tabular-nums tracking-tighter leading-none {{ $th['num'] }}">
                            <span class="text-2xl sm:text-4xl mt-1.5 sm:mt-2.5 mr-1 opacity-60">₱</span>
                            <span class="text-6xl sm:text-8xl">{{ $canWhole }}</span>
                            <span class="text-xl sm:text-3xl mt-1.5 sm:mt-2.5 opacity-60">.{{ $canCents }}</span>
                        </div>

                        <p class="mt-3 text-xs sm:text-sm font-medium text-slate-500">
                            {{ $peso($spentToday) }} spent of your {{ $pesoFloor($dailyQuota) }} limit
                            @if($savedToday >= 1)
                                <span class="text-slate-400">· incl. {{ $peso($savedToday) }} saved</span>
                            @endif
                        </p>
                    </div>

                    {{-- Progress ring --}}
                    <div class="relative shrink-0 h-24 w-24 sm:h-36 sm:w-36">
                        <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90">
                            <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="10" class="text-slate-200/70" />
                            <circle cx="60" cy="60" r="52" fill="none" stroke="currentColor" stroke-width="10" stroke-linecap="round"
                                    class="{{ $th['ring'] }} transition-all duration-700"
                                    style="stroke-dasharray: {{ $ringC }}; stroke-dashoffset: {{ $ringOffset }};" />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl sm:text-3xl font-black text-slate-900 tabular-nums leading-none">{{ $todayPct }}%</span>
                            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1">of today</span>
                        </div>
                    </div>
                </div>

                {{-- Message --}}
                <div class="flex items-start gap-3 rounded-2xl bg-white/70 backdrop-blur border border-white p-4 shadow-sm">
                    <div class="h-8 w-8 rounded-xl bg-white shadow-sm flex items-center justify-center shrink-0 {{ $th['ring'] }}">
                        <x-dynamic-component :component="'heroicon-o-' . $th['icon']" class="w-4 h-4" />
                    </div>
                    <p class="text-sm font-semibold text-slate-700 leading-snug pt-1">{{ $message }}</p>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col sm:flex-row gap-2.5">
                    <a href="{{ route('student.expenses.create') }}"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl bg-gradient-to-r from-[var(--brand)] to-[var(--brand-dark)] text-white text-sm font-extrabold shadow-lg shadow-[0_10px_20px_-6px_rgba(var(--brand-rgb),0.45)] hover:brightness-110 transition-all active:scale-[0.98]">
                        <x-heroicon-o-plus-circle class="w-5 h-5" />
                        Add expense
                    </a>
                    <a href="{{ route('student.receipt-scanner') }}"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-2xl bg-white/80 backdrop-blur border border-slate-200 hover:border-[var(--brand)] hover:text-[var(--brand)] text-slate-700 text-sm font-extrabold transition-all active:scale-[0.98]">
                        <x-heroicon-o-camera class="w-5 h-5" />
                        Scan receipt
                    </a>
                </div>

                <div class="text-center">
                    <a href="{{ route('student.simulation') }}" class="text-xs font-bold text-slate-500 hover:text-[var(--brand)] transition-colors">
                        Thinking of buying something? Check it first →
                    </a>
                </div>
            </div>
        </div>

        {{-- THREE SMALL FACTS --}}
        <div class="grid grid-cols-3 gap-3 sm:gap-4">
            <div class="rounded-[1.5rem] border border-slate-100 bg-white shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)] p-4 sm:p-5 min-w-0">
                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-[rgba(var(--brand-rgb),0.1)] text-[var(--brand)] flex items-center justify-center">
                    <x-heroicon-o-cash class="w-4 h-4 sm:w-5 sm:h-5" />
                </div>
                <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider block mt-3">Left this week</span>
                <div class="text-base sm:text-xl font-black text-slate-900 tabular-nums mt-0.5 truncate">{{ $peso($currentBudget->remaining_allowance) }}</div>
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-0.5 truncate">
                    of {{ $peso($weeklyAllowanceDisplay) }}
                    <a href="{{ route('student.settings') }}" class="font-bold text-slate-500 hover:text-[var(--brand)]">· Edit</a>
                </p>
            </div>

            <div class="rounded-[1.5rem] border border-slate-100 bg-white shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)] p-4 sm:p-5 min-w-0">
                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                    <x-heroicon-o-calendar class="w-4 h-4 sm:w-5 sm:h-5" />
                </div>
                <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider block mt-3">Days left</span>
                <div class="text-base sm:text-xl font-black text-slate-900 tabular-nums mt-0.5">{{ $daysRemaining === 1 ? 'Last day' : $daysRemaining }}</div>
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-0.5 truncate">Resets {{ $reset }}</p>
            </div>

            <div class="rounded-[1.5rem] border border-slate-100 bg-white shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)] p-4 sm:p-5 min-w-0">
                <div class="h-8 w-8 sm:h-9 sm:w-9 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <x-heroicon-o-save-as class="w-4 h-4 sm:w-5 sm:h-5" />
                </div>
                <span class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider block mt-3">Saved</span>
                <div class="text-base sm:text-xl font-black text-slate-900 tabular-nums mt-0.5">{{ $savingsProgressPercent }}%</div>
                <p class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-0.5 truncate">{{ $peso($totalSavingsSaved) }} in goals</p>
            </div>
        </div>

        {{-- MAIN WORKSPACE: SPENDING CHART + CATEGORY WIDGET (unchanged below this line) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6 items-stretch w-full min-w-0">
            {{-- SPENDING BY DAY --}}
            <div class="lg:col-span-7 rounded-[2rem] border border-slate-100 bg-white shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)] p-4 sm:p-7 flex flex-col justify-between w-full min-w-0 overflow-hidden">
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 truncate">Spending by day</h3>
                        <span class="text-[11px] text-slate-500 font-semibold">Red bars went over your daily safe amount</span>
                    </div>
                    <div class="flex flex-col items-end gap-1 shrink-0">
                        <span class="text-[10px] sm:text-xs text-slate-500 font-medium">
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
                <p class="text-[10px] text-slate-600 font-bold  text-center pt-1 sm:hidden">
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
        <div class="rounded-[2rem] border border-slate-100 bg-white shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)] p-4 sm:p-7 w-full min-w-0 overflow-hidden">
            <div class="pb-4 border-b border-slate-100 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 truncate">Recent Transactions</h3>
                    <p class="text-[12px] text-slate-500 font-medium mt-0.5">Your last 5 transactions</p>
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
        {{-- <div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-49" data-add-expense-widget>
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
        </div> --}}
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
                            ticks: { font: { size: 10 }, color: '#475569' },
                        },
                        y: {
                            stacked: true,
                            grid: { color: '#dcdfe3' },
                            ticks: {
                                font: { size: 9 },
                                color: '#475569',
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