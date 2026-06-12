<div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Header Panel -->
        <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Welcome back, {{ auth()->user()->name }}!</h1>
                <p class="text-xs text-slate-400 mt-0.5">Workspace: Student Budget Tracker</p>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    Active Week
                </span>
                
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Scorecard Banner -->
        <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 rounded-3xl p-8 text-white shadow-lg shadow-indigo-600/10 relative overflow-hidden">
            <div class="relative z-10 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-indigo-100/80 uppercase tracking-wider">
                        Your Safe-to-Spend Money Today
                    </span>
                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/10 backdrop-blur-sm">
                        {{ $daysRemaining }} {{ $daysRemaining == 1 ? 'Day' : 'Days' }} Left This Week
                    </span>
                </div>

                <div class="text-5xl font-black tracking-tight">
                    ₱{{ number_format($safeToSpend, 2) }}
                </div>

                <div class="pt-4 border-t border-white/10 flex justify-between text-xs text-indigo-100/90 font-medium">
                    <div>
                        <span class="block text-indigo-200/60 mb-0.5">Total Cash Left Until Refresh</span>
                        <span class="text-sm font-bold text-white">₱{{ number_format($currentBudget->remaining_allowance, 2) }}</span>
                        <span class="text-indigo-200/50"> / ₱{{ number_format($currentBudget->total_allowance, 2) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-indigo-200/60 mb-0.5">Your Regular Allowance Day</span>
                        <span class="text-sm font-bold text-white">Every {{ $currentBudget->reset_day }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Practical Action Hub Selection -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a href="{{ route('student.expenses.create') }}" class="bg-white hover:bg-slate-50 border border-slate-200 rounded-2xl p-6 text-center shadow-sm flex flex-col justify-center items-center group transition">
                <span class="text-2xl mb-1">📝</span>
                <span class="font-bold text-slate-900 group-hover:text-indigo-600 transition text-sm">Track Expense Manually</span>
                <span class="text-xs text-slate-400 mt-0.5">Quickly type in cash transactions item by item</span>
            </a>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center shadow-sm flex flex-col justify-center items-center relative overflow-hidden group">
                <span class="text-2xl mb-1">📸</span>
                <span class="font-bold text-slate-400 text-sm">AI Receipt Scanner</span>
                <span class="text-xs text-indigo-600 font-semibold bg-indigo-50 px-2.5 py-1 rounded-lg mt-1 border border-indigo-100">
                    Ready for Module Build (Process 2.3)
                </span>
            </div>
        </div>

        <!-- Transactions Ledger Container -->
        <div class="mt-8 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Recent Transactions</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Your latest manually logged and scanned expenses.</p>
                </div>
                
                <a href="{{ route('student.expenses.index') }}" class="text-xs font-bold px-3 py-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200 rounded-xl transition">
                    View All Transactions →
                </a>
            </div>
        
            @if (session()->has('success'))
                <div class="mx-6 mt-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium flex items-center gap-2">
                    ✅ {{ session('success') }}
                </div>
            @endif
            @if (session()->has('error'))
                <div class="mx-6 mt-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-medium flex items-center gap-2">
                    ⚠️ {{ session('error') }}
                </div>
            @endif
        
            @if($recentExpenses->isEmpty())
                <div class="p-8 text-center text-slate-400">
                    <span class="text-3xl block mb-2">💸</span>
                    <p class="text-sm font-medium">No transactions tracked yet for this week.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3 px-6">Date</th>
                                <th class="py-3 px-6">Item / Description</th>
                                <th class="py-3 px-6">Vendor</th>
                                <th class="py-3 px-6 text-right">Amount</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @foreach($recentExpenses as $expense)
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3.5 px-6 text-slate-500 whitespace-nowrap">
                                        {{ \Carbon\Carbon::parse($expense->transaction_date)->format('M d, Y • g:i A') }}
                                    </td>
                                    <td class="py-3.5 px-6 font-medium text-slate-900">
                                        {{ $expense->item_name }}
                                    </td>
                                    <td class="py-3.5 px-6 text-slate-500">
                                        {{ $expense->merchant_name ?? '—' }}
                                    </td>
                                    <td class="py-3.5 px-6 text-right font-bold text-rose-600 whitespace-nowrap">
                                        -₱{{ number_format($expense->amount, 2) }}
                                    </td>
                                    <td class="py-3.5 px-6 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            
                                            <a href="{{ route('student.expenses.edit', $expense->id) }}" 
                                               class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition"
                                               title="Edit Record">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                    
                                            <button 
                                                wire:click="deleteExpense({{ $expense->id }})"
                                                onclick="confirm('Are you sure you want to remove this transaction? This will instantly adjust your spending limits.') || event.stopImmediatePropagation()"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition"
                                                title="Delete Record">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
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