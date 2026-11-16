@php
    $ok = ($forecastResult['status'] ?? '') === 'success';

    if ($ok) {
        $m          = $forecastResult['metrics'];
        $t          = $forecastResult['text'];
        $days       = $forecastResult['days'];
        $scale      = max(1, (float) $forecastResult['day_scale']);
        $categories = $forecastResult['categories'];
        $advice     = $forecastResult['advice'];
        $state      = $m['state'];

        $theme = [
            'on_track'    => ['label' => 'On track',         'pill' => 'bg-emerald-100 text-emerald-700', 'bg' => 'from-emerald-50', 'glow' => 'bg-emerald-300/40', 'num' => 'text-emerald-600', 'ring' => 'text-emerald-500', 'icon' => 'check-circle'],
            'tight'       => ['label' => 'Tight week',       'pill' => 'bg-amber-100 text-amber-700',     'bg' => 'from-amber-50',   'glow' => 'bg-amber-300/40',   'num' => 'text-amber-600',   'ring' => 'text-amber-500',   'icon' => 'clock'],
            'runs_out'    => ['label' => 'Spending too fast','pill' => 'bg-rose-100 text-rose-700',       'bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'num' => 'text-rose-600',    'ring' => 'text-rose-500',    'icon' => 'trending-up'],
            'depleted'    => ['label' => 'Budget used up',   'pill' => 'bg-rose-100 text-rose-700',       'bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'num' => 'text-rose-600',    'ring' => 'text-rose-500',    'icon' => 'exclamation-circle'],
            'final_day'   => ['label' => 'Last day',         'pill' => 'bg-slate-100 text-slate-600',     'bg' => 'from-slate-100',  'glow' => 'bg-slate-300/40',   'num' => 'text-slate-900',   'ring' => 'text-slate-500',   'icon' => 'clock'],
            'fresh_start' => ['label' => 'Fresh start',      'pill' => 'bg-slate-100 text-slate-600',     'bg' => 'from-slate-100',  'glow' => 'bg-slate-300/40',   'num' => 'text-slate-900',   'ring' => 'text-slate-500',   'icon' => 'sparkles'],
        ];
        $th = $theme[$state] ?? $theme['on_track'];

        $toneMap = [
            'rose'    => 'bg-rose-100 text-rose-600',
            'amber'   => 'bg-amber-100 text-amber-600',
            'emerald' => 'bg-emerald-100 text-emerald-600',
            'slate'   => 'bg-slate-100 text-slate-500',
        ];

        $card = 'rounded-[2rem] border border-slate-100 bg-white shadow-[0_20px_50px_-24px_rgba(15,23,42,0.15)]';

        $peso      = fn ($n) => '₱' . number_format($n, 2);
        $pesoFloor = fn ($n) => '₱' . number_format(floor(round($n * 100, 4)) / 100, 2);
        $pesoShort = fn ($n) => '₱' . number_format($n, 0);

        [$canWhole, $canCents] = explode('.', number_format(floor(round($m['safe_today'] * 100, 4)) / 100, 2));

        $limit    = (float) $m['safe_per_day'];
        $todayPct = $limit > 0
            ? min(100, (int) round(($m['spent_today'] / $limit) * 100))
            : ($m['spent_today'] > 0 ? 100 : 0);

        $ringC      = 2 * M_PI * 52;
        $ringOffset = $ringC * (1 - $todayPct / 100);

        $limitPct  = min(100, (int) round(($limit / $scale) * 100));
        $daysLabel = $m['is_final_day'] ? 'Last day' : $m['days_left'] . ' ' . Str::plural('day', $m['days_left']) . ' to go';
    }
@endphp

<div class="min-h-screen py-6 sm:py-8 px-3.5 sm:px-6 lg:px-8 font-sans" wire:init="loadAiInsight">
    <div class="max-w-2xl mx-auto space-y-5 sm:space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Spending Forecast</h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">A quick check on how your week is going.</p>
        </div>

        @if(!$ok)
            <div class="{{ $card }} p-8 text-center space-y-3">
                <p class="text-sm font-bold text-slate-800">{{ $forecastResult['message'] ?? 'Something went wrong loading your forecast.' }}</p>
                <a href="{{ route('student.budget-setup') }}" class="inline-flex px-5 py-2.5 rounded-2xl bg-gradient-to-r from-[var(--brand)] to-[var(--brand-dark)] text-white text-xs font-bold hover:brightness-110 transition">
                    Set up budget
                </a>
            </div>
        @else

            {{-- 1. HERO --}}
            <div class="relative overflow-hidden rounded-[2rem] border border-slate-100 bg-gradient-to-br {{ $th['bg'] }} via-white to-white shadow-[0_20px_50px_-20px_rgba(15,23,42,0.18)] p-5 sm:p-8">
                <div class="pointer-events-none absolute -top-24 -right-24 h-64 w-64 rounded-full blur-3xl {{ $th['glow'] }}"></div>
                <div class="pointer-events-none absolute -bottom-32 -left-20 h-56 w-56 rounded-full blur-3xl bg-[rgba(var(--brand-rgb),0.08)]"></div>

                <div class="relative space-y-6">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold {{ $th['pill'] }}">
                            <x-dynamic-component :component="'heroicon-o-' . $th['icon']" class="w-3.5 h-3.5" />
                            {{ $th['label'] }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-white/80 backdrop-blur border border-slate-200 text-[11px] font-bold text-slate-500">
                            <x-heroicon-o-calendar class="w-3.5 h-3.5" />
                            {{ $daysLabel }} · resets {{ $m['reset_day'] }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4 sm:gap-8">
                        <div class="min-w-0">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-[0.15em] block">You can spend today</span>

                            <div class="mt-2 flex items-start font-black tabular-nums tracking-tighter leading-none {{ $th['num'] }}">
                                <span class="text-2xl sm:text-4xl mt-1.5 sm:mt-2.5 mr-1 opacity-60">₱</span>
                                <span class="text-6xl sm:text-8xl">{{ $canWhole }}</span>
                                <span class="text-xl sm:text-3xl mt-1.5 sm:mt-2.5 opacity-60">.{{ $canCents }}</span>
                            </div>

                            <p class="mt-3 text-xs sm:text-sm font-medium text-slate-500">
                                {{ $peso($m['spent_today']) }} spent of your {{ $pesoFloor($limit) }} limit
                            </p>
                        </div>

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

                    {{-- Verdict --}}
                    <div class="rounded-2xl bg-white/70 backdrop-blur border border-white p-4 sm:p-5 shadow-sm space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="h-9 w-9 rounded-xl bg-white shadow-sm flex items-center justify-center shrink-0 {{ $th['ring'] }}">
                                <x-dynamic-component :component="'heroicon-o-' . $th['icon']" class="w-4 h-4" />
                            </div>
                            <div class="min-w-0 space-y-0.5">
                                <h2 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug">{{ $t['headline'] }}</h2>
                                <p class="text-xs sm:text-sm font-medium text-slate-500 leading-relaxed">{{ $t['sub'] }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-3 border-t border-slate-200/70">
                            <p class="text-xs sm:text-sm font-bold text-slate-700 leading-snug">{{ $t['action'] }}</p>
                            <a href="{{ route('student.simulation') }}" class="text-xs font-bold whitespace-nowrap text-[var(--brand)] hover:opacity-75 transition-opacity self-end sm:self-auto">
                                Plan a purchase →
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. WEEK --}}
            <div class="{{ $card }} p-5 sm:p-7 space-y-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Your week so far</h3>
                        <p class="text-[11px] sm:text-xs text-slate-400 font-medium mt-0.5">Bars show what you spent each day.</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-[11px] font-bold text-slate-500 shrink-0">
                        <span class="w-3 inline-block border-t-2 border-dashed border-slate-400"></span>
                        Limit {{ $pesoFloor($limit) }}
                    </span>
                </div>

                <div class="flex items-end gap-1.5 sm:gap-2.5">
                    @foreach($days as $d)
                        @php
                            $isFuture = $d['status'] === 'future';
                            $isToday  = $d['status'] === 'today';
                            $barH     = $d['spent'] > 0 ? max(6, (int) round(($d['spent'] / $scale) * 100)) : 0;
                            $wentOver = !$isFuture && $limit > 0 && $d['spent'] > $limit;
                            $barColor = $wentOver
                                ? 'bg-gradient-to-t from-rose-500 to-rose-300'
                                : 'bg-gradient-to-t from-[var(--brand)] to-[rgba(var(--brand-rgb),0.55)]';
                        @endphp
                        <div class="flex-1 min-w-0 flex flex-col items-center gap-2 rounded-2xl py-2 px-0.5 {{ $isToday ? 'bg-[rgba(var(--brand-rgb),0.06)]' : '' }}">
                            <div class="relative w-full h-28 flex items-end justify-center">
                                <div class="relative w-full max-w-[26px] h-full rounded-full bg-slate-100/80 overflow-hidden flex items-end">
                                    @if(!$isFuture && $barH > 0)
                                        <div class="w-full rounded-full {{ $barColor }} {{ $isToday ? '' : 'opacity-80' }}" style="height: {{ $barH }}%"></div>
                                    @endif
                                </div>
                                <div class="absolute inset-x-0 border-t border-dashed border-slate-300/90" style="bottom: {{ $limitPct }}%"></div>
                            </div>
                            <span class="text-[11px] font-bold {{ $isToday ? 'text-[var(--brand)]' : 'text-slate-500' }}">{{ $isToday ? 'Today' : $d['label'] }}</span>
                            <span class="text-[10px] font-semibold tabular-nums {{ $isFuture ? 'text-slate-300' : ($wentOver ? 'text-rose-500' : 'text-slate-500') }}">
                                {{ $isFuture ? '—' : $pesoShort($d['spent']) }}
                            </span>
                        </div>
                    @endforeach
                </div>

                @if($m['saved'] > 0)
                    <p class="text-[11px] font-medium text-slate-400">
                        {{ $peso($m['saved']) }} moved to savings isn't counted as spending here.
                    </p>
                @endif
            </div>

            {{-- 3. CATEGORIES --}}
            @if(!empty($categories))
                <div class="{{ $card }} p-5 sm:p-7 space-y-5">
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Where your money is going</h3>
                    <div class="space-y-4">
                        @foreach($categories as $cat)
                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="h-8 w-8 rounded-xl shrink-0 flex items-center justify-center" style="background-color: {{ $cat['color'] }}1f;">
                                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $cat['color'] }};"></span>
                                        </span>
                                        <span class="text-sm font-bold text-slate-800 truncate">{{ $cat['name'] }}</span>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <span class="text-sm font-extrabold text-slate-900 tabular-nums block leading-tight">{{ $peso($cat['total']) }}</span>
                                        <span class="text-[11px] font-semibold text-slate-400">{{ $cat['percent'] }}%</span>
                                    </div>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ $cat['percent'] }}%; background-color: {{ $cat['color'] }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 4. ADVICE --}}
            <div class="{{ $card }} p-5 sm:p-7 space-y-3">
                <h3 class="text-sm sm:text-base font-extrabold text-slate-900 mb-1">What you can do</h3>

                @foreach($advice as $item)
                    <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-slate-50/80 border border-slate-100">
                        <div class="h-10 w-10 rounded-2xl {{ $toneMap[$item['tone']] ?? $toneMap['slate'] }} flex items-center justify-center shrink-0">
                            <x-dynamic-component :component="'heroicon-o-' . $item['icon']" class="w-5 h-5" />
                        </div>
                        <div class="min-w-0 space-y-0.5 pt-0.5">
                            <p class="text-sm font-extrabold text-slate-800 leading-snug">{{ $item['title'] }}</p>
                            <p class="text-xs sm:text-[13px] font-medium text-slate-500 leading-relaxed">{{ $item['body'] }}</p>
                        </div>
                    </div>
                @endforeach

                @if($aiLoaded && !empty($aiInsight['tips']))
                    @foreach($aiInsight['tips'] as $tip)
                        <div class="flex items-start gap-3.5 p-4 rounded-2xl bg-gradient-to-br from-[rgba(var(--brand-rgb),0.08)] to-[rgba(var(--brand-rgb),0.02)] border border-[rgba(var(--brand-rgb),0.15)]">
                            <div class="h-10 w-10 rounded-2xl bg-white shadow-sm text-[var(--brand)] flex items-center justify-center shrink-0">
                                <x-heroicon-o-sparkles class="w-5 h-5" />
                            </div>
                            <div class="min-w-0 space-y-0.5 pt-0.5">
                                <p class="text-[10px] font-extrabold uppercase tracking-[0.15em] text-[var(--brand)]">Extra tip</p>
                                <p class="text-xs sm:text-[13px] font-semibold text-slate-600 leading-relaxed">{{ $tip }}</p>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        @endif
    </div>
</div>