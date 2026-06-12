<div class="min-h-screen bg-slate-50 flex flex-col justify-center items-center px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
        
        <!-- Friendly Student Header -->
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Set Up Your Weekly Budget 💰</h1>
            <p class="text-sm text-slate-500 mt-2">
                Tell us your allowance details so we can figure out your safe daily spending pace.
            </p>
        </div>

        <form wire:submit.prevent="initializeEngine" class="space-y-6">
            
            <!-- Input 1: Allowance Amount -->
            <div>
                <label for="total_allowance" class="block text-sm font-semibold text-slate-700 mb-1">
                    How much is your weekly school allowance?
                </label>
                <p class="text-xs text-slate-400 mb-2">
                    Enter the total amount you have available for food, transportation, and school needs each week.
                </p>
                <div class="relative rounded-xl shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="text-slate-400 font-medium sm:text-sm">₱</span>
                    </div>
                    <input id="total_allowance" type="number" step="0.01" placeholder="0.00" wire:model.debounce.500ms="total_allowance"
                        class="block w-full pl-9 pr-4 py-3 border border-slate-300 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition">
                </div>
                @error('total_allowance')
                    <span class="text-xs text-red-600 mt-2 block font-medium">⚠️ {{ $message }}</span>
                @enderror
            </div>

            <!-- Input 2: Allowance Day Selection -->
            <div>
                <label for="reset_day" class="block text-sm font-semibold text-slate-700 mb-1">
                    When does your financial week start?
                </label>
                <p class="text-xs text-slate-400 mb-2">
                    This is your <strong>Allowance Day</strong>. Every week on this morning, your wallet balance will automatically refresh.
                </p>
                <select id="reset_day" wire:model="reset_day"
                    class="block w-full px-4 py-3 border border-slate-300 rounded-xl bg-white text-slate-900 focus:outline-none focus:ring-2 focus:border-indigo-500 focus:ring-indigo-500/20 transition">
                    <option value="Monday">Monday Morning (Class Week Start)</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday (Weekend Prep)</option>
                    <option value="Saturday">Saturday</option>
                    <option value="Sunday">Sunday Night</option>
                </select>
            </div>

            <!-- Simplified Information Box -->
            <div class="bg-indigo-50/50 rounded-xl p-4 border border-indigo-100/50 text-xs text-indigo-800 space-y-1.5">
                <span class="font-bold block text-sm text-indigo-900">🗓️ Your First Week Schedule</span>
                <p>Your monitoring will officially begin starting today.</p>
                <p class="font-medium text-indigo-700/90">
                    This week's timeline: <strong>{{ \Carbon\Carbon::today()->format('M d') }}</strong> to <strong>{{ \Carbon\Carbon::today()->addDays(6)->format('M d, Y') }}</strong>
                </p>
            </div>

            <!-- Action Trigger Button -->
            <button type="submit" wire:loading.attr="disabled"
                class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition">
                <span wire:loading.remove>Start My Smart Tracker</span>
                <span wire:loading>Setting up your dashboard...</span>
            </button>
        </form>
    </div>
</div>