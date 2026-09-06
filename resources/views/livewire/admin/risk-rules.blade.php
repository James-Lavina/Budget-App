<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Risk Detection Rules</h1>
        <p class="text-sm text-slate-500 mt-1">Configure thresholds used to identify potentially risky spending behavior.</p>
    </div>

    @if (session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-4">

        {{-- Overspending Threshold --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-900">Overspending Threshold (%)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Trigger when weekly spending exceeds threshold percent of allowance.</p>
                </div>
                <label class="inline-flex items-center gap-2 shrink-0">
                    <input type="checkbox" wire:model="overspending_enabled" class="h-4 w-4 rounded text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
                    <span class="text-xs font-bold text-slate-600">Enable</span>
                </label>
            </div>
            <div class="flex items-center gap-4 mt-4">
                <input type="range" min="1" max="100" wire:model.defer="overspending_threshold" class="flex-1 accent-[var(--brand-primary)]">
                <input type="number" wire:model.defer="overspending_threshold" class="w-20 px-2 py-1.5 border border-slate-200 rounded-lg text-sm font-bold text-[var(--brand-primary)]">
            </div>
            @error('overspending_threshold') <span class="text-[11px] text-rose-600 font-semibold">{{ $message }}</span> @enderror
        </div>

        {{-- Daily Safe-to-Spend Warning --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-900">Daily Safe-to-Spend Warning</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Warn when daily remaining budget drops below this percent.</p>
                </div>
                <label class="inline-flex items-center gap-2 shrink-0">
                    <input type="checkbox" wire:model="daily_safe_to_spend_enabled" class="h-4 w-4 rounded text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
                    <span class="text-xs font-bold text-slate-600">Enable</span>
                </label>
            </div>
            <div class="flex items-center gap-4 mt-4">
                <input type="range" min="1" max="100" wire:model.defer="daily_safe_to_spend_threshold" class="flex-1 accent-[var(--brand-primary)]">
                <input type="number" wire:model.defer="daily_safe_to_spend_threshold" class="w-20 px-2 py-1.5 border border-slate-200 rounded-lg text-sm font-bold text-[var(--brand-primary)]">
            </div>
        </div>

        {{-- Rapid Spending Detection --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-900">Rapid Spending Detection</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Detect rapid successive large transactions.</p>
                </div>
                <label class="inline-flex items-center gap-2 shrink-0">
                    <input type="checkbox" wire:model="rapid_spending_enabled" class="h-4 w-4 rounded text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
                    <span class="text-xs font-bold text-slate-600">Enable</span>
                </label>
            </div>
            <div class="flex items-center gap-4 mt-4">
                <input type="range" min="2" max="20" wire:model.defer="rapid_spending_count" class="flex-1 accent-[var(--brand-primary)]">
                <input type="number" wire:model.defer="rapid_spending_count" class="w-20 px-2 py-1.5 border border-slate-200 rounded-lg text-sm font-bold text-[var(--brand-primary)]">
            </div>
        </div>

        {{-- Consecutive No Expense Logs --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-900">Consecutive No Expense Logs</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Alert if user records no expenses for consecutive days.</p>
                </div>
                <label class="inline-flex items-center gap-2 shrink-0">
                    <input type="checkbox" wire:model="no_expense_logs_enabled" class="h-4 w-4 rounded text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
                    <span class="text-xs font-bold text-slate-600">Enable</span>
                </label>
            </div>
            <div class="flex items-center gap-4 mt-4">
                <input type="range" min="1" max="60" wire:model.defer="no_expense_logs_days" class="flex-1 accent-[var(--brand-primary)]">
                <input type="number" wire:model.defer="no_expense_logs_days" class="w-20 px-2 py-1.5 border border-slate-200 rounded-lg text-sm font-bold text-[var(--brand-primary)]">
            </div>
        </div>

        {{-- Low Remaining Budget Alert --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-bold text-slate-900">Low Remaining Budget Alert</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Low remaining budget threshold.</p>
                </div>
                <label class="inline-flex items-center gap-2 shrink-0">
                    <input type="checkbox" wire:model="low_remaining_budget_enabled" class="h-4 w-4 rounded text-[var(--brand-primary)] focus:ring-[var(--brand-primary)]">
                    <span class="text-xs font-bold text-slate-600">Enable</span>
                </label>
            </div>
            <div class="flex items-center gap-4 mt-4">
                <input type="range" min="1" max="100" wire:model.defer="low_remaining_budget_threshold" class="flex-1 accent-[var(--brand-primary)]">
                <input type="number" wire:model.defer="low_remaining_budget_threshold" class="w-20 px-2 py-1.5 border border-slate-200 rounded-lg text-sm font-bold text-[var(--brand-primary)]">
            </div>
        </div>

        <button type="submit" class="px-6 py-3 bg-[var(--brand-primary)] hover:opacity-90 text-white rounded-2xl font-bold text-sm shadow-md transition-all">
            Save Rules
        </button>
    </form>
</div>