<div class="min-h-screen bg-slate-50/50 py-8 px-4 sm:px-6 lg:px-8 text-slate-800 antialiased">
    <div class="max-w-5xl mx-auto space-y-6">

        <!-- SECTION 1: HERO METRICS CARD (Safe-To-Spend Balance Display) -->
        <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 rounded-3xl p-6 md:p-8 text-white shadow-xl shadow-indigo-600/10 relative overflow-hidden">
            <!-- Structural Background Decorative Glow Nodes -->
            <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/30 rounded-full blur-2xl pointer-events-none"></div>
            <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-purple-500/20 rounded-full blur-2xl pointer-events-none"></div>

            <div class="relative z-10 space-y-6">
                <div class="flex justify-between items-center flex-wrap gap-3">
                    <span class="text-[10px] font-black uppercase tracking-widest text-indigo-200/90">
                        Your Safe-to-Spend Money Today
                    </span>
                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/10 backdrop-blur-sm font-bold tracking-wide">
                        {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'Day' : 'Days' }} Left This Week
                    </span>
                </div>

                <div class="text-4xl md:text-5xl font-black font-mono tracking-tight">
                    ₱{{ number_format($safeToSpend, 2) }}
                </div>

                <div class="pt-5 border-t border-white/15 flex flex-col sm:flex-row justify-between gap-4 text-xs text-indigo-100/90 font-medium">
                    <div>
                        <span class="block text-indigo-200/60 mb-0.5 uppercase tracking-wider text-[10px] font-bold">Total Cash Left Until Refresh</span>
                        <span class="text-base font-black text-white font-mono">₱{{ number_format($currentBudget->remaining_allowance, 2) }}</span>
                        <span class="text-indigo-200/50 font-bold font-mono"> / ₱{{ number_format($currentBudget->total_allowance, 2) }}</span>
                    </div>
                    <div class="sm:text-right">
                        <span class="block text-indigo-200/60 mb-0.5 uppercase tracking-wider text-[10px] font-bold">Your Regular Allowance Day</span>
                        <span class="text-base font-black text-white tracking-wide">Every {{ $currentBudget->reset_day }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- BACKGROUND CALCULATION LOGIC LAYER -->
        @php
            $startDate = \Carbon\Carbon::parse($currentBudget->cycle_start_date);
            $daysElapsed = max(1, $startDate->diffInDays(\Carbon\Carbon::now()) + 1);
            $totalSpent = \App\Models\Expense::where('user_id', auth()->id())
                ->where('transaction_date', '>=', $currentBudget->cycle_start_date)
                ->sum('amount');
            
            $dailyVelocity = $totalSpent / $daysElapsed;
            $projectedDaysLeft = $dailyVelocity > 0 ? ($currentBudget->remaining_allowance / $dailyVelocity) : 7;
            $isCriticalState = $projectedDaysLeft < (7 - $daysElapsed);
        @endphp

        <!-- SECTION 2: SPENDING FORECAST BANNER -->
        <div class="bg-white rounded-3xl border shadow-sm p-5 transition-all hover:shadow-md flex flex-col lg:flex-row items-start lg:items-center justify-between gap-5 {{ $isCriticalState ? 'border-rose-200 bg-gradient-to-br from-white to-rose-50/20' : 'border-slate-200/70' }}">
            
            <div class="flex items-start gap-4 flex-1 min-w-0">
                <!-- Adaptive Status Icon Ring -->
                <div class="h-11 w-11 shrink-0 rounded-2xl flex items-center justify-center border transition-all shadow-sm {{ $isCriticalState ? 'bg-rose-50 border-rose-100 text-rose-600 animate-pulse' : 'bg-slate-50 border-slate-100 text-slate-600' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"></path>
                    </svg>
                </div>
        
                <!-- Informational Text Segment -->
                <div class="space-y-1 min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="font-bold text-slate-900 text-xs uppercase tracking-widest">Spending Forecast</h4>
                        
                        @if($isCriticalState)
                            <span class="inline-flex items-center gap-1 text-[10px] bg-rose-50 border border-rose-100 text-rose-700 px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider whitespace-nowrap">
                                <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                Deficit Risk
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[10px] bg-emerald-50 border border-emerald-100 text-emerald-700 px-2.5 py-0.5 rounded-full font-black uppercase tracking-wider whitespace-nowrap">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Stable Pacing
                            </span>
                        @endif
                    </div>
        
                    <p class="text-xs text-slate-500 font-medium leading-relaxed max-w-2xl">
                        At your current pace of <span class="font-bold text-slate-800 font-mono">₱{{ number_format($dailyVelocity, 2) }}/day</span>, your remaining allowance is estimated to last for another 
                        <span class="inline-block whitespace-nowrap font-black font-mono text-xs px-2 py-0.5 rounded-md shadow-sm {{ $isCriticalState ? 'bg-rose-100/70 text-rose-700 border border-rose-200' : 'bg-indigo-50 text-indigo-600 border border-indigo-100' }}">
                            {{ round($projectedDaysLeft, 1) }} days
                        </span>.
                    </p>
                </div>
            </div>
        
            <!-- Navigation Action Event Button -->
            <div class="w-full lg:w-auto shrink-0 border-t border-slate-100 pt-4 lg:border-t-0 lg:pt-0">
                <a href="{{ route('student.forecast') }}" class="w-full lg:w-auto inline-flex items-center justify-center gap-2 text-xs font-bold px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm shadow-indigo-600/10 transition-all duration-200 group">
                    <span>View Details</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- SECTION 3: WORKSPACE ACTION TOOL GRID (3-Column Layout Setup) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-stretch">
            
            <!-- TOOL 1: MANUAL TRACKING DECK LINK -->
            <a href="{{ route('student.expenses.create') }}" class="bg-white border border-slate-200/70 rounded-3xl p-5 shadow-sm flex flex-col justify-between group hover:border-slate-300 transition-all duration-300">
                <div class="flex-1 flex flex-col">
                    <div class="border-b border-slate-100 pb-3 mb-4 shrink-0">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-1.5 h-3 bg-indigo-500 rounded-full"></span>
                            Track Manually
                        </h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Quickly record cash transactions manually item by item.</p>
                    </div>
                    <div class="flex-1 flex flex-col items-center justify-center py-4">
                        <div class="h-12 w-12 bg-slate-50 border border-slate-100 text-slate-500 rounded-2xl flex items-center justify-center group-hover:bg-indigo-50 group-hover:border-indigo-100 group-hover:text-indigo-600 shadow-sm transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="w-full mt-4 bg-slate-50 border border-slate-200 text-slate-700 text-[11px] font-bold py-2.5 px-4 rounded-xl group-hover:bg-indigo-600 group-hover:text-white group-hover:border-transparent transition-all duration-200 uppercase tracking-wider flex items-center justify-center gap-1">
                    <span>Log Expense</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </div>
            </a>
        
            <!-- TOOL 2: RECEIPT SCANNER AI LINK -->
            <a href="{{ route('student.receipt-scanner') }}" class="bg-white border border-slate-200/70 rounded-3xl p-5 shadow-sm flex flex-col justify-between group hover:border-slate-300 transition-all duration-300">
                <div class="flex-1 flex flex-col">
                    <div class="border-b border-slate-100 pb-3 mb-4 shrink-0">
                        <h3 class="text-xs font-bold text-slate-900 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-1.5 h-3 bg-emerald-500 rounded-full"></span>
                            Receipt Scanner AI
                        </h3>
                        <p class="text-[11px] text-slate-400 font-medium mt-0.5">Upload a receipt photo to automatically extract costs via AI.</p>
                    </div>
                    <div class="flex-1 flex flex-col items-center justify-center py-4">
                        <div class="h-12 w-12 bg-slate-50 border border-slate-100 text-slate-500 rounded-2xl flex items-center justify-center group-hover:bg-indigo-50 group-hover:border-indigo-100 group-hover:text-indigo-600 shadow-sm transition-all duration-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316A2.192 2.192 0 0014.512 3.75h-5.024c-.53 0-1.03.24-1.353.654l-.822 1.316z"></path>
                                <circle cx="12" cy="13" r="3" stroke-width="2.2"></circle>
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="w-full mt-4 bg-slate-50 border border-slate-200 text-slate-700 text-[11px] font-bold py-2.5 px-4 rounded-xl group-hover:bg-indigo-600 group-hover:text-white group-hover:border-transparent transition-all duration-200 uppercase tracking-wider flex items-center justify-center gap-1">
                    <span>Launch Scanner</span>
                    <svg class="w-3 h-3 transform group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </div>
            </a>

            <!-- TOOL 3: SIMULATION COMPONENT CONTAINER -->
            <div class="flex h-full">
                <livewire:student.simulation-widget />
            </div>
        </div>

        <!-- SECTION 4: DATA INSIGHTS ROW (Secondary Analytic Widgets Block) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
            <div class="flex h-full">
                <livewire:student.savings-widget />
            </div>
            
            <div class="flex h-full">
                <livewire:student.expense-category-widget />
            </div>
        </div>

        <!-- SECTION 5: DATA TABLE MATRIX (Recent Historical Activity Logs) -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200/70 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-widest flex items-center gap-2">
                        <span class="w-1.5 h-3 bg-slate-500 rounded-full"></span>
                        Recent Transactions
                    </h3>
                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Your latest manually logged and parsed OCR expense items.</p>
                </div>
                
                <a href="{{ route('student.expenses.index') }}" class="text-xs font-bold px-3 py-2 bg-slate-50 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200 rounded-xl transition-all whitespace-nowrap">
                    View All Transactions →
                </a>
            </div>
        
            <!-- SESSION STATE ALERT TOASTS -->
            @if (session()->has('success'))
                <div class="mx-6 mt-4 p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    {{ session('success') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="mx-6 mt-4 p-3 bg-rose-50 border border-rose-100 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                    {{ session('error') }}
                </div>
            @endif
        
            <!-- TABLE RENDERING DECISION ARCHITECTURE -->
            @if($recentExpenses->isEmpty())
                <div class="p-12 text-center text-slate-400 max-w-sm mx-auto space-y-2">
                    <div class="h-10 w-10 bg-slate-50 rounded-full flex items-center justify-center mx-auto text-slate-400 border border-slate-200 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"></path>
                        </svg>
                    </div>
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wide">No transactions tracked yet</p>
                    <p class="text-[11px] text-slate-400 leading-normal">Expenses you log manually or via receipt scans this week will display right here.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/70 border-b border-slate-200/60 text-[10px] font-black text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-6">Date</th>
                                <th class="py-3 px-6">Item / Description</th>
                                <th class="py-3 px-6">Vendor</th>
                                <th class="py-3 px-6 text-right">Amount</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs text-slate-600 font-medium">
                            @foreach($recentExpenses as $expense)
                                <tr class="hover:bg-slate-50/30 transition-colors group/row">
                                    <td class="py-3.5 px-6 text-slate-400 whitespace-nowrap font-mono">
                                        {{ \Carbon\Carbon::parse($expense->transaction_date)->format('M d, Y • g:i A') }}
                                    </td>
                                    <td class="py-3.5 px-6 font-bold text-slate-800">
                                        {{ $expense->item_name }}
                                    </td>
                                    <td class="py-3.5 px-6 text-slate-400 font-semibold">
                                        {{ $expense->merchant_name ?? '—' }}
                                    </td>
                                    <td class="py-3.5 px-6 text-right font-black text-rose-600 whitespace-nowrap font-mono text-sm">
                                        -₱{{ number_format($expense->amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('student.expenses.edit', $expense->id) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all duration-150">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                            </a>
                                            <button wire:click="deleteExpense({{ $expense->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all duration-150">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>