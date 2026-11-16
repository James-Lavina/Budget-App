@php
    $s         = $this->summary;
    $allowance = is_numeric($total_allowance) ? (float) $total_allowance : 0;
    $newTotal  = $allowance + $s['unspent'];
    $perDay    = $allowance > 0 ? $newTotal / max(1, $s['daysLeft']) : 0;

    $card = 'rounded-[2rem] border border-slate-100 bg-white shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)]';
    $peso = fn ($n) => '₱' . number_format($n, 2);

    [$totalWhole, $totalCents] = explode('.', number_format($newTotal, 2));
@endphp

<div class="min-h-screen bg-gradient-to-b from-[rgba(var(--brand-rgb),0.06)] via-[#f4f6fa] to-[#f4f6fa] flex items-center justify-center p-4 sm:p-8 font-sans"
     style="--brand: {{ $appSettings->primary_color }}; --brand-rgb: {{ $appSettings->primaryColorRgb() }}; --brand-dark: {{ $appSettings->primaryColorDark() }};">

    <div class="w-full max-w-xl space-y-5 sm:space-y-6">

        {{-- Header --}}
        <div class="text-center space-y-3">
            <div class="h-14 w-14 mx-auto rounded-2xl flex items-center justify-center text-white shrink-0 overflow-hidden bg-gradient-to-br from-[var(--brand)] to-[var(--brand-dark)] shadow-[0_10px_24px_-6px_rgba(var(--brand-rgb),0.45)]">
                @if($appSettings->logo_path)
                    <img src="{{ Storage::url($appSettings->logo_path) }}" alt="{{ $appSettings->application_name }}" class="h-full w-full object-cover">
                @else
                    <x-heroicon-o-sparkles class="w-7 h-7" />
                @endif
            </div>
            <div class="space-y-1.5">
                <h1 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">New week, fresh start</h1>
                <p class="text-sm text-slate-500 font-medium">
                    Your last week ({{ $s['startDate']->format('M j') }} – {{ $s['endDate']->format('M j') }}) is done.
                    Set your allowance to begin this one.
                </p>
            </div>
        </div>

        {{-- Last week recap --}}
        <div class="{{ $card }} p-5 sm:p-6 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-slate-400">How last week went</h2>

            <div class="grid grid-cols-2 gap-3">
                <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-100 min-w-0">
                    <div class="h-9 w-9 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                        <x-heroicon-o-shopping-bag class="w-4 h-4" />
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Spent</span>
                        <span class="block text-sm font-black text-slate-900 tabular-nums truncate">{{ $peso($s['amountSpent']) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-100 min-w-0">
                    <div class="h-9 w-9 rounded-xl bg-[rgba(var(--brand-rgb),0.1)] text-[var(--brand)] flex items-center justify-center shrink-0">
                        <x-heroicon-o-save-as class="w-4 h-4" />
                    </div>
                    <div class="min-w-0">
                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Moved to savings</span>
                        <span class="block text-sm font-black text-slate-900 tabular-nums truncate">{{ $peso($s['amountSaved']) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3 p-4 rounded-2xl bg-gradient-to-r from-emerald-50 to-white border border-emerald-100">
                <div class="h-10 w-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                    <x-heroicon-o-trending-up class="w-5 h-5" />
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-extrabold text-emerald-700 tabular-nums">{{ $peso($s['unspent']) }} carried over</p>
                    <p class="text-[11px] font-medium text-emerald-600/80">Your unspent money is added to this week automatically.</p>
                </div>
            </div>
        </div>

        {{-- Allowance form --}}
        <form wire:submit.prevent="confirm" class="relative overflow-hidden {{ $card }} p-5 sm:p-8 space-y-6">
            <div class="pointer-events-none absolute -top-24 -right-24 h-56 w-56 rounded-full blur-3xl bg-[rgba(var(--brand-rgb),0.12)]"></div>

            <div class="relative space-y-6">
                <div class="space-y-1">
                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">What's your allowance this week?</h2>
                    <p class="text-sm text-slate-500">Just the new money you're getting. We'll add the carry-over for you.</p>
                </div>

                {{-- Amount --}}
                <div class="space-y-3">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400 font-black text-2xl sm:text-3xl">₱</span>

                        {{-- Hidden input that syncs the raw value with wire:model.lazy --}}
                        <input type="hidden" id="total_allowance_raw" wire:model.lazy="total_allowance" value="{{ $total_allowance }}">

                        {{-- Visible input with comma formatting --}}
                        <input id="total_allowance" type="text" inputmode="decimal" placeholder="0.00" autofocus
                            value="{{ $total_allowance ? number_format($total_allowance, 2, '.', ',') : '' }}"
                            onblur="formatMoney(this, 'total_allowance')"
                            class="w-full pl-12 sm:pl-14 pr-4 py-4 sm:py-5 bg-slate-50 border rounded-2xl text-3xl sm:text-4xl font-black tabular-nums text-slate-900 placeholder-slate-300 focus:bg-white focus:outline-none focus:ring-4 transition-all
                            @error('total_allowance') border-rose-300 focus:border-rose-500 focus:ring-rose-500/15 @else border-slate-200 focus:border-[var(--brand)] focus:ring-[rgba(var(--brand-rgb),0.12)] @enderror">
                    </div>
                    @error('total_allowance') <span class="text-xs text-rose-600 font-semibold block">{{ $message }}</span> @enderror

                    {{-- Quick picks --}}
                    <div class="flex flex-wrap gap-2">
                        <button type="button"
                            wire:click="$set('total_allowance', '{{ number_format($s['lastBase'], 2, '.', '') }}')"
                            class="px-3.5 py-2 text-xs font-bold rounded-full border border-[rgba(var(--brand-rgb),0.3)] bg-[rgba(var(--brand-rgb),0.07)] text-[var(--brand)] hover:bg-[rgba(var(--brand-rgb),0.14)] transition-colors">
                            Same as last week · ₱{{ number_format($s['lastBase']) }}
                        </button>
                        @foreach([500, 1000, 1500, 2000] as $preset)
                            <button type="button"
                                wire:click="$set('total_allowance', '{{ number_format($preset, 2, '.', '') }}')"
                                class="px-3.5 py-2 text-xs font-bold rounded-full bg-white border border-slate-200 text-slate-600 hover:border-[var(--brand)] hover:text-[var(--brand)] transition-all">
                                ₱{{ number_format($preset) }}
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Live result --}}
                <div class="rounded-3xl bg-gradient-to-br from-[var(--brand)] to-[var(--brand-dark)] text-white p-5 sm:p-6 space-y-4 shadow-[0_20px_40px_-16px_rgba(var(--brand-rgb),0.55)]">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-[0.15em] text-white/70 block">You'll have this week</span>
                        <div class="mt-1 flex items-start font-black tabular-nums tracking-tighter leading-none">
                            <span class="text-xl sm:text-2xl mt-1 mr-1 opacity-70">₱</span>
                            <span class="text-5xl sm:text-6xl">{{ $totalWhole }}</span>
                            <span class="text-lg sm:text-xl mt-1 opacity-70">.{{ $totalCents }}</span>
                        </div>
                        @if($perDay > 0)
                            <p class="mt-2 text-sm font-semibold text-white/85">
                                About {{ $peso($perDay) }} a day for {{ $s['daysLeft'] }} {{ Str::plural('day', $s['daysLeft']) }}
                            </p>
                        @else
                            <p class="mt-2 text-sm font-medium text-white/70">Enter your allowance to see your daily amount.</p>
                        @endif
                    </div>

                    <div class="space-y-2 pt-4 border-t border-white/20 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-white/80">New allowance</span>
                            <span class="font-bold tabular-nums">{{ $peso($allowance) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-white/80">Carried over from last week</span>
                            <span class="font-bold tabular-nums">+ {{ $peso($s['unspent']) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Week dates --}}
                <div class="flex items-center gap-3 text-xs font-medium text-slate-500">
                    <div class="h-9 w-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0">
                        <x-heroicon-o-calendar class="w-4 h-4" />
                    </div>
                    <p class="leading-snug">
                        {{ $s['newCycleStart']->format('l, M j') }} to {{ $s['newNextReset']->copy()->subDay()->format('l, M j') }}.
                        Resets every {{ $s['newResetDay'] }}. You can change this in Settings.
                    </p>
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="confirm"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-4 rounded-2xl bg-gradient-to-r from-[var(--brand)] to-[var(--brand-dark)] text-white text-sm font-extrabold shadow-lg shadow-[0_10px_20px_-6px_rgba(var(--brand-rgb),0.45)] hover:brightness-110 active:scale-[0.99] transition-all disabled:opacity-60">
                    <span wire:loading.remove wire:target="confirm" class="inline-flex items-center gap-2">
                        Start this week
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </span>
                    <span wire:loading wire:target="confirm">Starting...</span>
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">Log out</button>
        </form>
    </div>
</div>

<script>
    function formatMoney(input, propertyName = 'total_allowance') {
        const cleaned = input.value.replace(/,/g, '').trim();
        const rawInputId = propertyName + '_raw';
        const rawInput = document.getElementById(rawInputId);

        if (cleaned === '') {
            input.value = '';
            if (rawInput) {
                rawInput.value = '';
                rawInput.dispatchEvent(new Event('input', { bubbles: true }));
                rawInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (typeof @this !== 'undefined') {
                @this.set(propertyName, '');
            }
            return;
        }

        const value = parseFloat(cleaned);
        if (isNaN(value) || value < 0) {
            input.value = '';
            if (rawInput) {
                rawInput.value = '';
                rawInput.dispatchEvent(new Event('input', { bubbles: true }));
                rawInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
            if (typeof @this !== 'undefined') {
                @this.set(propertyName, '');
            }
            return;
        }

        // Format display value with commas and 2 decimal places (e.g., 10,000.00)
        input.value = value.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });

        // Update hidden raw input and trigger lazy updates/events
        const rawVal = value.toFixed(2);
        if (rawInput) {
            rawInput.value = rawVal;
            rawInput.dispatchEvent(new Event('input', { bubbles: true }));
            rawInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (typeof @this !== 'undefined') {
            @this.set(propertyName, rawVal);
        }
    }
</script>