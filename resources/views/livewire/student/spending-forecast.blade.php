<div class="min-h-screen bg-slate-50/50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/60 pb-5">
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="text-xl font-black text-slate-900 tracking-tight sm:text-2xl">Spending Forecast</h2>
                    <span class="text-[10px] font-bold bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-md ring-1 ring-indigo-700/10 uppercase tracking-wider">
                        AI-Assisted
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1">
                    Smart future projections based on your weekly habits.
                </p>
            </div>
        </div>

        @if(($forecastResult['status'] ?? '') === 'error')
            <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-slate-800 text-xs font-semibold shadow-sm flex items-center gap-2.5">
                <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ $forecastResult['message'] }}</span>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                
                <div class="bg-white border border-slate-200/70 p-6 rounded-3xl shadow-sm transition-all hover:border-slate-300">
                    <span class="block text-slate-400 font-bold text-[10px] uppercase tracking-widest">Your Daily Spending</span>
                    <div class="text-2xl font-black text-slate-900 mt-2.5 tracking-tight font-mono">
                        ₱{{ $forecastResult['metrics']['daily_velocity'] }}
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium mt-1 block">Average spent per day</span>
                </div>

                <div class="bg-white border border-slate-200/70 p-6 rounded-3xl shadow-sm transition-all hover:border-slate-300">
                    <span class="block text-slate-400 font-bold text-[10px] uppercase tracking-widest">How Long It Will Last</span>
                    <div class="text-2xl font-black text-slate-900 mt-2.5 tracking-tight font-mono">
                        {{ $forecastResult['metrics']['projected_days_left'] }} Days
                    </div>
                    <div class="mt-2">
                        @if($forecastResult['metrics']['is_critical'])
                            <span class="inline-flex items-center gap-1.5 text-[10px] bg-rose-50 text-rose-700 px-2 py-0.5 rounded-md font-bold ring-1 ring-rose-700/10 uppercase tracking-wider">
                                <span class="w-1 h-1 bg-rose-500 rounded-full animate-pulse"></span>
                                Deficit Risk
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md font-bold ring-1 ring-emerald-700/10 uppercase tracking-wider">
                                <span class="w-1 h-1 bg-emerald-500 rounded-full"></span>
                                Stable Pacing
                            </span>
                        @endif
                    </div>
                </div>

                <div class="bg-white border border-slate-200/70 p-6 rounded-3xl shadow-sm transition-all hover:border-slate-300">
                    <span class="block text-slate-400 font-bold text-[10px] uppercase tracking-widest">Estimated Empty Date</span>
                    <div class="text-2xl font-black text-indigo-600 mt-2.5 tracking-tight font-mono">
                        {{ $forecastResult['metrics']['depletion_date'] }}
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium mt-1 block">When your cash runs out</span>
                </div>

            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200/70 shadow-sm">
                <div class="mb-5">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Days Until Zero</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">A line graph showing your balance countdown toward your estimated empty date.</p>
                </div>
                <div class="h-64 relative w-full" wire:ignore>
                    <canvas id="forecastTrajectoryChart"></canvas>
                </div>
            </div>

            <div class="bg-white border border-slate-200/70 rounded-3xl shadow-sm overflow-hidden">
                
                <div class="bg-slate-50/70 px-6 py-4 flex justify-between items-center border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-sm shadow-indigo-600/20 text-white shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide">Behavioral Budget Insights</h3>
                            <span class="text-[9px] font-bold text-slate-400 tracking-widest uppercase block mt-0.5">
                                Engine: {{ str_contains($forecastResult['source'], 'Groq') ? 'Llama 3.1 Cloud Intelligence' : 'Local Backup Framework' }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 bg-white px-2.5 py-1 rounded-md border border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                        <span class="relative flex h-1.5 w-1.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ str_contains($forecastResult['source'], 'Offline') ? 'bg-amber-400' : 'bg-emerald-400' }} opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-1.5 w-1.5 {{ str_contains($forecastResult['source'], 'Offline') ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                        </span>
                        <span>{{ str_contains($forecastResult['source'], 'Offline') ? 'Offline Fallback' : 'Connected' }}</span>
                    </div>
                </div>

                <div class="p-6 space-y-5">
                    <div class="p-5 bg-gradient-to-br from-indigo-50/40 via-white to-white rounded-2xl border border-indigo-100/70 shadow-sm text-xs font-semibold text-slate-600 leading-relaxed border-l-4 border-l-indigo-600">
                        <p class="px-1">
                            {{ $forecastResult['ai_coach_text'] }}
                        </p>
                    </div>
                </div>

            </div>
        @endif

    </div>
</div>

<script>
    document.addEventListener('livewire:load', function () {
        const ctx = document.getElementById('forecastTrajectoryChart').getContext('2d');
        
        let trajectoryChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($forecastResult['chart']['labels'] ?? []),
                datasets: [{
                    label: 'Projected Cash Balance',
                    data: @json($forecastResult['chart']['values'] ?? []),
                    borderColor: '#6366f1',
                    borderWidth: 2.5,
                    fill: true,
                    backgroundColor: function(context) {
                        const chart = context.chart;
                        const {ctx, chartArea} = chart;
                        if (!chartArea) return null;
                        const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.12)');
                        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');
                        return gradient;
                    },
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHitRadius: 12,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        padding: 12,
                        titleFont: { size: 10, weight: '700' },
                        bodyFont: { size: 11, weight: '600' },
                        callbacks: {
                            label: (ctx) => ` Balance: ₱${ctx.raw.toLocaleString(undefined, {minimumFractionDigits: 2})}`
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9, weight: '600' }, color: '#94a3b8', maxRotation: 0 }
                    },
                    y: {
                        grid: { color: '#f1f5f9' },
                        ticks: { 
                            font: { size: 9, weight: '600' }, 
                            color: '#94a3b8',
                            callback: (value) => '₱' + value
                        }
                    }
                }
            }
        });

        // Event listener bridge for real-time reactivity without page refreshes
        window.addEventListener('renderForecastChart', event => {
            const data = event.detail;
            trajectoryChart.data.labels = data.labels;
            trajectoryChart.data.datasets[0].data = data.values;
            trajectoryChart.update();
        });
    });
</script>