<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Reports</h1>
        <p class="text-sm text-slate-500 mt-1">Generate administrative reports from BudgetWise activity data.</p>
    </div>

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-6">

        {{-- Filters --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Start Date</label>
                <input type="date" wire:model="startDate"
                    class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">End Date</label>
                <input type="date" wire:model="endDate"
                    class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">User</label>
                <select wire:model="selectedUser" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1.5 sm:col-span-3 sm:w-1/3">
                <label class="block text-xs font-bold text-slate-700">Expense Category</label>
                <select wire:model="selectedCategory" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-indigo-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="pt-5 border-t border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Report Preview</h3>
                <p class="text-xs text-slate-400">Live data for the selected filters</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="exportPdf" class="px-4 py-2 text-xs font-bold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50">PDF</button>
                <button wire:click="exportExcel" class="px-4 py-2 text-xs font-bold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50">Excel</button>
                <button wire:click="exportCsv" class="px-4 py-2 text-xs font-bold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50">CSV</button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 bg-slate-50 rounded-2xl">
                <span class="text-xs font-semibold text-slate-500">Total Expenses</span>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($total_expenses) }}</div>
                <p class="text-[11px] text-slate-400 mt-0.5">₱{{ number_format($total_amount, 2) }} recorded</p>
            </div>
            <div class="p-5 bg-slate-50 rounded-2xl">
                <span class="text-xs font-semibold text-slate-500">Budget Alerts</span>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($budget_alerts) }}</div>
                <p class="text-[11px] text-slate-400 mt-0.5">Triggered in range</p>
            </div>
            <div class="p-5 bg-slate-50 rounded-2xl">
                <span class="text-xs font-semibold text-slate-500">OCR Scans</span>
                <div class="text-2xl font-black text-slate-900 mt-1">{{ number_format($ocr_scans) }}</div>
                <p class="text-[11px] text-slate-400 mt-0.5">Processed receipts</p>
            </div>
        </div>
    </div>
</div>