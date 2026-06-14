<div class="min-h-screen bg-slate-50/50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Main Scorecard Banner -->
        <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 rounded-2xl p-8 text-white shadow-[0_10px_25px_-5px_rgba(79,70,229,0.15)] relative overflow-hidden">
            <div class="relative z-10 space-y-5">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-indigo-100/80 uppercase tracking-widest">
                        Your Safe-to-Spend Money Today
                    </span>
                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/10 backdrop-blur-sm font-semibold tracking-wide">
                        {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'Day' : 'Days' }} Left This Week
                    </span>
                </div>

                <div class="text-5xl font-black tracking-tight">
                    ₱{{ number_format($safeToSpend, 2) }}
                </div>

                <div class="pt-5 border-t border-white/10 flex flex-col sm:flex-row justify-between gap-4 text-xs text-indigo-100/90 font-medium">
                    <div>
                        <span class="block text-indigo-200/60 mb-1 uppercase tracking-wider text-[10px] font-bold">Total Cash Left Until Refresh</span>
                        <span class="text-base font-black text-white">₱{{ number_format($currentBudget->remaining_allowance, 2) }}</span>
                        <span class="text-indigo-200/50 font-semibold"> / ₱{{ number_format($currentBudget->total_allowance, 2) }}</span>
                    </div>
                    <div class="sm:text-right">
                        <span class="block text-indigo-200/60 mb-1 uppercase tracking-wider text-[10px] font-bold">Your Regular Allowance Day</span>
                        <span class="text-base font-black text-white">Every {{ $currentBudget->reset_day }}</span>
                    </div>
                </div>
            </div>
            <!-- Decorative subtle background glow -->
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        </div>

        <!-- EMBEDDED COMPONENT SYSTEM: Pacing Velocity Forecast Preview Summary Widget -->
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

        <div class="bg-white border border-slate-200/80 rounded-2xl p-6 shadow-[0_2px_8px_-3px_rgba(0,0,0,0.05)] transition-all hover:shadow-md flex flex-col md:flex-row items-start md:items-center justify-between gap-5">
            <div class="flex items-start gap-4 flex-1">
                <div class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center border transition-colors {{ $isCriticalState ? 'bg-rose-50 border-rose-100 text-rose-600' : 'bg-slate-50 border-slate-200 text-slate-600' }}">
                    <!-- Modern SVG Line Graph Icon -->
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 12l3-3 3 3 4-4M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="space-y-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h4 class="font-extrabold text-slate-900 text-sm tracking-tight">Spending Forecast</h4>
                        @if($isCriticalState)
                            <span class="inline-flex items-center gap-1 text-[10px] bg-rose-50 text-rose-700 px-2 py-0.5 rounded-full font-bold border border-rose-100 uppercase tracking-wider">
                                <span class="h-1 w-1 rounded-full bg-rose-500 animate-pulse"></span>
                                Deficit Risk
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-full font-bold border border-emerald-100 uppercase tracking-wider">
                                <span class="h-1 w-1 rounded-full bg-emerald-500"></span>
                                Stable Pacing
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed max-w-2xl">
                        At your current velocity of <span class="font-bold text-slate-800">₱{{ number_format($dailyVelocity, 2) }}/day</span>, your remaining allowance is mathematically projected to last another <span class="font-black text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">{{ round($projectedDaysLeft, 1) }} days</span>.
                    </p>
                </div>
            </div>
            <div class="w-full md:w-auto shrink-0">
                <a href="{{ route('student.forecast') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-1.5 text-xs font-bold px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl shadow-sm shadow-indigo-600/10 transition-all whitespace-nowrap group">
                    <span>View Full Insights</span>
                    <svg class="w-3.5 h-3.5 transform group-hover:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Practical Action Hub Selection -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('student.expenses.create') }}" class="bg-white hover:bg-slate-50/80 border border-slate-200/80 rounded-2xl p-6 text-center shadow-[0_2px_8px_-3px_rgba(0,0,0,0.04)] flex flex-col justify-center items-center group transition-all hover:shadow-sm">
                <div class="h-10 w-10 bg-slate-50 border border-slate-200 text-slate-500 rounded-xl flex items-center justify-center mb-3 group-hover:bg-indigo-50 group-hover:border-indigo-100 group-hover:text-indigo-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                </div>
                <span class="font-extrabold text-slate-900 group-hover:text-indigo-600 transition text-sm tracking-tight">Track Manually</span>
                <span class="text-[11px] text-slate-400 mt-1 leading-normal">Quickly log cash transactions item by item</span>
            </a>
        
            <a href="{{ route('student.receipt-scanner') }}" class="bg-white hover:bg-slate-50/80 border border-slate-200/80 rounded-2xl p-6 text-center shadow-[0_2px_8px_-3px_rgba(0,0,0,0.04)] flex flex-col justify-center items-center group transition-all hover:shadow-sm">
                <div class="h-10 w-10 bg-slate-50 border border-slate-200 text-slate-500 rounded-xl flex items-center justify-center mb-3 group-hover:bg-indigo-50 group-hover:border-indigo-100 group-hover:text-indigo-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V3z"></path>
                        <circle cx="12" cy="13" r="3" stroke-width="2"></circle>
                    </svg>
                </div>
                <span class="font-extrabold text-slate-900 group-hover:text-indigo-600 transition text-sm tracking-tight">Receipt Scanner AI</span>
                <span class="text-[11px] text-slate-400 mt-1 leading-normal">Snap a photo to automatically parse total costs</span>
            </a>

            <div>
                <livewire:student.simulation-widget />
            </div>
        </div>

        <livewire:student.savings-widget />

        <!-- Transactions Ledger Container -->
        <div class="bg-white rounded-2xl shadow-[0_4px_12px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-base font-black text-slate-900 tracking-tight">Recent Transactions</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Your latest manually logged and scanned expenses.</p>
                </div>
                
                <a href="{{ route('student.expenses.index') }}" class="text-xs font-bold px-3 py-1.5 bg-slate-50 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200 rounded-xl transition-colors whitespace-nowrap">
                    View All Transactions →
                </a>
            </div>
        
            @if (session()->has('success'))
                <div class="mx-6 mt-4 p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ session('success') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="mx-6 mt-4 p-3 bg-rose-50 border border-rose-100 text-rose-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                    {{ session('error') }}
                </div>
            @endif
        
            @if($recentExpenses->isEmpty())
                <div class="p-12 text-center text-slate-400 max-w-sm mx-auto space-y-2">
                    <div class="h-10 w-10 bg-slate-50 rounded-full flex items-center justify-center mx-auto text-slate-400 border border-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-600">No transactions tracked yet</p>
                    <p class="text-xs text-slate-400 leading-normal">Expenses you log manually or via receipt scans this week will display right here.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200/60 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-6">Date</th>
                                <th class="py-3 px-6">Item / Description</th>
                                <th class="py-3 px-6">Vendor</th>
                                <th class="py-3 px-6 text-right">Amount</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                            @foreach($recentExpenses as $expense)
                                <tr class="hover:bg-slate-50/40 transition-colors">
                                    <td class="py-3.5 px-6 text-slate-400 whitespace-nowrap text-xs">
                                        {{ \Carbon\Carbon::parse($expense->transaction_date)->format('M d, Y • g:i A') }}
                                    </td>
                                    <td class="py-3.5 px-6 font-semibold text-slate-800">
                                        {{ $expense->item_name }}
                                    </td>
                                    <td class="py-3.5 px-6 text-slate-400">
                                        {{ $expense->merchant_name ?? '—' }}
                                    </td>
                                    <td class="py-3.5 px-6 text-right font-black text-rose-600 whitespace-nowrap">
                                        -₱{{ number_format($expense->amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('student.expenses.edit', $expense->id) }}" class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </a>
                                            <button wire:click="deleteExpense({{ $expense->id }})" onclick="confirm('Are you sure?') || event.stopImmediatePropagation()" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
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