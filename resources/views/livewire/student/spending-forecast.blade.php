@php
    $chartLabels = $forecastResult['chart']['labels'] ?? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $chartActual = $forecastResult['chart']['actual'] ?? [];
    $chartPredicted = $forecastResult['chart']['predicted'] ?? [];
    $chartAllowance = $forecastResult['chart']['allowance'] ?? 2000;
@endphp

<div class="min-h-screen py-6 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-6">
        
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Spending Forecast</h2>
                <p class="text-xs text-slate-500 font-medium mt-0.5">
                    A look at where your week is heading.
                </p>
            </div>
        </div>

        @if(($forecastResult['status'] ?? '') === 'error')
            <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-slate-800 text-xs font-semibold shadow-sm flex items-center gap-2.5">
                <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ $forecastResult['message'] ?? 'An error occurred while loading the forecast.' }}</span>
            </div>
        @else

            <!-- Top 3 Status Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                
                <!-- Card 1: On Track -->
                <div class="bg-white border {{ !($forecastResult['metrics']['is_critical'] ?? false) && !($forecastResult['metrics']['is_faster'] ?? false) ? 'border-emerald-400 ring-2 ring-emerald-400/20' : 'border-slate-200/80' }} p-5 rounded-3xl shadow-sm transition-all">
                    <div class="h-8 w-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold mb-3">
                        ✓
                    </div>
                    <h3 class="text-base font-bold text-slate-900">On Track</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">You'll finish within budget.</p>
                </div>

                <!-- Card 2: Spending Faster -->
                <div class="bg-white border {{ ($forecastResult['metrics']['is_faster'] ?? false) ? 'border-amber-400 ring-2 ring-amber-400/20' : 'border-slate-200/80' }} p-5 rounded-3xl shadow-sm transition-all">
                    <div class="h-8 w-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold mb-3">
                        ↗
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Spending Faster</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Slightly ahead of plan.</p>
                </div>

                <!-- Card 3: Budget Risk -->
                <div class="bg-white border {{ ($forecastResult['metrics']['is_critical'] ?? false) ? 'border-rose-400 ring-2 ring-rose-400/20' : 'border-slate-200/80' }} p-5 rounded-3xl shadow-sm transition-all">
                    <div class="h-8 w-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center font-bold mb-3">
                        ⚠
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Budget Risk</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Likely to overspend.</p>
                </div>

            </div>

            <!-- Main Weekly Spending Trend Graph -->
            <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-200/80 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-extrabold text-slate-900">Weekly Spending Trend</h3>
                    @if($forecastResult['metrics']['is_critical'] ?? false)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">
                            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> Deficit Risk
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> On track
                        </span>
                    @endif
                </div>

                <!-- Chart Canvas Container -->
                <div class="h-72 relative w-full pt-2" wire:ignore>
                    <canvas id="forecastTrajectoryChart"></canvas>
                </div>

                <!-- Custom Bottom Chart Legend -->
                <div class="flex items-center gap-6 pt-2 text-xs font-semibold text-slate-600">
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-2 rounded-full bg-indigo-600 inline-block"></span>
                        <span>Actual spending</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 h-2 rounded-full bg-teal-400 border border-dashed border-teal-500 inline-block"></span>
                        <span>Predicted</span>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Predicted Remaining Budget & AI Insights -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-5">
                
                <!-- Left Indigo Card -->
                <div class="md:col-span-2 bg-indigo-600 text-white rounded-3xl p-7 flex flex-col justify-between shadow-lg shadow-indigo-600/10 min-h-[220px]">
                    <div class="space-y-1">
                        <span class="text-xs font-semibold text-indigo-200 block uppercase tracking-wider">Predicted remaining budget</span>
                        <div class="text-4xl font-black tracking-tight font-mono">
                            ₱{{ $forecastResult['metrics']['predicted_remaining'] ?? '0' }}
                        </div>
                    </div>
                    <div class="text-xs text-indigo-200 font-medium">
                        by the end of the week (Sunday)
                    </div>
                </div>

                <!-- Right White Insights Card -->
                <div class="md:col-span-3 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <div class="h-7 w-7 rounded-full bg-orange-100 text-orange-500 flex items-center justify-center font-bold text-sm">
                                💡
                            </div>
                            <h3 class="text-base font-extrabold text-slate-900">Friendly AI Insights</h3>
                        </div>

                        <div class="space-y-3">
                            @if(!empty($forecastResult['ai_coach_text']))
                                @foreach(explode('|', $forecastResult['ai_coach_text']) as $tip)
                                    @if(trim($tip))
                                        <div class="p-3.5 bg-slate-50/80 border border-slate-100 rounded-2xl flex items-start gap-3">
                                            <span class="text-amber-500 font-bold shrink-0 mt-0.5">💡</span>
                                            <p class="text-xs font-semibold text-slate-600 leading-relaxed">
                                                {{ trim($tip) }}
                                            </p>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                        <span>Engine: {{ str_contains($forecastResult['source'] ?? '', 'Groq') ? 'gpt-oss-20b' : 'Local Module' }}</span>
                        <span class="{{ str_contains($forecastResult['source'] ?? '', 'Offline') ? 'text-amber-500' : 'text-emerald-500' }}">● {{ str_contains($forecastResult['source'] ?? '', 'Offline') ? 'Offline' : 'Connected' }}</span>
                    </div>
                </div>

            </div>

        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function () {
        const ctx = document.getElementById('forecastTrajectoryChart').getContext('2d');
        const allowanceAmount = {{ $chartAllowance }};

        // Custom plugin to draw the "Allowance" label with proper padding
        const allowanceLabelPlugin = {
            id: 'allowanceLabel',
            afterDraw(chart) {
                const { ctx, chartArea, scales } = chart;
                if (!chartArea) return;
                const yPos = scales.y.getPixelForValue(allowanceAmount);
                if (yPos >= chartArea.top && yPos <= chartArea.bottom) {
                    ctx.save();
                    ctx.font = 'bold 10px sans-serif';
                    ctx.fillStyle = '#f43f5e';
                    ctx.textAlign = 'right';
                    ctx.fillText('Allowance', chartArea.right - 8, yPos - 8);
                    ctx.restore();
                }
            }
        };

        let trajectoryChart = new Chart(ctx, {
            type: 'line',
            plugins: [allowanceLabelPlugin],
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Actual spending',
                        data: @json($chartActual),
                        borderColor: '#6366f1',
                        backgroundColor: '#6366f1',
                        borderWidth: 3,
                        tension: 0.35,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#6366f1',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        spanGaps: false
                    },
                    {
                        label: 'Predicted',
                        data: @json($chartPredicted),
                        borderColor: '#2dd4bf',
                        backgroundColor: '#2dd4bf',
                        borderWidth: 3,
                        borderDash: [6, 6],
                        tension: 0.35,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#2dd4bf',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        spanGaps: false
                    },
                    {
                        label: 'Allowance',
                        data: Array(7).fill(allowanceAmount),
                        borderColor: '#f43f5e',
                        borderWidth: 1.5,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 10,
                        titleFont: { size: 11, weight: '700' },
                        bodyFont: { size: 12, weight: '600' },
                        callbacks: {
                            label: (ctx) => ` ${ctx.dataset.label}: ₱${ctx.raw ? ctx.raw.toLocaleString(undefined, {minimumFractionDigits: 2}) : 0}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '600' }, color: '#94a3b8' }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        suggestedMax: allowanceAmount * 1.15, // Adds 15% headroom so Allowance text never gets clipped!
                        ticks: {
                            font: { size: 10, weight: '600' },
                            color: '#94a3b8',
                            callback: (value) => value
                        }
                    }
                }
            }
        });

        window.addEventListener('renderForecastChart', event => {
            const data = event.detail;
            trajectoryChart.data.labels = data.labels;
            trajectoryChart.data.datasets[0].data = data.actual;
            trajectoryChart.data.datasets[1].data = data.predicted;
            trajectoryChart.data.datasets[2].data = Array(7).fill(data.allowance);
            trajectoryChart.options.scales.y.suggestedMax = data.allowance * 1.15;
            trajectoryChart.update();
        });
    });
</script>