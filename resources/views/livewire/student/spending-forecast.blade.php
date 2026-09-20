@php
    $ok = ($forecastResult['status'] ?? '') === 'success';

    if ($ok) {
        $m     = $forecastResult['metrics'];
        $chart = $forecastResult['chart'];
        $t     = $forecastResult['text'];
        $state = $m['state'];

        $styleMap = [
            'on_track'    => ['border' => 'border-l-emerald-500', 'num' => 'text-emerald-600', 'callout' => 'bg-emerald-50 border-emerald-100 text-emerald-800'],
            'tight'       => ['border' => 'border-l-amber-500',   'num' => 'text-amber-600',   'callout' => 'bg-amber-50 border-amber-100 text-amber-800'],
            'runs_out'    => ['border' => 'border-l-rose-500',    'num' => 'text-rose-600',    'callout' => 'bg-rose-50 border-rose-100 text-rose-800'],
            'depleted'    => ['border' => 'border-l-rose-500',    'num' => 'text-rose-600',    'callout' => 'bg-rose-50 border-rose-100 text-rose-800'],
            'final_day'   => ['border' => 'border-l-slate-400',   'num' => 'text-slate-900',   'callout' => 'bg-slate-50 border-slate-200 text-slate-700'],
            'fresh_start' => ['border' => 'border-l-slate-300',   'num' => 'text-slate-900',   'callout' => 'bg-slate-50 border-slate-200 text-slate-700'],
        ];
        $s = $styleMap[$state] ?? $styleMap['on_track'];

        $peso = function ($n) {
            return '₱' . number_format($n, 2);
        };

        $bigLabel = in_array($state, ['final_day', 'depleted', 'fresh_start'])
            ? 'Money left'
            : 'Estimated money left by ' . $m['end_label'] . ' night';

        $daysLabel = $m['is_final_day'] ? 'Last day' : $m['days_left'] . ' ' . Str::plural('day', $m['days_left']);
        $isRisk    = in_array($state, ['runs_out', 'depleted']);
        $chip      = 'bg-slate-100 text-slate-500';
    }
@endphp

