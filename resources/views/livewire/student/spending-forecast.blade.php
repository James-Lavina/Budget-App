@php
    $ok = ($forecastResult['status'] ?? '') === 'success';

    if ($ok) {
        $m          = $forecastResult['metrics'];
        $t          = $forecastResult['text'];
        $categories = $forecastResult['categories'];
        $advice     = $forecastResult['advice'];
        $chart      = $forecastResult['chart'];
        $state      = $m['state'];

        $theme = [
            'on_track'    => ['label' => 'On track',              'pill' => 'bg-emerald-100 text-emerald-700', 'bg' => 'from-emerald-50', 'glow' => 'bg-emerald-300/40', 'num' => 'text-emerald-600', 'ring' => 'text-emerald-500', 'icon' => 'check-circle'],
            'tight'       => ['label' => 'Tight week',            'pill' => 'bg-amber-100 text-amber-700',     'bg' => 'from-amber-50',   'glow' => 'bg-amber-300/40',   'num' => 'text-amber-600',   'ring' => 'text-amber-500',   'icon' => 'clock'],
            'runs_out'    => ['label' => 'Spending too fast',     'pill' => 'bg-rose-100 text-rose-700',       'bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'num' => 'text-rose-600',    'ring' => 'text-rose-500',    'icon' => 'trending-up'],
            'depleted'    => ['label' => 'Budget used up',        'pill' => 'bg-rose-100 text-rose-700',       'bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'num' => 'text-rose-600',    'ring' => 'text-rose-500',    'icon' => 'exclamation-circle'],
            'over_quota'  => ['label' => "Over today's limit",    'pill' => 'bg-rose-100 text-rose-700',       'bg' => 'from-rose-50',    'glow' => 'bg-rose-300/40',    'num' => 'text-rose-600',    'ring' => 'text-rose-500',    'icon' => 'exclamation'],
            'quota_hit'   => ['label' => "Today's limit reached", 'pill' => 'bg-amber-100 text-amber-700',     'bg' => 'from-amber-50',   'glow' => 'bg-amber-300/40',   'num' => 'text-amber-600',   'ring' => 'text-amber-500',   'icon' => 'clock'],
            'final_day'   => ['label' => 'Last day',              'pill' => 'bg-slate-100 text-slate-600',     'bg' => 'from-slate-100',  'glow' => 'bg-slate-300/40',   'num' => 'text-slate-900',   'ring' => 'text-slate-500',   'icon' => 'clock'],
            'fresh_start' => ['label' => 'Fresh start',           'pill' => 'bg-slate-100 text-slate-600',     'bg' => 'from-slate-100',  'glow' => 'bg-slate-300/40',   'num' => 'text-slate-900',   'ring' => 'text-slate-500',   'icon' => 'sparkles'],
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

        [$canWhole, $canCents] = explode('.', number_format(floor(round($m['safe_today'] * 100, 4)) / 100, 2));

        $limit    = (float) $m['safe_per_day'];
        $todayPct = $limit > 0
            ? min(100, (int) round(($m['spent_today'] / $limit) * 100))
            : ($m['spent_today'] > 0 ? 100 : 0);

        $ringC      = 2 * M_PI * 52;
        $ringOffset = $ringC * (1 - $todayPct / 100);

        $daysLabel = $m['is_final_day']
            ? 'Last day'
            : $m['days_left'] . ' ' . Str::plural('day', $m['days_left']) . ' to go';

        // ---- Balance projection chart ------------------------------------------------
        $hasProjection = !$m['is_final_day']
            && collect($chart['projected'])->filter(fn ($v) => $v !== null)->count() > 1;

        $paceColor = '#059669';

        if (in_array($state, ['runs_out', 'depleted', 'over_quota'], true)) {
            $paceColor = '#e11d48';
        } elseif (in_array($state, ['tight', 'quota_hit'], true)) {
            $paceColor = '#d97706';
        }

        $emptySeries = array_fill(0, count($chart['labels']), null);

        $balanceChartPayload = [
            'labels'       => $chart['labels'],
            'actual'       => $chart['actual'],
            'projected'    => $hasProjection ? $chart['projected'] : $emptySeries,
            'safePath'     => $hasProjection ? $chart['safe_path'] : $emptySeries,
            'pool'         => $chart['pool'],
            'todayIndex'   => $chart['today_index'],
            'runsOutIndex' => $chart['runs_out_index'],
            'paceColor'    => $paceColor,
        ];

        if ($m['runs_out_label']) {
            $chartHeadline = "Money runs out {$m['runs_out_label']}";
        } elseif ($m['is_final_day']) {
            $chartHeadline = 'Final balance this week';
        } else {
            $chartHeadline = 'You make it to ' . $m['reset_day'];
        }
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

            {{-- 2. BALANCE PROJECTION --}}
            <div class="{{ $card }} p-5 sm:p-7 space-y-5">
                <div class="space-y-1">
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Where your balance is heading</h3>
                    <p class="text-[11px] sm:text-xs text-slate-400 font-medium">
                        Solid line is what you have left. Dashed lines show where it goes from here.
                    </p>
                </div>

                {{-- Legend --}}
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] font-bold text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="inline-block w-5 border-t-[3px] border-[var(--brand)] rounded"></span>
                        Your balance
                    </span>
                    @if($hasProjection)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block w-5 border-t-[3px] border-dashed rounded" style="border-color: {{ $paceColor }};"></span>
                            At your pace
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <span class="inline-block w-5 border-t-2 border-dashed border-slate-400 rounded"></span>
                            Safe path (₱0 on reset day)
                        </span>
                    @endif
                </div>

                <div wire:ignore>
                    <div class="relative h-56 sm:h-72 w-full">
                        <canvas id="balanceForecastChart"></canvas>
                    </div>
                </div>

                <script type="application/json" id="balanceForecastData">{!! json_encode($balanceChartPayload) !!}</script>

                {{-- Prediction summary --}}
                <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 sm:p-4 min-w-0">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Left now</span>
                        <span class="text-sm sm:text-lg font-black text-slate-900 tabular-nums block mt-1 truncate">{{ $peso($m['remaining']) }}</span>
                    </div>

                    <div class="rounded-2xl border p-3 sm:p-4 min-w-0 {{ $m['runs_out_label'] ? 'bg-rose-50 border-rose-100' : 'bg-slate-50 border-slate-100' }}">
                        <span class="text-[10px] font-bold uppercase tracking-wider block {{ $m['runs_out_label'] ? 'text-rose-400' : 'text-slate-400' }}">
                            {{ $m['is_final_day'] ? 'Rolls over' : 'At reset' }}
                        </span>
                        <span class="text-sm sm:text-lg font-black tabular-nums block mt-1 truncate {{ $m['runs_out_label'] ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ $m['runs_out_label'] ? $peso(0) : $peso($m['is_final_day'] ? $m['remaining'] : $m['projected_remaining']) }}
                        </span>
                    </div>

                    <div class="rounded-2xl border p-3 sm:p-4 min-w-0 {{ $m['runs_out_label'] ? 'bg-rose-50 border-rose-100' : 'bg-emerald-50 border-emerald-100' }}">
                        <span class="text-[10px] font-bold uppercase tracking-wider block {{ $m['runs_out_label'] ? 'text-rose-400' : 'text-emerald-500' }}">
                            Verdict
                        </span>
                        <span class="text-xs sm:text-sm font-extrabold leading-snug block mt-1 {{ $m['runs_out_label'] ? 'text-rose-600' : 'text-emerald-700' }}">
                            {{ $chartHeadline }}
                        </span>
                    </div>
                </div>

                @if($m['saved'] > 0)
                    <p class="flex items-center gap-2 text-[11px] font-medium text-slate-400">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-400 shrink-0"></span>
                        {{ $peso($m['saved']) }} moved to savings lowers your balance but not your spending pace.
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

            {{-- Balance chart script --}}
            <script>
                (function () {
                    let balanceChart = null;

                    function peso(n) {
                        return '₱' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    }

                    function initBalanceChart() {
                        const canvas = document.getElementById('balanceForecastChart');
                        const dataEl = document.getElementById('balanceForecastData');
                        if (!canvas || !dataEl || typeof Chart === 'undefined') return;

                        if (balanceChart) { balanceChart.destroy(); balanceChart = null; }

                        const p        = JSON.parse(dataEl.textContent);
                        const styles   = getComputedStyle(document.documentElement);
                        const brand    = styles.getPropertyValue('--brand').trim() || '#4f39fa';
                        const brandRgb = styles.getPropertyValue('--brand-rgb').trim() || '79, 57, 250';

                        // Draws the "today" divider and the run-out marker. No annotation plugin needed.
                        const markers = {
                            id: 'forecastMarkers',
                            afterDatasetsDraw(chart) {
                                const { ctx, chartArea, scales } = chart;
                                const x = scales.x, y = scales.y;
                                ctx.save();

                                if (p.todayIndex !== null) {
                                    const tx = x.getPixelForValue(p.todayIndex);
                                    ctx.setLineDash([4, 4]);
                                    ctx.lineWidth = 1;
                                    ctx.strokeStyle = 'rgba(100, 116, 139, 0.45)';
                                    ctx.beginPath();
                                    ctx.moveTo(tx, chartArea.top);
                                    ctx.lineTo(tx, chartArea.bottom);
                                    ctx.stroke();
                                    ctx.setLineDash([]);
                                    ctx.fillStyle = '#64748b';
                                    ctx.font = '700 10px sans-serif';
                                    ctx.textAlign = 'center';
                                    ctx.fillText('TODAY', tx, chartArea.top - 6);
                                }

                                if (p.runsOutIndex !== null) {
                                    const rx = x.getPixelForValue(p.runsOutIndex);
                                    const ry = y.getPixelForValue(0);

                                    // Shade the days with no money left.
                                    ctx.fillStyle = 'rgba(225, 29, 72, 0.07)';
                                    ctx.fillRect(rx, chartArea.top, chartArea.right - rx, chartArea.bottom - chartArea.top);

                                    // Marker dot.
                                    ctx.beginPath();
                                    ctx.arc(rx, ry, 6, 0, Math.PI * 2);
                                    ctx.fillStyle = '#e11d48';
                                    ctx.fill();
                                    ctx.lineWidth = 2;
                                    ctx.strokeStyle = '#ffffff';
                                    ctx.stroke();

                                    // Label (kept inside the chart on the right edge).
                                    const nearRight = rx > chartArea.right - 60;
                                    ctx.fillStyle = '#e11d48';
                                    ctx.font = '800 11px sans-serif';
                                    ctx.textAlign = nearRight ? 'right' : 'left';
                                    ctx.fillText('Money runs out', nearRight ? rx - 10 : rx + 10, ry - 12);
                                }

                                ctx.restore();
                            },
                        };

                        balanceChart = new Chart(canvas.getContext('2d'), {
                            type: 'line',
                            data: {
                                labels: p.labels,
                                datasets: [
                                    {
                                        label: 'Your balance',
                                        data: p.actual,
                                        borderColor: brand,
                                        backgroundColor: 'rgba(' + brandRgb + ', 0.10)',
                                        fill: 'origin',
                                        borderWidth: 3,
                                        tension: 0.25,
                                        pointRadius: 3,
                                        pointBackgroundColor: brand,
                                        spanGaps: false,
                                        order: 1,
                                    },
                                    {
                                        label: 'At your pace',
                                        data: p.projected,
                                        borderColor: p.paceColor,
                                        borderWidth: 3,
                                        borderDash: [8, 6],
                                        tension: 0,
                                        pointRadius: 0,
                                        pointHoverRadius: 4,
                                        spanGaps: false,
                                        fill: false,
                                        order: 0,
                                    },
                                    {
                                        label: 'Safe path',
                                        data: p.safePath,
                                        borderColor: '#94a3b8',
                                        borderWidth: 2,
                                        borderDash: [3, 5],
                                        tension: 0,
                                        pointRadius: 0,
                                        pointHoverRadius: 3,
                                        spanGaps: false,
                                        fill: false,
                                        order: 2,
                                    },
                                ],
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                layout: { padding: { top: 18, right: 8 } },
                                interaction: { mode: 'index', intersect: false },
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        padding: 10,
                                        bodyFont: { size: 11, weight: 'bold' },
                                        filter: (item) => item.raw !== null && item.raw !== undefined,
                                        callbacks: {
                                            label: (ctx) => ' ' + ctx.dataset.label + ': ' + peso(ctx.raw),
                                        },
                                    },
                                },
                                scales: {
                                    x: {
                                        grid: { display: false },
                                        ticks: { font: { size: 10, weight: '600' }, color: '#64748b' },
                                    },
                                    y: {
                                        min: 0,
                                        suggestedMax: p.pool,
                                        grid: { color: '#eef1f5' },
                                        ticks: {
                                            font: { size: 9 },
                                            color: '#64748b',
                                            maxTicksLimit: 5,
                                            callback: (v) => '₱' + Number(v).toLocaleString('en-US'),
                                        },
                                    },
                                },
                            },
                            plugins: [markers],
                        });
                    }

                    document.addEventListener('livewire:load', initBalanceChart);
                })();
            </script>
        @endif
    </div>
</div>