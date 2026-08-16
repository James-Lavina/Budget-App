<div class="min-h-screen py-6 sm:py-10 px-3.5 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Budget Settings</h1>
            <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1">
                Set your allowance and pick when your budget week resets.
            </p>
        </div>

        <!-- Success Toast Notification -->
        @if (session()->has('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Settings Form Card -->
        <form wire:submit.prevent="updateSettings" class="bg-white rounded-3xl p-5 sm:p-8 shadow-sm border border-slate-100 space-y-6">

            <!-- Row 1: Weekly Allowance & Reset Day -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                <!-- Weekly Allowance Input -->
                <div class="space-y-1.5">
                    <label for="total_allowance" class="block text-xs font-bold text-slate-700">
                        Weekly Allowance
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 font-bold text-base">
                            ₱
                        </span>
                        <input id="total_allowance" type="number" step="0.01" wire:model.defer="total_allowance" placeholder="0.00"
                            class="w-full pl-9 pr-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-extrabold text-base placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    @error('total_allowance')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Reset Day Select -->
                <div class="space-y-1.5">
                    <label for="reset_day" class="block text-xs font-bold text-slate-700">
                        Weekly Reset Day
                    </label>
                    <select id="reset_day" wire:model.defer="reset_day"
                        class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                    @error('reset_day')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Calculation Behavior Info Box -->
            <div class="p-4 sm:p-5 bg-indigo-50/50 rounded-2xl border border-indigo-100/60 space-y-3">
                <div class="text-xs text-slate-600 leading-relaxed">
                    <span class="font-extrabold text-indigo-950 block mb-0.5">When should changes apply?</span>
                    By default, your new allowance starts on your next reset day.
                </div>

                <label class="flex items-start gap-3 p-3 bg-white rounded-xl border border-slate-100 cursor-pointer select-none hover:border-indigo-200 transition-all">
                    <input type="checkbox" wire:model="update_current_week"
                        class="h-4 w-4 mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 transition-colors shrink-0">
                    <div class="space-y-0.5">
                        <span class="text-xs font-bold text-slate-800 block">
                            Update my current week's budget right now
                        </span>
                        <span class="text-[11px] font-medium text-slate-500 block leading-normal">
                            This adjusts your remaining balance for this week immediately instead of waiting for your next reset day.
                        </span>
                    </div>
                </label>
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex flex-col-reverse sm:flex-row items-center sm:justify-end gap-2.5 sm:gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}"
                   class="w-full sm:w-auto text-center px-5 py-2.5 rounded-full text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all">
                   Cancel
                </a>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full sm:w-auto justify-center px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-bold text-xs shadow-lg shadow-indigo-200 transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="updateSettings">Save Changes</span>
                    <span wire:loading wire:target="updateSettings">Saving...</span>
                </button>
            </div>
        </form>

    </div>
</div>