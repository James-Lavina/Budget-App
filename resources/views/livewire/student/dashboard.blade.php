<div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">

        <div class="flex justify-between items-center bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Welcome back, {{ auth()->user()->name }}!</h1>
                <p class="text-xs text-slate-400 mt-0.5">Role Domain: Student Tracking Workspace</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                Active Cycle
            </span>
        </div>

        <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 rounded-3xl p-8 text-white shadow-lg shadow-indigo-600/10 relative overflow-hidden">
            <div class="relative z-10 space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-indigo-100/80 uppercase tracking-wider">
                        Today's Absolute Limit (Safe-to-Spend)
                    </span>
                    <span class="text-xs bg-white/10 px-3 py-1 rounded-full border border-white/10 backdrop-blur-sm">
                        {{ $daysRemaining }} Days Left in Cycle
                    </span>
                </div>

                <div class="text-5xl font-black tracking-tight">
                    ₱{{ number_format($safeToSpend, 2) }}
                </div>

                <div class="pt-4 border-t border-white/10 flex justify-between text-xs text-indigo-100/90 font-medium">
                    <div>
                        <span class="block text-indigo-200/60 mb-0.5">Remaining Wallet Capacity</span>
                        <span class="text-sm font-bold text-white">₱{{ number_format($currentBudget->remaining_allowance, 2) }}</span>
                        <span class="text-indigo-200/50"> / ₱{{ number_format($currentBudget->total_allowance, 2) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="block text-indigo-200/60 mb-0.5">Cycle Configuration Reset</span>
                        <span class="text-sm font-bold text-white">Every {{ $currentBudget->reset_day }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center text-slate-400 text-sm">
                Next Step: <span class="font-bold text-indigo-600">Manual Expense Log</span> (Process 2.2)
            </div>
            <div class="border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center text-slate-400 text-sm">
                Next Step: <span class="font-bold text-indigo-600">OCR Scan Upload Panel</span> (Process 2.3)
            </div>
        </div>

    </div>
</div>