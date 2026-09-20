@php
    $steps = [
        1 => 'Weekly Allowance',
        2 => 'Track Spending',
        3 => 'Savings Goal',
        4 => 'Smart Alerts',
    ];
    $totalSteps = \App\Http\Livewire\Student\BudgetSetup::TOTAL_STEPS;
@endphp

<div class="min-h-screen bg-[#f4f6fa] flex items-center justify-center p-4 sm:p-8 font-sans"
     style="--brand: {{ $appSettings->primary_color }}; --brand-rgb: {{ $appSettings->primaryColorRgb() }}; --brand-dark: {{ $appSettings->primaryColorDark() }};">
    <div class="w-full max-w-2xl space-y-6">

        {{-- Header --}}
        <div class="text-center space-y-3">
            <div class="h-12 w-12 mx-auto rounded-2xl flex items-center justify-center text-white shrink-0 overflow-hidden bg-[var(--brand)] shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.3)]">
                @if($appSettings->logo_path)
                    <img src="{{ Storage::url($appSettings->logo_path) }}" alt="{{ $appSettings->application_name }}" class="h-full w-full object-cover">
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12V7H5a2 2 0 010-4h14v4M3 5v14a2 2 0 002 2h16v-5M18 12a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                @endif
            </div>
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Let's Set Up Your Budget</h1>
                <p class="text-sm text-slate-500 font-medium">Welcome to {{ $appSettings->application_name }}. This only takes about one minute.</p>
            </div>
        </div>

        {{-- Stepper --}}
        <div class="flex items-start justify-between">
            @foreach($steps as $n => $label)
                <div class="flex-1 flex flex-col items-center gap-1.5 relative">
                    @if($n > 1)
                        <div class="absolute top-4 right-1/2 w-full h-0.5 {{ $step >= $n ? 'bg-[var(--brand)]' : 'bg-slate-200' }}"></div>
                    @endif
                    <div class="relative z-10 h-8 w-8 rounded-full flex items-center justify-center text-xs font-black transition-all
                        {{ $step > $n ? 'bg-[var(--brand)] text-white' : ($step === $n ? 'bg-[var(--brand)] text-white ring-4 ring-[rgba(var(--brand-rgb),0.15)]' : 'bg-white text-slate-400 border border-slate-200') }}">
                        @if($step > $n)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        @else
                            {{ $n }}
                        @endif
                    </div>
                    <span class="text-[10px] sm:text-[11px] font-bold text-center {{ $step >= $n ? 'text-[var(--brand)]' : 'text-slate-400' }}">{{ $label }}</span>
                </div>
            @endforeach
        </div>

        <p class="text-center text-xs font-bold text-[var(--brand)]">Step {{ $step }} of {{ $totalSteps }}: {{ $steps[$step] }}</p>

        {{-- Card --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-10 min-h-[340px]">

            {{-- STEP 1 --}}
            @if($step === 1)
                @php $fc = $this->firstCycle; @endphp
                <div class="space-y-6">
                    <div class="text-center space-y-1">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">What's your weekly allowance?</h2>
                        <p class="text-sm text-slate-500">We'll use this to calculate a calm, realistic daily budget.</p>
                    </div>

                    <div class="max-w-sm mx-auto space-y-4">
                        <div class="space-y-1.5">
                            <label for="total_allowance" class="block text-xs font-bold text-slate-700">Weekly Allowance</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                                <input id="total_allowance" type="number" step="0.01" min="0" placeholder="0.00" autofocus
                                    wire:model.lazy="total_allowance"
                                    wire:keyup.enter="next"
                                    onblur="formatMoney(this)"
                                    class="w-full pl-9 pr-4 py-3.5 bg-slate-50 border rounded-2xl text-2xl font-black text-slate-900 placeholder-slate-300 focus:bg-white focus:outline-none focus:ring-2 transition-all
                                    @error('total_allowance') border-rose-300 focus:border-rose-500 focus:ring-rose-500/20 @else border-slate-200 focus:border-[var(--brand)] focus:ring-[rgba(var(--brand-rgb),0.2)] @enderror">
                            </div>
                            @error('total_allowance') <span class="text-xs text-rose-600 font-medium">{{ $message }}</span> @enderror
                        </div>

                        {{-- Quick select --}}
                        <div class="flex flex-wrap justify-center gap-2">
                            @foreach([500, 1000, 1500, 2000] as $preset)
                                <button type="button"
                                    wire:click="$set('total_allowance', '{{ number_format($preset, 2, '.', '') }}')"
                                    class="px-3 py-1.5 text-xs font-bold font-mono rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors">
                                    ₱{{ number_format($preset) }}
                                </button>
                            @endforeach
                        </div>

                        @if($fc['perDay'] > 0)
                            <div class="text-center space-y-1.5">
                                <span class="inline-block px-4 py-1.5 rounded-full bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] text-xs font-bold">
                                    That's about ₱{{ number_format($fc['perDay'], 2) }} per day
                                </span>
                                <p class="text-[11px] text-slate-400 font-medium">
                                    Your first week runs {{ $fc['days'] }} {{ Str::plural('day', $fc['days']) }}, until {{ $fc['nextReset']->format('l, M j') }}. Every week after that is 7 days.
                                </p>
                            </div>
                        @endif

                        <div class="space-y-1.5">
                            <label for="reset_day" class="block text-xs font-bold text-slate-700">Weekly Reset Day</label>
                            <select id="reset_day" wire:model="reset_day"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:border-[var(--brand)] focus:ring-[rgba(var(--brand-rgb),0.2)] transition-all">
                                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400 font-medium">Unspent money rolls over to your next week automatically.</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- STEP 2 --}}
            @if($step === 2)
                <div class="space-y-6">
                    <div class="text-center space-y-1">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Know what's safe to spend</h2>
                        <p class="text-sm text-slate-500">Log purchases in seconds and let {{ $appSettings->application_name }} do the math.</p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="h-10 w-10 rounded-xl bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Daily Safe-to-Spend</h3>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">Your remaining allowance divided by the days left this week. Stay under it and your money lasts until reset day.</p>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-3">
                            <div class="flex items-start gap-4">
                                <div class="h-10 w-10 rounded-xl bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-extrabold text-slate-900">Log an expense manually</h3>
                                    <p class="text-xs text-slate-500 font-medium mt-0.5">Pick a category, enter the amount, done. Use "Save &amp; Add Another" to log several at once.</p>
                                </div>
                            </div>

                            @if($categories->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5 pl-14">
                                    @foreach($categories as $cat)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold {{ $cat->color }}">
                                            <x-category-icon :type="$cat->icon" />
                                            {{ $cat->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="h-10 w-10 rounded-xl bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Scan a receipt</h3>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">Snap a photo and AI reads the merchant, date, and every item. You review before anything is saved.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- STEP 3 --}}
            @if($step === 3)
                <div class="space-y-6">
                    <div class="text-center space-y-1">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Saving for something?</h2>
                        <p class="text-sm text-slate-500">Optional. Set a goal now, or add one anytime later.</p>
                    </div>

                    <div class="max-w-sm mx-auto space-y-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Goal name</label>
                            <input type="text" wire:model.defer="goal_name" placeholder="e.g. Exam fees, New laptop"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:border-[var(--brand)] focus:ring-[rgba(var(--brand-rgb),0.2)] transition-all">
                            @error('goal_name') <span class="text-xs text-rose-600 font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Goal amount</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold text-sm">₱</span>
                                <input type="number" step="0.01" min="0" wire:model.defer="goal_amount" placeholder="0.00"
                                    onblur="formatMoney(this)"
                                    class="w-full pl-9 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-900 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:border-[var(--brand)] focus:ring-[rgba(var(--brand-rgb),0.2)] transition-all">
                            </div>
                            @error('goal_amount') <span class="text-xs text-rose-600 font-medium">{{ $message }}</span> @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Target date <span class="font-normal text-slate-400">(optional)</span></label>
                            <input type="date" wire:model.defer="goal_date"
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:border-[var(--brand)] focus:ring-[rgba(var(--brand-rgb),0.2)] transition-all">
                            @error('goal_date') <span class="text-xs text-rose-600 font-medium">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- STEP 4 --}}
            @if($step === 4)
                <div class="space-y-6">
                    <div class="text-center space-y-1">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">We've got your back</h2>
                        <p class="text-sm text-slate-500">{{ $appSettings->application_name }} watches your pace so you don't have to.</p>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="h-10 w-10 rounded-xl bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Smart alerts</h3>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">
                                    Get nudged {{ $appSettings->email_notifications_enabled ? 'in the app and by email' : 'in the app' }} when you're spending too fast, running low, or making a big purchase. Alerts clear themselves once you're back on track.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="h-10 w-10 rounded-xl bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Spending Forecast</h3>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">See where your money is heading by reset day, with budget tips in plain language.</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                            <div class="h-10 w-10 rounded-xl bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">Purchase Simulator</h3>
                                <p class="text-xs text-slate-500 font-medium mt-0.5">Thinking of buying something? Test how it changes your daily limit before you spend.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Navigation --}}
        <div class="flex items-center justify-between">
            <div>
                @if($step > 1)
                    <button type="button" wire:click="back"
                        class="inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-full transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        Back
                    </button>
                @endif
            </div>

            <div class="flex items-center gap-2">
                @if($step === 3)
                    <button type="button" wire:click="skipGoal"
                        class="px-4 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-full transition-all">
                        Skip
                    </button>
                @endif

                @if($step < $totalSteps)
                    <button type="button" wire:click="next" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-7 py-3 bg-[var(--brand)] hover:bg-[var(--brand-dark)] text-white rounded-full font-bold text-sm shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.35)] transition-all active:scale-95 disabled:opacity-50">
                        Continue
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </button>
                @else
                    <button type="button" wire:click="finish" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-7 py-3 bg-[var(--brand)] hover:bg-[var(--brand-dark)] text-white rounded-full font-bold text-sm shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.35)] transition-all active:scale-95 disabled:opacity-50">
                        <span wire:loading.remove wire:target="finish">Start Tracking</span>
                        <span wire:loading wire:target="finish">Setting up...</span>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    // Formats to 2 decimals and notifies Livewire. Both events are dispatched:
    // `change` is what wire:model.lazy listens to, `input` is what wire:model.defer needs.
    function formatMoney(input) {
        if (input.value !== '') {
            const value = parseFloat(input.value);
            if (!isNaN(value)) {
                input.value = value.toFixed(2);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }
</script>