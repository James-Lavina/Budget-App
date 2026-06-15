<div class="bg-white rounded-3xl border border-slate-200/70 shadow-sm p-6 w-full" wire:init="loadCategoryBreakdown">
    <div class="border-b border-slate-100 pb-4 mb-5">
        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-widest flex items-center gap-2">
            <span class="w-1.5 h-3 bg-cyan-500 rounded-full"></span>
            Expenses by Category
        </h3>
        <p class="text-[11px] text-slate-400 font-medium mt-0.5">A visual breakdown of where your money went this month.</p>
    </div>

    @if(!$hasExpenses)
        <div class="py-12 flex flex-col items-center justify-center text-center space-y-3">
            <div class="h-10 w-10 bg-slate-50 border border-slate-100 text-slate-400 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
            </div>
            <p class="text-xs text-slate-400 font-semibold max-w-[200px]">No transactions logged yet for this weekly cycle window.</p>
        </div>
    @else
        @php
            // Define the visual color palette in PHP so it matches Chart.js exactly and renders instantly
            $colorPalette = ['#6366f1', '#10b981', '#f59e0b', '#f43f5e', '#8b5cf6', '#06b6d4'];
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-5 gap-6 items-center">
            
            <div class="sm:col-span-2 relative flex items-center justify-center h-40 w-40 mx-auto shrink-0" wire:ignore>
                <canvas id="categoryDistributionChart"></canvas>
                
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center transform translate-y-1">
                    <span class="text-[9px] uppercase tracking-wider font-bold text-slate-400">Total Spent</span>
                    <span class="text-sm font-black text-slate-800 font-mono">₱{{ number_format($totalSpent, 2) }}</span>
                </div>
            </div>

            <div class="sm:col-span-3 space-y-2 max-h-[168px] overflow-y-auto pr-1.5 custom-dashboard-scrollbar">
                @foreach($categoriesData as $index => $category)
                    @php
                        // Cycle through colors based on loop index to guarantee accurate chart-to-text mapping
                        $assignedColor = $colorPalette[$index % count($colorPalette)];
                    @endphp
                    <div class="flex items-center justify-between p-2 rounded-xl border border-slate-50 bg-slate-50/20 hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0 shadow-sm" style="background-color: {{ $assignedColor }};"></span>
                            
                            <span class="text-xs font-bold text-slate-700 tracking-tight truncate">{{ $category['name'] }}</span>
                        </div>
                        <div class="text-right shrink-0 pl-2">
                            <span class="text-xs font-black text-slate-900 font-mono">₱{{ number_format($category['total'], 0) }}</span>
                            <span class="text-[10px] text-slate-400 font-bold block">{{ $category['percentage'] }}%</span>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>
    @endif

    <style>
        .custom-dashboard-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-dashboard-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-dashboard-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1; /* slate-300 */
            border-radius: 10px;
        }
        .custom-dashboard-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; /* slate-400 */
        }
    </style>

    <script>
        document.addEventListener('livewire:load', function () {
            const chartCanvas = document.getElementById('categoryDistributionChart');
            if (!chartCanvas) return;

            const colorPalette = [
                '#6366f1', // indigo-500
                '#10b981', // emerald-500
                '#f59e0b', // amber-500
                '#f43f5e', // rose-500
                '#8b5cf6', // violet-500
                '#06b6d4', // cyan-500
                '#ec4899', // pink-500 (New)
                '#f97316', // orange-500 (New)
                '#84cc16', // lime-500 (New)
                '#64748b'  // slate-500 (New)
            ];

            const ctx = chartCanvas.getContext('2d');
            let categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: colorPalette,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            padding: 10,
                            bodyFont: { size: 11, weight: 'bold' },
                            callbacks: {
                                label: (ctx) => ` ${ctx.label}: ₱${ctx.raw.toLocaleString()}`
                            }
                        }
                    }
                }
            });

            window.addEventListener('updateCategoryChart', event => {
                categoryChart.data.labels = event.detail.labels;
                categoryChart.data.datasets[0].data = event.detail.values;
                categoryChart.update();
            });
        });
    </script>
</div>