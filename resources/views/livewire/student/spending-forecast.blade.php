<div class="min-h-screen bg-slate-50/50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">

        <!-- Clean, Modern Header Area -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight sm:text-3xl">Spending Forecast</h2>
                    <span class="text-[10px] font-bold bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full border border-indigo-100 uppercase tracking-wide">
                        AI-Assisted
                    </span>
                </div>
                <p class="text-sm text-slate-500 mt-1">
                    Predictive pacing projections based on your real-world weekly spending habits.
                </p>
            </div>
        </div>

        @if(($forecastResult['status'] ?? '') === 'error')
            <div class="p-5 bg-rose-50 border border-rose-100 rounded-2xl text-center text-rose-800 text-sm font-semibold shadow-sm">
                👋 {{ $forecastResult['message'] }}
            </div>
        @else
            <!-- Modernized Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                
                <!-- Metric 1: Average Daily Burn Speed -->
                <div class="bg-white border border-slate-200/80 p-6 rounded-2xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0,05)] transition-all hover:shadow-md">
                    <span class="block text-slate-400 font-bold text-[11px] uppercase tracking-wider">Your Daily Spending</span>
                    <div class="text-3xl font-black text-slate-900 mt-2 tracking-tight">
                        ₱{{ $forecastResult['metrics']['daily_velocity'] }}
                    </div>
                    <span class="text-xs text-slate-500 mt-1.5 block">Average spent per day</span>
                </div>

                <!-- Metric 2: Estimated Balance Longevity -->
                <div class="bg-white border border-slate-200/80 p-6 rounded-2xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0,05)] transition-all hover:shadow-md">
                    <span class="block text-slate-400 font-bold text-[11px] uppercase tracking-wider">How Long It Will Last</span>
                    <div class="text-3xl font-black text-slate-900 mt-2 tracking-tight flex items-baseline gap-2">
                        <span>{{ $forecastResult['metrics']['projected_days_left'] }} Days</span>
                    </div>
                    <div class="mt-2">
                        @if($forecastResult['metrics']['is_critical'])
                            <span class="inline-flex items-center text-[11px] bg-rose-50 text-rose-700 px-2.5 py-0.5 rounded-full font-bold border border-rose-100 uppercase tracking-wide">
                                ⚠️ Deficit Risk
                            </span>
                        @else
                            <span class="inline-flex items-center text-[11px] bg-emerald-50 text-emerald-700 px-2.5 py-0.5 rounded-full font-bold border border-emerald-100 uppercase tracking-wide">
                                ✅ Stable Pacing
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Metric 3: Target Exhaustion Date Milestone -->
                <div class="bg-white border border-slate-200/80 p-6 rounded-2xl shadow-[0_2px_8px_-3px_rgba(0,0,0,0,05)] transition-all hover:shadow-md">
                    <span class="block text-slate-400 font-bold text-[11px] uppercase tracking-wider">Estimated Empty Date</span>
                    <div class="text-3xl font-black text-indigo-600 mt-2 tracking-tight">
                        {{ $forecastResult['metrics']['depletion_date'] }}
                    </div>
                    <span class="text-xs text-slate-500 mt-1.5 block">When your cash runs out</span>
                </div>

            </div>

            <!-- Central AI Coach Section -->
            <div class="bg-white border border-slate-200/90 rounded-2xl shadow-[0_4px_12px_-4px_rgba(0,0,0,0.05)] overflow-hidden">
                
                <!-- Coach Header -->
                <div class="bg-slate-50 px-6 py-4 flex justify-between items-center border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 bg-indigo-600 rounded-lg flex items-center justify-center shadow-sm text-sm">
                            🤖
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Behavioral Budget Insights</h3>
                            <span class="text-[9px] font-bold text-slate-400 tracking-wider uppercase block">
                                Engine: {{ str_contains($forecastResult['source'], 'Groq') ? 'Llama 3.1 Cloud Intelligence' : 'Local Backup Framework' }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Network Status Pin Indicator -->
                    <div class="flex items-center gap-2 bg-white px-2.5 py-1 rounded-full border border-slate-200 text-[10px] font-semibold text-slate-600">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ str_contains($forecastResult['source'], 'Offline') ? 'bg-amber-400' : 'bg-emerald-400' }} opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 {{ str_contains($forecastResult['source'], 'Offline') ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                        </span>
                        <span>{{ str_contains($forecastResult['source'], 'Offline') ? 'Offline Fallback' : 'Connected' }}</span>
                    </div>
                </div>

                <!-- Live Generated Text Advice Container Box -->
                <div class="p-6 space-y-4">
                    <div class="relative p-5 bg-gradient-to-r from-slate-50 to-indigo-50/20 rounded-xl border border-slate-100 text-sm leading-relaxed font-medium text-slate-700 italic">
                        <span class="absolute -top-3 left-4 text-3xl text-slate-200 font-serif select-none">“</span>
                        <p class="relative z-10 px-2">
                            {{ $forecastResult['ai_coach_text'] }}
                        </p>
                    </div>

                    <!-- Thesis Defense System Context Footer -->
                    <div class="text-[11px] text-slate-400 leading-normal bg-blue-50/20 p-3.5 rounded-xl border border-blue-100/50 flex items-start gap-2">
                        <span class="text-blue-500 mt-0.5">💡</span>
                        <p>
                            <span class="font-bold text-slate-600">Defense System Architecture note:</span> This dashboard implements a hybrid algorithmic execution model. If network latency or internet failure breaks connectivity with the cloud provider during user testing, a localized deterministic module instantly safeguards the UI with native analytical string injections to maintain an uninterrupted system experience.
                        </p>
                    </div>
                </div>

            </div>
        @endif

    </div>
</div>