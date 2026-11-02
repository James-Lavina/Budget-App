@php
    $s         = $this->summary;
    $allowance = is_numeric($total_allowance) ? (float) $total_allowance : 0;
    $newTotal  = $allowance + $s['unspent'];
    $perDay    = $allowance > 0 ? $newTotal / $s['daysLeft'] : 0;
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                @endif
            </div>
            <div class="space-y-1">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">A New Week Begins</h1>
                <p class="text-sm text-slate-500 font-medium">
                    Your last budget week ({{ $s['startDate']->format('M j') }} – {{ $s['endDate']->format('M j') }}) has ended.
                    Set your allowance for this week to continue.
                </p>
            </div>
        </div>

        {{-- Last week recap --}}
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">Last week recap</h2>
            <div class="grid grid-cols-3 gap-3">
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Spent</span>
                    <span class="block text-sm sm:text-base font-black text-slate-900 font-mono mt-1">₱{{ number_format($s['amountSpent'], 2) }}</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Saved</span>
                    <span class="block text-sm sm:text-base font-black text-slate-900 font-mono mt-1">₱{{ number_format($s['amountSaved'], 2) }}</span>
                </div>
                <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-100">
                    <span class="block text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Unspent</span>
                    <span class="block text-sm sm:text-base font-black text-emerald-700 font-mono mt-1">₱{{ number_format($s['unspent'], 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Allowance form --}}
        <form wire:submit.prevent="confirm" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 sm:p-10 space-y-6">
            <div class="text-center space-y-1">
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">What's your allowance this week?</h2>
                <p class="text-sm text-slate-500">Your unspent ₱{{ number_format($s['unspent'], 2) }} will be added on top automatically.</p>
            </div>

            <div class="max-w-sm mx-auto space-y-4">
                <div class="space-y-1.5">
                    <label for="total_allowance" class="block text-xs font-bold text-slate-700">This week's allowance</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">₱</span>
                        <input id="total_allowance" type="number" step="0.01" min="0" placeholder="0.00" autofocus
                            wire:model.lazy="total_allowance"
                            onblur="formatMoney(this)"
                            class="w-full pl-9 pr-4 py-3.5 bg-slate-50 border rounded-2xl text-2xl font-black text-slate-900 placeholder-slate-300 focus:bg-white focus:outline-none focus:ring-2 transition-all
                            @error('total_allowance') border-rose-300 focus:border-rose-500 focus:ring-rose-500/20 @else border-slate-200 focus:border-[var(--brand)] focus:ring-[rgba(var(--brand-rgb),0.2)] @enderror">
                    </div>
                    @error('total_allowance') <span class="text-xs text-rose-600 font-medium">{{ $message }}</span> @enderror
                </div>

                {{-- Quick select --}}
                <div class="flex flex-wrap justify-center gap-2">
                    <button type="button"
                        wire:click="$set('total_allowance', '{{ number_format($s['lastBase'], 2, '.', '') }}')"
                        class="px-3 py-1.5 text-xs font-bold font-mono rounded-xl border border-[rgba(var(--brand-rgb),0.3)] bg-[rgba(var(--brand-rgb),0.06)] text-[var(--brand)] hover:bg-[rgba(var(--brand-rgb),0.12)] transition-colors">
                        Same as last week · ₱{{ number_format($s['lastBase']) }}
                    </button>
                    @foreach([500, 1000, 1500, 2000] as $preset)
                        <button type="button"
                            wire:click="$set('total_allowance', '{{ number_format($preset, 2, '.', '') }}')"
                            class="px-3 py-1.5 text-xs font-bold font-mono rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 transition-colors">
                            ₱{{ number_format($preset) }}
                        </button>
                    @endforeach
                </div>

                {{-- Live breakdown --}}
                <div class="rounded-2xl bg-slate-50 border border-slate-100 p-4 space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-500">This week's allowance</span>
                        <span class="font-bold text-slate-800 font-mono">₱{{ number_format($allowance, 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-emerald-600">+ Rolled over from last week</span>
                        <span class="font-bold text-emerald-600 font-mono">₱{{ number_format($s['unspent'], 2) }}</span>
                    </div>
                    <div class="flex items-center justify-between pt-2.5 border-t border-slate-200">
                        <span class="text-xs font-extrabold text-slate-900">Total available this week</span>
                        <span class="text-base font-black text-[var(--brand)] font-mono">₱{{ number_format($newTotal, 2) }}</span>
                    </div>
                </div>

                @if($perDay > 0)
                    <div class="text-center">
                        <span class="inline-block px-4 py-1.5 rounded-full bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] text-xs font-bold">
                            That's about ₱{{ number_format($perDay, 2) }} per day
                        </span>
                    </div>
                @endif

                <div class="flex items-start gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                    <div class="h-8 w-8 rounded-lg bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium leading-snug">
                        This week runs {{ $s['newCycleStart']->format('l, M j') }} to {{ $s['newNextReset']->copy()->subDay()->format('l, M j') }}.
                        It resets every {{ $s['newResetDay'] }}. Change the reset day anytime in Settings.
                    </p>
                </div>
            </div>

            <div class="flex justify-center pt-2">
                <button type="submit" wire:loading.attr="disabled" wire:target="confirm"
                    class="inline-flex items-center gap-1.5 px-8 py-3 bg-[var(--brand)] hover:bg-[var(--brand-dark)] text-white rounded-full font-bold text-sm shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.35)] transition-all active:scale-95 disabled:opacity-50">
                    <span wire:loading.remove wire:target="confirm">Start This Week</span>
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