<div class="min-h-screen bg-slate-50 py-10 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">
        
        <div class="flex justify-between items-center">
            <a href="{{ route('student.dashboard') }}" class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-500 transition gap-1">
                ← Back to Dashboard
            </a>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Transaction Records</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Complete historical breakdown of your tracked budget items.</p>
                </div>
                <span class="text-xs font-semibold px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-full">
                    Total: {{ $allExpenses->total() }} Entries
                </span>
            </div>

            @if (session()->has('success'))
                <div class="mx-6 mt-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs font-medium">
                    ✅ {{ session('success') }}
                </div>
            @endif

            @if($allExpenses->isEmpty())
                <div class="p-12 text-center text-slate-400">
                    <span class="text-4xl block mb-2">📒</span>
                    <p class="text-sm font-medium">Your ledger is completely empty.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                                <th class="py-3 px-6">Date & Time</th>
                                <th class="py-3 px-6">Item / Description</th>
                                <th class="py-3 px-6">Vendor</th>
                                <th class="py-3 px-6 text-right">Amount</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                            @foreach($allExpenses as $expense)
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
                                               class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>

                                            <button 
                                                wire:click="deleteExpense({{ $expense->id }})"
                                                onclick="confirm('Are you sure you want to remove this transaction? This will instantly adjust your spending limits.') || event.stopImmediatePropagation()"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
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

                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $allExpenses->links() }}
                </div>
                
            @endif
        </div>
    </div>
</div>