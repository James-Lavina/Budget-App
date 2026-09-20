@php
    $riskStyles = [
        'low' => [
            'label'   => 'Low Risk',
            'badge'   => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            'text'    => 'text-emerald-600',
            'bar'     => 'bg-emerald-500',
            'callout' => 'border-l-emerald-500',
            'icon'    => 'text-emerald-500',
        ],
        'medium' => [
            'label'   => 'Medium Risk',
            'badge'   => 'bg-amber-50 text-amber-700 border-amber-100',
            'text'    => 'text-amber-600',
            'bar'     => 'bg-amber-400',
            'callout' => 'border-l-amber-400',
            'icon'    => 'text-amber-500',
        ],
        'high' => [
            'label'   => 'High Risk',
            'badge'   => 'bg-rose-50 text-rose-700 border-rose-100',
            'text'    => 'text-rose-600',
            'bar'     => 'bg-rose-500',
            'callout' => 'border-l-rose-500',
            'icon'    => 'text-rose-500',
        ],
    ];
    $risk = $riskStyles[$riskLevel] ?? $riskStyles['low'];

    // Shared neutral icon chip for every card
    $chip = 'bg-slate-100 text-slate-500';
@endphp

<div class="min-h-screen py-6 sm:py-8 px-3.5 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-4xl mx-auto space-y-5 sm:space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Purchase Simulator</h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">
                See how a planned purchase could affect your weekly budget.
            </p>
        </div>

        @if(!$hasBudget)
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8 text-center space-y-3">
                <p class="text-sm font-bold text-slate-800">Set up your weekly budget first</p>
                <p class="text-xs text-slate-500 font-medium">The simulator needs an active budget to compare against.</p>
                <a href="{{ route('student.budget-setup') }}" class="inline-flex px-5 py-2.5 rounded-2xl bg-[var(--brand)] text-white text-xs font-bold hover:opacity-90 transition">
                    Set up budget
                </a>
            </div>
        @else

            {{-- Overview cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 flex flex-col justify-between min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Weekly Allowance</span>
                        <div class="h-7 w-7 rounded-lg {{ $chip }} flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 font-mono mt-4 whitespace-nowrap">₱{{ number_format($weeklyAllowance, 2) }}</div>
                </div>

                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 flex flex-col justify-between min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Remaining Budget</span>
                        <div class="h-7 w-7 rounded-lg {{ $chip }} flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 font-mono mt-4 whitespace-nowrap">₱{{ number_format($remainingBudget, 2) }}</div>
                </div>

                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 flex flex-col justify-between min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Days Left</span>
                        <div class="h-7 w-7 rounded-lg {{ $chip }} flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 font-mono mt-4 whitespace-nowrap">
                        {{ $daysRemaining === 1 ? 'Last day' : $daysRemaining }}
                    </div>
                </div>

                {{-- Daily Safe-to-Spend --}}
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 flex flex-col justify-between min-w-0">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Daily Safe-to-Spend</span>
                        <div class="h-7 w-7 rounded-lg {{ $chip }} flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4 flex items-baseline flex-wrap gap-x-2 gap-y-1">
                        <span class="text-lg sm:text-xl font-black text-slate-900 font-mono whitespace-nowrap">₱{{ number_format($currentDailyQuota, 2) }}</span>
                        <span class="text-slate-300 font-bold">/</span>
                        <span class="text-sm sm:text-base font-bold text-slate-500 font-mono whitespace-nowrap">₱{{ number_format($spentToday, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form wire:submit.prevent="simulate" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6 space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-5 gap-4">
                    <div class="sm:col-span-3 space-y-1.5">
                        <label for="itemName" class="block text-xs font-bold text-slate-700">Planned Purchase</label>
                        <input id="itemName" type="text" wire:model.defer="itemName"
                            placeholder="Item name — e.g. New Shoes"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:border-[var(--brand)] focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:outline-none transition-all">
                        @error('itemName') <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2 space-y-1.5">
                        <label for="purchaseAmount" class="block text-xs font-bold text-slate-700">Amount</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-extrabold text-sm">₱</span>
                            <input id="purchaseAmount" type="text" inputmode="decimal" wire:model.defer="purchaseAmount"
                                placeholder="0.00"
                                onblur="formatSimulatorAmount(this)"
                                class="w-full pl-9 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-extrabold font-mono text-sm placeholder-slate-400 focus:bg-white focus:border-[var(--brand)] focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:outline-none transition-all">
                        </div>
                        @error('purchaseAmount') <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <button type="submit" wire:loading.attr="disabled" wire:target="simulate,applyPreset"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-[var(--brand)] hover:opacity-90 active:scale-[0.98] text-white text-xs font-bold shadow-md shadow-[0_4px_12px_-2px_rgba(var(--brand-rgb),0.35)] transition-all disabled:opacity-60 self-start">
                        <svg wire:loading.remove wire:target="simulate,applyPreset" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <svg wire:loading wire:target="simulate,applyPreset" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span wire:loading.remove wire:target="simulate,applyPreset">Simulate Purchase</span>
                        <span wire:loading wire:target="simulate,applyPreset">Simulating...</span>
                    </button>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-400">Quick picks:</span>
                        <button type="button" wire:click="applyPreset(50, 'Milk Tea')" class="px-3 py-1.5 text-[11px] font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">₱50 Milk Tea</button>
                        <button type="button" wire:click="applyPreset(150, 'Campus Lunch')" class="px-3 py-1.5 text-[11px] font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">₱150 Lunch</button>
                        <button type="button" wire:click="applyPreset(500, 'Textbooks')" class="px-3 py-1.5 text-[11px] font-bold rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">₱500 Books</button>
                    </div>
                </div>
            </form>

            {{-- Result / Empty state --}}
            @if(!$hasSimulated)
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm py-14 px-6 flex flex-col items-center text-center space-y-3">
                    <div class="h-12 w-12 rounded-2xl {{ $chip }} flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                    <h3 class="text-sm font-extrabold text-slate-800">Before you spend, check the impact</h3>
                    <p class="text-xs text-slate-500 font-medium max-w-xs leading-relaxed">
                        Enter an item and amount above, then simulate to see your budget after the purchase.
                    </p>
                </div>
            @else
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6 space-y-5">

                    {{-- Result header --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-base font-extrabold text-slate-900">After Purchase</h3>
                            <p class="text-xs text-slate-400 font-medium truncate mt-0.5">
                                {{ $simulatedItem }} · ₱{{ number_format($simulatedAmount, 2) }}
                            </p>
                        </div>
                        <span class="shrink-0 inline-flex items-center gap-1.5 px-3 py-1 rounded-full border text-[11px] font-bold {{ $risk['badge'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $risk['bar'] }}"></span>
                            {{ $risk['label'] }}
                        </span>
                    </div>

                    {{-- Result stats --}}
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                        <div class="rounded-2xl border border-slate-100 p-4 flex flex-col justify-between min-w-0">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Remaining Budget</span>
                            <div class="text-base sm:text-lg font-black font-mono mt-2 whitespace-nowrap {{ $isDeficit ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ $isDeficit ? '-' : '' }}₱{{ number_format(abs($newRemaining), 2) }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 p-4 flex flex-col justify-between min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Daily Safe-to-Spend</span>
                            </div>
                            <div class="mt-2 flex items-baseline flex-wrap gap-x-1.5 gap-y-0.5">
                                <span class="text-base sm:text-lg font-black text-slate-900 font-mono whitespace-nowrap">₱{{ number_format($newDailyQuota, 2) }}</span>
                                <span class="text-slate-300 font-bold">/</span>
                                <span class="text-xs sm:text-sm font-bold text-slate-500 font-mono whitespace-nowrap">₱{{ number_format($spentToday, 2) }}</span>
                            </div>
                            @if($dailyQuotaDrop > 0)
                                <div class="text-[10px] font-bold text-rose-500 font-mono mt-1.5">-₱{{ number_format($dailyQuotaDrop, 2) }}/day</div>
                            @endif
                        </div>

                        <div class="rounded-2xl border border-slate-100 p-4">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Budget Status</span>
                            <div class="text-base sm:text-lg font-black mt-2 {{ $risk['text'] }}">{{ $risk['label'] }}</div>
                        </div>

                        <div class="rounded-2xl border border-slate-100 p-4">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Savings Goal</span>
                            <div class="text-sm sm:text-base font-black text-slate-900 mt-2 leading-tight">{{ $savingsImpact }}</div>
                        </div>
                    </div>

                    {{-- Before / After bars --}}
                    <div class="rounded-2xl bg-slate-50/70 border border-slate-100 p-4 sm:p-5 space-y-4">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-700">Before Purchase</span>
                                <span class="font-bold text-slate-700 font-mono">₱{{ number_format($remainingBudget, 2) }} remaining</span>
                            </div>
                            <div class="w-full bg-slate-200/70 h-2.5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-[var(--brand)] transition-all duration-500" style="width: {{ $percentBefore }}%"></div>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-700">After Purchase</span>
                                <span class="font-bold font-mono {{ $isDeficit ? 'text-rose-600' : 'text-slate-700' }}">
                                    @if($isDeficit)
                                        ₱{{ number_format(abs($newRemaining), 2) }} over budget
                                    @else
                                        ₱{{ number_format($newRemaining, 2) }} remaining
                                    @endif
                                </span>
                            </div>
                            <div class="w-full bg-slate-200/70 h-2.5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $risk['bar'] }} transition-all duration-500" style="width: {{ $isDeficit ? 100 : $percentAfter }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Advice --}}
                    <div class="flex items-start gap-3 rounded-2xl border border-slate-100 border-l-4 {{ $risk['callout'] }} p-4">
                        <svg class="w-4 h-4 shrink-0 mt-0.5 {{ $risk['icon'] }}" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="min-w-0">
                            <p class="text-xs sm:text-sm font-semibold text-slate-700 leading-relaxed">{{ $aiInsight }}</p>
                            <span class="inline-block mt-1.5 text-[9px] font-bold uppercase tracking-wider {{ $isOfflineMode ? 'text-slate-400' : 'text-[var(--brand)]' }}">
                                {{ $isOfflineMode ? 'Instant calculation' : 'AI advice' }}
                            </span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end gap-3 pt-1">
                        <button type="button" wire:click="resetSimulation"
                            class="px-4 py-2.5 rounded-2xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition-colors">
                            Cancel
                        </button>

                        @if($isDeficit)
                            <span class="px-5 py-2.5 rounded-2xl bg-slate-100 text-slate-400 text-xs font-bold cursor-not-allowed" title="Not enough remaining budget">
                                Not enough budget
                            </span>
                        @else
                            <button type="button" wire:click="addAsExpense" wire:loading.attr="disabled"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[var(--brand)] hover:opacity-90 active:scale-[0.98] text-white text-xs font-bold shadow-md shadow-[0_4px_12px_-2px_rgba(var(--brand-rgb),0.35)] transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Add as Expense
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>

    <script>
        function formatSimulatorAmount(input) {
            const cleaned = input.value.replace(/,/g, '').trim();
            if (cleaned === '') return;

            const value = parseFloat(cleaned);
            if (isNaN(value)) return;

            input.value = value.toFixed(2);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    </script>
</div>