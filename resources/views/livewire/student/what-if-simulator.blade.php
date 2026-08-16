<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-7xl mx-auto space-y-6">
        
        <!-- Header Section -->
        <div class="border-b border-slate-200/60 pb-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Purchase Simulator</h1>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-700/10 uppercase tracking-wider">
                            Interactive Test
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        Simulate potential purchases to view real-time impacts on your daily allowance before spending.
                    </p>
                </div>
                @if($purchaseAmount)
                    <button 
                        wire:click="resetSimulation" 
                        type="button"
                        class="px-3.5 py-2 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-all flex items-center gap-1.5 self-start md:self-auto"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset Simulation</span>
                    </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
         
            <!-- Left Panel: Input Controls & Presets -->
            <div class="lg:col-span-5 bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-5 lg:sticky lg:top-8 z-10">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">Simulated Item Details</h3>
                    <span class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-2 py-0.5 rounded-md">Live Calculation</span>
                </div>

                <!-- Input: Item Name -->
                <div class="space-y-1.5">
                    <label for="itemName" class="block text-xs font-bold text-slate-700">What do you want to buy?</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a1.442 1.442 0 002.04 0l4.318-4.318a1.442 1.442 0 000-2.04l-9.581-9.581a2.25 2.25 0 00-1.591-.659z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                            </svg>
                        </span>
                        <input id="itemName" type="text" wire:model.lazy="itemName"
                            placeholder="e.g., Campus Coffee, Books"
                            class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200/80 rounded-2xl text-slate-900 font-semibold text-xs placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all outline-none">
                    </div>
                </div>

                <!-- Input: Cost -->
                <div class="space-y-1.5">
                    <label for="purchaseAmount" class="block text-xs font-bold text-slate-700">Estimated Cost (₱)</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-800 font-extrabold text-sm">₱</span>
                        <input id="purchaseAmount" type="number" min="0" step="1" wire:model.lazy="purchaseAmount" placeholder="0.00"
                            class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200/80 rounded-2xl text-slate-900 font-extrabold text-sm placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-mono outline-none">
                    </div>
                </div>

                <!-- Quick Presets -->
                <div class="pt-3 border-t border-slate-100">
                    <label class="block text-[11px] font-bold text-slate-500 mb-2">Quick Student Presets</label>
                    <div class="flex flex-wrap gap-2">
                        <button 
                            type="button" 
                            wire:click="applyPreset(50, 'Milk Tea')"
                            class="px-2.5 py-1.5 text-xs font-bold bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 hover:border-indigo-200 rounded-xl transition-all"
                        >
                            +₱50 Milk Tea
                        </button>
                        <button 
                            type="button" 
                            wire:click="applyPreset(150, 'Campus Lunch')"
                            class="px-2.5 py-1.5 text-xs font-bold bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 hover:border-indigo-200 rounded-xl transition-all"
                        >
                            +₱150 Lunch
                        </button>
                        <button 
                            type="button" 
                            wire:click="applyPreset(500, 'Textbooks')"
                            class="px-2.5 py-1.5 text-xs font-bold bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 hover:border-indigo-200 rounded-xl transition-all"
                        >
                            +₱500 Books
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Predictive Output & Analysis -->
            <div class="lg:col-span-7 space-y-5">
             
                <!-- Metric Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                 
                    <!-- Baseline Daily Quota -->
                    <div class="bg-white border border-slate-100 rounded-3xl p-5 shadow-sm flex items-center gap-4">
                        <div class="h-11 w-11 bg-slate-50 border border-slate-100 text-slate-700 rounded-2xl flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <span class="block text-[9px] text-slate-400 uppercase font-bold tracking-widest mb-0.5">Current Safe-to-Spend</span>
                            <span class="text-lg font-black text-slate-900 tracking-tight font-mono">
                                ₱{{ number_format($currentSafeToSpend, 2) }}<span class="text-xs text-slate-400 font-medium">/day</span>
                            </span>
                            <span class="text-[10px] text-slate-400 font-medium block mt-0.5">Active for <span class="font-bold text-slate-600">{{ $daysRemaining }} day(s)</span> left</span>
                        </div>
                    </div>

                    <!-- Outcome Daily Quota Card -->
                    <div class="border rounded-3xl p-5 shadow-sm flex items-center gap-4 transition-all duration-300 {{ $isDeficit ? 'border-rose-200 bg-rose-50/40' : 'border-indigo-200/80 bg-indigo-50/30 ring-1 ring-indigo-500/10' }}">
                        <div class="h-11 w-11 rounded-2xl flex items-center justify-center shrink-0 {{ $isDeficit ? 'bg-rose-100 text-rose-600 border border-rose-200/50' : 'bg-indigo-600 text-white shadow-md shadow-indigo-200' }}">
                            @if($isDeficit)
                                <svg class="w-5 h-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1">
                            <span class="block text-[9px] uppercase font-bold tracking-widest {{ $isDeficit ? 'text-rose-600' : 'text-indigo-600' }}">Simulated Safe-to-Spend</span>
                            
                            <!-- Staked Layout to Prevent Red Impact Text Overflow -->
                            <div class="flex flex-col">
                                <span class="text-lg font-black font-mono tracking-tight {{ $isDeficit ? 'text-rose-600' : 'text-slate-900' }}">
                                    @if($isDeficit)
                                        ₱0.00
                                    @else
                                        ₱{{ number_format($newSafeToSpend, 2) }}<span class="text-xs text-slate-400 font-medium">/day</span>
                                    @endif
                                </span>
                                @if($purchaseAmount && floatval($purchaseAmount) > 0 && !$isDeficit)
                                    <span class="text-[11px] font-bold text-rose-500 font-mono mt-0.5">
                                        (-₱{{ number_format($dailyImpactDelta, 2) }}/day)
                                    </span>
                                @endif
                            </div>

                            <span class="text-[10px] text-slate-500 font-medium block mt-1">
                                @if($isDeficit)
                                    Deficit: <span class="font-bold text-rose-600 font-mono">₱{{ number_format(abs($newRemaining), 2) }}</span>
                                @else
                                    Money Left: <span class="font-bold text-slate-700 font-mono">₱{{ number_format($newRemaining, 2) }}</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- AI Advice Banner -->
                <div class="p-5 rounded-3xl border transition-all duration-200 {{ $isDeficit ? 'bg-rose-50/70 border-rose-200/80 text-rose-900' : 'bg-gradient-to-br from-indigo-50/70 via-white to-blue-50/40 border-indigo-100 text-slate-800' }}">
                    <div class="flex items-start gap-3.5">
                        <div class="p-2 rounded-xl shrink-0 {{ $isDeficit ? 'bg-rose-100 text-rose-600' : 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/10' }}">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="space-y-1.5 w-full">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-[10px] font-black uppercase tracking-widest {{ $isDeficit ? 'text-rose-800' : 'text-slate-400' }}">
                                    {{ $isDeficit ? 'Budget Overdraft Warning' : 'AI Budget Advice' }}
                                </h4>
                                <div wire:loading.remove wire:target="runSimulation, resetSimulation, applyPreset">
                                    @if(!empty($aiInsight))
                                        @if($isOfflineMode)
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-slate-100 text-slate-600 border border-slate-200 uppercase tracking-wider">
                                                Instant Calculation
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-700/10 uppercase tracking-wider flex items-center gap-1">
                                                <span class="w-1 h-1 bg-indigo-500 rounded-full animate-pulse"></span>
                                                AI Active
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <span wire:loading wire:target="runSimulation, applyPreset" class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-700/10 uppercase tracking-wider flex items-center gap-1">
                                    Analyzing...
                                </span>
                            </div>
                            <div class="text-xs font-semibold leading-relaxed">
                                <div wire:loading wire:target="runSimulation, applyPreset" class="animate-pulse text-indigo-600 italic font-black flex items-center gap-1.5 py-0.5">
                                    <span class="w-1.5 h-1.5 bg-indigo-500 rounded-full animate-ping"></span>
                                    Calculating impact on weekly allowance...
                                </div>
                                <div wire:loading.remove wire:target="runSimulation, applyPreset">
                                    {{ $aiInsight }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pattern 1: Multi-Color Cash Breakdown Chart -->
                <div class="bg-white p-5 rounded-3xl border border-slate-100 shadow-sm space-y-3">
                    <div>
                        <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">Weekly Budget Breakdown</h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Stacked breakdown accounting for spent cash, savings set aside, simulated item, and remaining allowance.</p>
                    </div>
                    <div class="h-36 relative w-full" wire:ignore>
                        <canvas id="weeklyBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Setup Script (Pattern 1 with Savings Dataset) -->
    <script>
        document.addEventListener('livewire:load', function () {
            const ctx = document.getElementById('weeklyBreakdownChart').getContext('2d');
            let breakdownChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Weekly Allowance'],
                    datasets: [
                        { label: 'Already Spent', data: [0], backgroundColor: '#94a3b8', borderRadius: 6 },
                        { label: 'Savings Set Aside', data: [0], backgroundColor: '#06b6d4', borderRadius: 6 },
                        { label: 'This Purchase', data: [0], backgroundColor: '#6366f1', borderRadius: 6 },
                        { label: 'Money Left', data: [0], backgroundColor: '#10b981', borderRadius: 6 },
                        { label: 'Overdraft / Deficit', data: [0], backgroundColor: '#f43f5e', borderRadius: 6 }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: { boxWidth: 10, boxHeight: 10, font: { size: 10, weight: '700' } }
                        },
                        tooltip: { 
                            padding: 10, 
                            bodyFont: { size: 11, weight: 'bold' }, 
                            callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ₱${ctx.raw.toLocaleString()}` } 
                        }
                    },
                    scales: {
                        x: { stacked: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9, weight: '600' }, color: '#94a3b8' } },
                        y: { stacked: true, display: false }
                    }
                }
            });

            window.addEventListener('renderWeeklyImpactChart', event => {
                const data = event.detail;
                breakdownChart.data.datasets[0].data = [data.spent];
                breakdownChart.data.datasets[1].data = [data.savings || 0];
                breakdownChart.data.datasets[2].data = [data.simulated];
                breakdownChart.data.datasets[3].data = [data.remaining];
                breakdownChart.data.datasets[4].data = [data.deficit || 0];
                breakdownChart.update();
            });
        });
    </script>
</div>