<div class="min-h-screen bg-slate-50/50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Main Ledger Card Container -->
        <div class="bg-white rounded-2xl shadow-[0_4px_12px_-4px_rgba(0,0,0,0.05)] border border-slate-200/80 overflow-hidden">
            
            <!-- Ledger Header with Integrated Breadcrumb -->
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <!-- Tiny Professional Breadcrumb Track -->
                    <nav class="flex items-center gap-1.5 text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">
                        <a href="{{ route('student.dashboard') }}" class="hover:text-indigo-600 transition-colors">Dashboard</a>
                        <svg class="w-2.5 h-2.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                        </svg>
                        <span class="text-slate-500">Ledger Records</span>
                    </nav>

                    <h1 class="text-xl font-black text-slate-900 tracking-tight sm:text-2xl">Transaction Records</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Complete historical breakdown of your tracked budget items.</p>
                </div>
                <div class="self-start sm:self-center">
                    <span class="inline-flex items-center text-xs font-bold px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100/80 rounded-full tracking-wide">
                        Total: {{ $allExpenses->total() }} Entries
                    </span>
                </div>
            </div>

            <!-- Modern Flash Notifications Alerts -->
            @if (session()->has('success'))
                <div class="mx-6 mt-4 p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ session('success') }}
                </div>
            @endif

            @if($allExpenses->isEmpty())
                <!-- Minimal Empty State Panel -->
                <div class="p-16 text-center max-w-sm mx-auto space-y-3">
                    <div class="h-12 w-12 bg-slate-50 rounded-full flex items-center justify-center mx-auto text-slate-400 border border-slate-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-700">Your ledger is empty</h3>
                    <p class="text-xs text-slate-400 leading-normal">
                        There are no logged records recorded in this system database profile yet. 
                    </p>
                </div>
            @else
                <!-- Responsive Detailed Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-200 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-6">Date & Time</th>
                                <th class="py-3 px-6">Item / Description</th>
                                <th class="py-3 px-6">Vendor</th>
                                <th class="py-3 px-6 text-right">Amount</th>
                                <th class="py-3 px-6 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm text-slate-600">
                            @foreach($allExpenses as $expense)
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
                                            <a href="{{ route('student.expenses.edit', $expense->id) }}" 
                                               class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>

                                            <button 
                                                wire:click="deleteExpense({{ $expense->id }})"
                                                onclick="confirm('Are you sure you want to remove this transaction? This will instantly adjust your spending limits.') || event.stopImmediatePropagation()"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
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

                <!-- Pagination Nav System Box -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $allExpenses->links() }}
                </div>
                
            @endif
        </div>
    </div>
</div>