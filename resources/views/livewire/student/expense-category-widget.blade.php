<div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-5 sm:p-6 w-full flex flex-col justify-between" wire:init="loadCategoryBreakdown">
    
    <!-- HEADER SECTION -->
    <div class="pb-2">
        <h3 class="text-sm font-extrabold text-slate-900">Expense Categories</h3>
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
            // Updated color palette matching the reference image slices
            $colorPalette = [
                '#ff7052', // Coral / Orange
                '#5b46f6', // Purple / Indigo
                '#4fd1c5', // Teal / Cyan
                '#ffc043', // Yellow
                '#f43f5e', // Pink
                '#3b82f6', // Blue
                '#10b981', // Emerald
                '#8b5cf6'  // Violet
            ];
        @endphp

        <div class="flex flex-col gap-4">
            
            <!-- CENTERED DOUGHNUT CHART -->
            <div class="py-2 flex items-center justify-center">
                <div class="relative h-44 w-44 mx-auto shrink-0" wire:ignore>
                    <canvas id="categoryDistributionChart"></canvas>
                </div>
            </div>

            <!-- CLEAN CATEGORY LIST LEGEND -->
            <div class="space-y-2.5 max-h-[170px] overflow-y-auto pr-1 custom-dashboard-scrollbar">
                @foreach($categoriesData as $index => $category)
                    @php
                        $assignedColor = $colorPalette[$index % count($colorPalette)];
                    @endphp
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $assignedColor }};"></span>
                            <span class="font-semibold text-slate-600 truncate">{{ $category['name'] }}</span>
                        </div>
                        <span class="font-black text-slate-900 font-mono shrink-0 pl-2">
                            ₱{{ number_format($category['total'], 0) }}
                        </span>
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
            background: #e2e8f0;
            border-radius: 10px;
        }
        .custom-dashboard-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>

    <script>
        document.addEventListener('livewire:load', function () {
            const chartCanvas = document.getElementById('categoryDistributionChart');
            if (!chartCanvas) return;

            const colorPalette = [
                '#ff7052', // Coral / Orange
                '#5b46f6', // Purple / Indigo
                '#4fd1c5', // Teal / Cyan
                '#ffc043', // Yellow
                '#f43f5e', // Pink
                '#3b82f6', // Blue
                '#10b981', // Emerald
                '#8b5cf6'  // Violet
            ];

            const ctx = chartCanvas.getContext('2d');
            let categoryChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: colorPalette,
                        borderWidth: 2.5,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
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