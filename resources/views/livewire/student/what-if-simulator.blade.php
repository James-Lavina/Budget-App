<div class="max-w-7xl mx-auto p-4 sm:p-6 lg:p-8 space-y-8" wire:init="initSimulation">
    
    <div class="border-b border-slate-200/60 pb-6">
        
        <nav class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-3">
            <a href="{{ route('student.dashboard') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
            <svg class="w-2.5 h-2.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
            <span class="text-slate-500">Predictive Modeling</span>
        </nav>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-baseline gap-2.5">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight sm:text-2xl">What-If Simulator</h1>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-700/10 uppercase tracking-wider self-center sm:self-auto">
                        Predictive Engine
                    </span>
                </div>
                <p class="text-xs text-slate-500 font-medium mt-1">Test out a purchase before tapping your wallet to see exactly how it changes your daily safe-to-spend limits.</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <div class="bg-white rounded-3xl border border-slate-200/70 shadow-sm p-6 space-y-6 h-fit sticky top-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-xs font-bold text-slate-900 uppercase tracking-widest flex items-center gap-2">
                    <span class="w-1.5 h-3 bg-amber-500 rounded-full"></span>
                    Simulation Controls
                </h2>
            </div>

            <div class="space-y-5">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Simulated Purchase Item</label>
                    <div class="relative rounded-xl shadow-sm group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a1.442 1.442 0 002.04 0l4.318-4.318a1.442 1.442 0 000-2.04l-9.581-9.581a2.25 2.25 0 00-1.591-.659z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                            </svg>
                        </div>
                        <input type="text" 
                               wire:model.defer="itemName" 
                               placeholder="e.g., Shoes, Mechanical Keyboard, Food" 
                               class="w-full text-xs pl-10 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50/30 font-semibold text-slate-800 placeholder-slate-400 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 focus:outline-none">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Estimated Item Cost</label>
                    <div class="relative rounded-xl shadow-sm group">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none font-mono text-xs font-bold text-slate-400">
                            ₱
                        </div>
                        <input type="number" 
                               min="0" 
                               step="1" 
                               wire:model.defer="purchaseAmount" 
                               class="w-full text-xs pl-9 pr-4 py-3 rounded-xl border border-slate-200 bg-slate-50/30 font-bold text-slate-800 placeholder-slate-400 font-mono focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/10 focus:outline-none" 
                               placeholder="0.00">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2 pt-2">
                    <button wire:click="resetSimulation" 
                            class="col-span-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold py-3.5 px-3 rounded-xl transition-all duration-200 uppercase tracking-wider flex items-center justify-center">
                        Clear
                    </button>
                    
                    <button wire:click="runSimulation" 
                            class="col-span-2 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.98] text-white text-xs font-black py-3.5 px-4 rounded-xl transition-all duration-200 uppercase tracking-wider shadow-sm shadow-indigo-600/20 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <span>Test Impact</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white border border-slate-200/70 rounded-3xl p-5 shadow-sm flex items-center gap-4 transition-all hover:border-slate-300">
                    <div class="h-12 w-12 bg-slate-50 border border-slate-100 text-slate-700 rounded-2xl flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <span class="block text-[9px] text-slate-400 uppercase font-bold tracking-widest mb-0.5">Current Safe-to-Spend</span>
                        <span class="text-xl font-black text-slate-900 tracking-tight font-mono">₱{{ number_format($currentSafeToSpend, 2) }}<span class="text-xs text-slate-400 font-medium">/day</span></span>
                        <span class="text-[10px] text-slate-400 font-medium block mt-0.5">Active across next <span class="font-bold text-slate-600">{{ $daysRemaining }} days</span></span>
                    </div>
                </div>

                <div class="bg-white border rounded-3xl p-5 shadow-sm flex items-center gap-4 transition-all duration-300 {{ $isDeficit ? 'border-rose-200 bg-rose-50/30' : 'border-indigo-100/80 bg-indigo-50/10' }}">
                    <div class="h-12 w-12 rounded-2xl flex items-center justify-center shrink-0 {{ $isDeficit ? 'bg-rose-100/80 text-rose-600 border border-rose-200/40' : 'bg-indigo-100/80 text-indigo-600 border border-indigo-200/40' }}">
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
                    <div>
                        <span class="block text-[9px] uppercase font-bold tracking-widest {{ $isDeficit ? 'text-rose-500' : 'text-indigo-500' }}">Adjusted Safe-to-Spend</span>
                        <span class="text-xl font-black font-mono tracking-tight {{ $isDeficit ? 'text-rose-600' : 'text-slate-900' }}">
                            @if($isDeficit)
                                ₱0.00
                            @else
                                ₱{{ number_format($newSafeToSpend, 2) }}<span class="text-xs text-slate-400 font-medium">/day</span>
                            @endif
                        </span>
                        <span class="text-[10px] text-slate-500 font-medium block">
                            @if($isDeficit)
                                Deficit: <span class="font-bold text-rose-600 font-mono">₱{{ number_format(abs($newRemaining), 2) }}</span>
                            @else
                                Remaining Cash: <span class="font-bold text-slate-700 font-mono">₱{{ number_format($newRemaining, 2) }}</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-3xl border border-slate-200/70 shadow-sm">
                <div class="mb-5">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Weekly Cash Breakdown</h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">A clean view of how this item takes up space in your current weekly allowance pool.</p>
                </div>
                <div class="h-28 relative w-full" wire:ignore>
                    <canvas id="weeklyBreakdownChart"></canvas>
                </div>
            </div>

            <div class="bg-gradient-to-br from-indigo-50/40 via-white to-white text-slate-800 p-6 rounded-3xl border border-indigo-100/80 shadow-sm flex gap-4 items-start relative overflow-hidden transition-all hover:shadow-[0_4px_20px_-4px_rgba(99,102,241,0.06)]">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
                
                <div class="p-2.5 bg-indigo-600 text-white rounded-xl shadow-sm shadow-indigo-600/10 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                
                <div class="space-y-2.5 z-10 w-full">
                    <div class="flex items-center justify-between gap-2 w-full">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Behavioral Strategy Analysis</h4>
                        
                        @if(!$loadingAi && !empty($aiInsight))
                            @if($isOfflineMode)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-bold bg-amber-500/10 text-amber-700 ring-1 ring-amber-500/20 uppercase tracking-wider transition animate-fade-in">
                                    Offline Engine
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-bold bg-indigo-50 text-indigo-700 ring-1 ring-indigo-700/10 uppercase tracking-wider flex items-center gap-1 transition animate-fade-in">
                                    <span class="w-1 h-1 bg-indigo-500 rounded-full animate-pulse"></span>
                                    Live Groq AI
                                </span>
                            @endif
                        @endif
                    </div>

                    <p class="text-xs text-slate-600 font-semibold leading-relaxed">
                        @if($loadingAi)
                            <span class="animate-pulse text-indigo-600 italic font-black flex items-center gap-1.5">
                                <span class="w-1 h-1 bg-indigo-500 rounded-full animate-ping"></span>
                                Asking your upperclassman financial coach...
                            </span>
                        @else
                            {{ $aiInsight }}
                        @endif
                    </p>
                </div>
            </div>

        </div>
    </div>

    <script>
        document.addEventListener('livewire:load', function () {
            const ctx = document.getElementById('weeklyBreakdownChart').getContext('2d');
            
            let breakdownChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Current Budget Cycle Pool'],
                    datasets: []
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            display: true, 
                            position: 'top',
                            labels: { boxWidth: 12, boxHeight: 12, usePointStyle: true, font: { size: 10, weight: '700' } } 
                        },
                        tooltip: { padding: 12, bodyFont: { size: 11, weight: 'bold' }, callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ₱${ctx.raw.toLocaleString()}` } }
                    },
                    scales: {
                        x: { stacked: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9, weight: '600' }, color: '#94a3b8' } },
                        y: { stacked: true, display: false }
                    }
                }
            });

            window.addEventListener('renderWeeklyImpactChart', event => {
                const data = event.detail;
                breakdownChart.data.datasets = [
                    { label: 'Already Consumed', data: [data.spent], backgroundColor: '#e2e8f0', borderRadius: 6 },
                    { label: 'Simulated Purchase Outlay', data: [data.simulated], backgroundColor: '#6366f1', borderRadius: 6 },
                    { label: 'Residual Liquid Reserve', data: [data.remaining], backgroundColor: '#10b981', borderRadius: 6 }
                ];
                breakdownChart.update();
            });
        });
    </script>
</div>