<div class="min-h-screen py-6 sm:py-8 px-3.5 sm:px-6 lg:px-8 font-sans" wire:init="loadAiInsight">
    <div class="max-w-3xl mx-auto space-y-5 sm:space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Spending Forecast</h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">Where your money is heading this week.</p>
        </div>

        @if(!$ok)
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-8 text-center space-y-3">
                <p class="text-sm font-bold text-slate-800">{{ $forecastResult['message'] ?? 'Something went wrong loading your forecast.' }}</p>
                <a href="{{ route('student.budget-setup') }}" class="inline-flex px-5 py-2.5 rounded-2xl bg-[var(--brand)] text-white text-xs font-bold hover:opacity-90 transition">
                    Set up budget
                </a>
            </div>
        @else

            {{-- 1. VERDICT --}}
            <div class="bg-white rounded-3xl border border-slate-100 border-l-4 {{ $s['border'] }} shadow-sm p-5 sm:p-7 space-y-4">
                <div>
                    <span class="text-[11px] sm:text-xs font-bold text-slate-400 uppercase tracking-wider block">{{ $bigLabel }}</span>
                    <div class="text-4xl sm:text-5xl font-black font-mono tracking-tight mt-1 {{ $s['num'] }}">
                        {{ $peso($m['projected_remaining']) }}
                    </div>
                </div>

                <div class="space-y-1">
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug">{{ $t['headline'] }}</h2>
                    <p class="text-xs sm:text-sm font-medium text-slate-500">{{ $t['sub'] }}</p>
                </div>

                <div class="rounded-2xl border p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 {{ $s['callout'] }}">
                    <p class="text-xs sm:text-sm font-bold leading-snug">{{ $t['action'] }}</p>
                    <a href="{{ route('student.simulation') }}" class="text-xs font-bold whitespace-nowrap hover:opacity-75 transition-opacity self-end sm:self-auto">
                        Plan a purchase →
                    </a>
                </div>
            </div>

            {{-- 2. PACE vs SAFE PACE --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Your pace</span>
                        <div class="h-7 w-7 rounded-lg {{ $chip }} flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 font-mono mt-3 whitespace-nowrap">
                        {{ $peso($m['pace']) }}<span class="text-xs text-slate-400 font-semibold">/day</span>
                    </div>
                    <p class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-1.5">Average so far</p>
                </div>

                <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Safe pace</span>
                        <div class="h-7 w-7 rounded-lg {{ $chip }} flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 font-mono mt-3 whitespace-nowrap">
                        {{ $peso($m['safe_per_day']) }}<span class="text-xs text-slate-400 font-semibold">/day</span>
                    </div>
                    <p class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-1.5">To finish on budget</p>
                </div>

                <div class="col-span-2 sm:col-span-1 bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm p-4 sm:p-5 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[11px] sm:text-xs font-semibold text-slate-500">Days left</span>
                        <div class="h-7 w-7 rounded-lg {{ $chip }} flex items-center justify-center shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                    </div>
                    <div class="text-lg sm:text-xl font-black text-slate-900 font-mono mt-3 whitespace-nowrap">{{ $daysLabel }}</div>
                    <p class="text-[10px] sm:text-[11px] font-medium text-slate-400 mt-1.5">Resets {{ $m['reset_day'] }}</p>
                </div>
            </div>

            {{-- 3. MONEY LEFT CHART --}}
            <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6 space-y-4">
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Money left each day</h3>
                    <p class="text-[11px] sm:text-xs text-slate-400 font-medium mt-0.5">
                        @if($m['is_final_day'])
                            How your money moved through the week.
                        @else
                            The dashed line shows where you're heading if you keep this pace.
                        @endif
                    </p>
                </div>

                <div class="relative h-56 w-full" wire:ignore>
                    <canvas id="forecastChart"></canvas>
                </div>

                <div class="flex items-center gap-5 text-[11px] font-semibold text-slate-500">
                    <span class="flex items-center gap-2"><span class="w-5 h-0.5 bg-[var(--brand)] inline-block rounded-full"></span>So far</span>
                    @if(!$m['is_final_day'])
                        <span class="flex items-center gap-2">
                            <span class="w-5 inline-block border-t-2 border-dashed {{ $isRisk ? 'border-rose-500' : 'border-slate-400' }}"></span>If you keep this pace
                        </span>
                    @endif
                </div>
            </div>

            {{-- 4. EXTRA TIPS (only appear if the AI loads) --}}
            @if($aiLoaded && !empty($aiInsight['tips']))
                <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6 space-y-3">
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900">Tips for this week</h3>
                    @foreach($aiInsight['tips'] as $tip)
                        <div class="flex items-start gap-3 p-3.5 bg-slate-50/80 border border-slate-100 rounded-2xl">
                            <div class="h-6 w-6 rounded-lg {{ $chip }} flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.516 0c.85.493 1.508 1.333 1.508 2.316V18"/></svg>
                            </div>
                            <p class="text-xs sm:text-sm font-semibold text-slate-600 leading-relaxed">{{ $tip }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</div>

@if($ok)
<script>
    document.addEventListener('livewire:load', function () {
        const canvas = document.getElementById('forecastChart');
        if (!canvas) return;

        const brand     = (getComputedStyle(document.documentElement).getPropertyValue('--brand') || '#4f39fa').trim();
        const projColor = '{{ $isRisk ? '#f43f5e' : '#94a3b8' }}';
        const todayIdx  = {{ $chart['today_index'] }};
        const peso      = (v) => '₱' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        new Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: @json($chart['labels']),
                datasets: [
                    {
                        label: 'Money left',
                        data: @json($chart['actual']),
                        borderColor: brand,
                        backgroundColor: brand + '14',
                        fill: true,
                        borderWidth: 3,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: brand,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        spanGaps: false
                    },
                    {
                        label: 'Projected',
                        data: @json($chart['projected']),
                        borderColor: projColor,
                        borderWidth: 2.5,
                        borderDash: [6, 6],
                        tension: 0.3,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        spanGaps: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 10,
                        bodyFont: { size: 12, weight: '600' },
                        // The projected line is anchored on today's real point; don't list it twice.
                        filter: (item) => item.raw !== null && !(item.datasetIndex === 1 && item.dataIndex === todayIdx),
                        callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${peso(ctx.raw)}` }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11, weight: '600' }, color: '#94a3b8' } },
                    y: {
                        min: 0,
                        suggestedMax: {{ $chart['pool'] }},
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { size: 10, weight: '600' },
                            color: '#94a3b8',
                            callback: (v) => '₱' + Number(v).toLocaleString()
                        }
                    }
                }
            }
        });
    });
</script>
@endif