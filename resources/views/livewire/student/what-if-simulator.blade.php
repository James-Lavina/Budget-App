@php
    $theme = [
        'low'    => ['bg' => 'from-emerald-50', 'glow' => 'bg-emerald-300/40', 'pill' => 'bg-emerald-100 text-emerald-700', 'text' => 'text-emerald-600', 'bar' => 'bg-emerald-500', 'icon' => 'check-circle'],
        'medium' => ['bg' => 'from-amber-50',   'glow' => 'bg-amber-300/40',   'pill' => 'bg-amber-100 text-amber-700',     'text' => 'text-amber-600',   'bar' => 'bg-amber-400',   'icon' => 'exclamation-circle'],
        'high'   => ['bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'pill' => 'bg-rose-100 text-rose-700',       'text' => 'text-rose-600',    'bar' => 'bg-rose-500',    'icon' => 'exclamation'],
    ];
    $th = $theme[$riskLevel] ?? $theme['low'];

    $card = 'rounded-[2rem] border border-slate-100 bg-white shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)]';
    $peso = fn ($n) => '₱' . number_format($n, 2);

    if ($hasSimulated) {
        if ($isDeficit) {
            $verdict = "You can't afford this yet";
            $th['icon'] = 'x-circle';
        } elseif ($riskLevel === 'low') {
            $verdict = 'Yes, you can afford it';
        } elseif ($riskLevel === 'medium') {
            $verdict = "You can, but it's over today's limit";
        } else {
            $verdict = 'This would hurt your week';
        }
    }
@endphp

<div class="min-h-screen py-6 sm:py-8 px-3.5 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-2xl mx-auto space-y-5 sm:space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Can I afford this?</h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">
                Type what you want to buy. We'll show what it does to today and the rest of your week, before you spend.
            </p>
        </div>

        @if(!$hasBudget)
            <div class="{{ $card }} p-8 text-center space-y-3">
                <p class="text-sm font-bold text-slate-800">Set up your weekly budget first</p>
                <p class="text-xs text-slate-500 font-medium">The simulator needs an active budget to compare against.</p>
                <a href="{{ route('student.budget-setup') }}" class="inline-flex px-5 py-2.5 rounded-2xl bg-gradient-to-r from-[var(--brand)] to-[var(--brand-dark)] text-white text-xs font-bold hover:brightness-110 transition">
                    Set up budget
                </a>
            </div>
        @else

            {{-- 1. THE QUESTION --}}
            <form wire:submit.prevent="simulate" class="relative overflow-hidden {{ $card }} p-5 sm:p-8 space-y-5">
                <div class="pointer-events-none absolute -top-24 -right-24 h-56 w-56 rounded-full blur-3xl bg-[rgba(var(--brand-rgb),0.12)]"></div>

                <div class="relative space-y-5">
                    <div class="space-y-1.5">
                        <label for="purchaseAmount" class="block text-xs font-bold text-slate-500 uppercase tracking-[0.15em]">How much is it?</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400 font-black text-2xl sm:text-3xl">₱</span>

                            {{-- Hidden input that syncs the clean numeric value to Livewire --}}
                            <input type="hidden" id="purchaseAmount_raw" wire:model.defer="purchaseAmount" value="{{ $purchaseAmount }}">

                            {{-- Visible input with comma formatting --}}
                            <input id="purchaseAmount" type="text" inputmode="decimal" autofocus
                                placeholder="0.00"
                                value="{{ $purchaseAmount ? number_format($purchaseAmount, 2, '.', ',') : '' }}"
                                onblur="formatSimulatorAmount(this)"
                                class="w-full pl-12 sm:pl-14 pr-4 py-4 sm:py-5 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-black tabular-nums text-3xl sm:text-4xl placeholder-slate-300 focus:bg-white focus:border-[var(--brand)] focus:ring-4 focus:ring-[rgba(var(--brand-rgb),0.12)] focus:outline-none transition-all">
                        </div>
                        @error('purchaseAmount') <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="itemName" class="block text-xs font-bold text-slate-500 uppercase tracking-[0.15em]">
                            What is it? <span class="normal-case tracking-normal font-medium text-slate-400">(optional)</span>
                        </label>
                        <input id="itemName" type="text" wire:model.defer="itemName"
                            placeholder="e.g. New shoes"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:border-[var(--brand)] focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:outline-none transition-all">
                        @error('itemName') <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-400">Try:</span>
                        <button type="button" wire:click="applyPreset(50, 'Milk Tea')" class="px-3.5 py-1.5 text-[11px] font-bold rounded-full bg-white border border-slate-200 text-slate-600 hover:border-[var(--brand)] hover:text-[var(--brand)] transition-all">₱50 Milk tea</button>
                        <button type="button" wire:click="applyPreset(150, 'Campus Lunch')" class="px-3.5 py-1.5 text-[11px] font-bold rounded-full bg-white border border-slate-200 text-slate-600 hover:border-[var(--brand)] hover:text-[var(--brand)] transition-all">₱150 Lunch</button>
                        <button type="button" wire:click="applyPreset(500, 'Textbooks')" class="px-3.5 py-1.5 text-[11px] font-bold rounded-full bg-white border border-slate-200 text-slate-600 hover:border-[var(--brand)] hover:text-[var(--brand)] transition-all">₱500 Books</button>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="simulate,applyPreset"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-gradient-to-r from-[var(--brand)] to-[var(--brand-dark)] text-white text-sm font-extrabold shadow-lg shadow-[0_10px_20px_-6px_rgba(var(--brand-rgb),0.45)] hover:brightness-110 active:scale-[0.99] transition-all disabled:opacity-60">
                        <svg wire:loading.remove wire:target="simulate,applyPreset" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg wire:loading wire:target="simulate,applyPreset" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <span wire:loading.remove wire:target="simulate,applyPreset">Check if I can afford it</span>
                        <span wire:loading wire:target="simulate,applyPreset">Checking...</span>
                    </button>

                    {{-- Context in one line --}}
                    <p class="text-center text-[11px] sm:text-xs font-medium text-slate-400">
                        @if($currentOverToday >= 1)
                            You're already <span class="font-bold text-rose-500">{{ $peso($currentOverToday) }} over</span> today's limit
                        @else
                            You have <span class="font-bold text-slate-600">{{ $peso($currentSafeToSpend) }}</span> left today
                        @endif
                        · <span class="font-bold text-slate-600">{{ $peso($remainingBudget) }}</span> left this week
                    </p>
                </div>
            </form>

            {{-- 2. THE ANSWER --}}
            @if($hasSimulated)

                {{-- Verdict --}}
                <div class="relative overflow-hidden rounded-[2rem] border border-slate-100 bg-gradient-to-br {{ $th['bg'] }} via-white to-white shadow-[0_20px_50px_-20px_rgba(15,23,42,0.18)] p-5 sm:p-8">
                    <div class="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full blur-3xl {{ $th['glow'] }}"></div>

                    <div class="relative space-y-4">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold {{ $th['pill'] }}">
                                <x-dynamic-component :component="'heroicon-o-' . $th['icon']" class="w-3.5 h-3.5" />
                                {{ $simulatedItem }} · {{ $peso($simulatedAmount) }}
                            </span>
                        </div>

                        <h2 class="text-2xl sm:text-4xl font-black tracking-tight leading-tight {{ $th['text'] }}">{{ $verdict }}</h2>

                        <div class="rounded-2xl bg-white/70 backdrop-blur border border-white p-4 shadow-sm">
                            <p class="text-sm font-semibold text-slate-700 leading-relaxed">{{ $aiInsight }}</p>
                            <span class="inline-block mt-2 text-[9px] font-extrabold uppercase tracking-[0.15em] {{ $isOfflineMode ? 'text-slate-400' : 'text-[var(--brand)]' }}">
                                {{ $isOfflineMode ? 'Instant calculation' : 'AI advice' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- What it changes --}}
                <div class="{{ $card }} p-5 sm:p-7 space-y-5">
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">What it changes</h3>

                    <div class="divide-y divide-slate-100">

                        {{-- Today --}}
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-10 w-10 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
                                    <x-heroicon-o-sun class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold text-slate-700">Today</span>
                            </div>
                            <div class="text-right min-w-0">
                                @if($newOverToday >= 1)
                                    <div class="text-sm sm:text-base font-black text-rose-600 tabular-nums">{{ $peso($newOverToday) }} over</div>
                                    <div class="text-[11px] font-medium text-slate-400">limit is {{ $peso($currentDailyQuota) }}</div>
                                @else
                                    <div class="text-sm sm:text-base font-black text-slate-900 tabular-nums">{{ $peso($newSafeToSpend) }} left</div>
                                    <div class="text-[11px] font-medium text-slate-400">was {{ $peso($currentSafeToSpend) }}</div>
                                @endif
                            </div>
                        </div>

                        {{-- From tomorrow --}}
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-10 w-10 rounded-2xl bg-[rgba(var(--brand-rgb),0.1)] text-[var(--brand)] flex items-center justify-center shrink-0">
                                    <x-heroicon-o-calendar class="w-5 h-5" />
                                </div>
                                <span class="text-sm font-bold text-slate-700">From tomorrow</span>
                            </div>
                            <div class="text-right min-w-0">
                                @if($daysAfterToday > 0)
                                    <div class="text-sm sm:text-base font-black text-slate-900 tabular-nums">{{ $peso($tomorrowAfter) }} a day</div>
                                    @if($tomorrowDrop >= 1)
                                        <div class="text-[11px] font-bold text-rose-500">down {{ $peso($tomorrowDrop) }} from {{ $peso($tomorrowBefore) }}</div>
                                    @else
                                        <div class="text-[11px] font-medium text-slate-400">no change</div>
                                    @endif
                                @else
                                    <div class="text-sm sm:text-base font-black text-slate-900">Last day</div>
                                    <div class="text-[11px] font-medium text-slate-400">leftover rolls over</div>
                                @endif
                            </div>
                        </div>

                        {{-- Savings goal (only if they have one) --}}
                        {{-- @if($hasGoal)
                            <div class="flex items-center justify-between gap-4 py-3.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="h-10 w-10 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                                        <x-heroicon-o-save-as class="w-5 h-5" />
                                    </div>
                                    <span class="text-sm font-bold text-slate-700 truncate">{{ $savingsGoalName }}</span>
                                </div>
                                <div class="text-right min-w-0">
                                    @if($isDeficit)
                                        <div class="text-[11px] font-bold text-rose-500">No money left to add</div>
                                    @else
                                        <div class="text-sm sm:text-base font-black text-slate-900 tabular-nums">up to {{ $peso($savingsFreeAfter) }}</div>
                                        <div class="text-[11px] font-medium text-slate-400">can still go in · {{ $peso($savingsGoalNeeded) }} to go</div>
                                    @endif
                                </div>
                            </div>
                        @endif --}}
                    </div>

                    {{-- This week: before / after --}}
                    <div class="rounded-2xl bg-slate-50/80 border border-slate-100 p-4 sm:p-5 space-y-4">
                        <p class="text-xs font-extrabold text-slate-700">Left this week</p>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-500">Now</span>
                                <span class="font-bold text-slate-700 tabular-nums">{{ $peso($remainingBudget) }}</span>
                            </div>
                            <div class="w-full bg-slate-200/70 h-2.5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-[var(--brand)] transition-all duration-500" style="width: {{ $percentBefore }}%"></div>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-500">After buying</span>
                                <span class="font-bold tabular-nums {{ $isDeficit ? 'text-rose-600' : 'text-slate-700' }}">
                                    @if($isDeficit)
                                        {{ $peso(abs($newRemaining)) }} short
                                    @else
                                        {{ $peso($newRemaining) }}
                                    @endif
                                </span>
                            </div>
                            <div class="w-full bg-slate-200/70 h-2.5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full {{ $th['bar'] }} transition-all duration-500" style="width: {{ $isDeficit ? 100 : $percentAfter }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2.5">
                        <button type="button" wire:click="resetSimulation"
                            class="px-5 py-3 rounded-2xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Try another
                        </button>

                        @if($isDeficit)
                            <span class="px-5 py-3 rounded-2xl bg-slate-100 text-slate-400 text-xs font-bold text-center cursor-not-allowed" title="Not enough remaining budget">
                                Not enough budget
                            </span>
                        @else
                            <button type="button" wire:click="addAsExpense" wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-2xl bg-gradient-to-r from-[var(--brand)] to-[var(--brand-dark)] text-white text-xs font-extrabold shadow-lg shadow-[0_10px_20px_-6px_rgba(var(--brand-rgb),0.45)] hover:brightness-110 active:scale-[0.98] transition-all">
                                <x-heroicon-o-plus-circle class="w-4 h-4" />
                                I'll buy it — add as expense
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
            const rawInput = document.getElementById('purchaseAmount_raw');

            if (cleaned === '') {
                input.value = '';
                if (rawInput) {
                    rawInput.value = '';
                    rawInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (typeof @this !== 'undefined') {
                    @this.set('purchaseAmount', '');
                }
                return;
            }

            const value = parseFloat(cleaned);
            if (isNaN(value) || value < 0) {
                input.value = '';
                if (rawInput) {
                    rawInput.value = '';
                    rawInput.dispatchEvent(new Event('input', { bubbles: true }));
                }
                if (typeof @this !== 'undefined') {
                    @this.set('purchaseAmount', '');
                }
                return;
            }

            // Format display with commas and 2 decimal places
            input.value = value.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });

            const rawVal = value.toFixed(2);
            if (rawInput) {
                rawInput.value = rawVal;
                rawInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (typeof @this !== 'undefined') {
                @this.set('purchaseAmount', rawVal);
            }
        }
    </script>
</div>