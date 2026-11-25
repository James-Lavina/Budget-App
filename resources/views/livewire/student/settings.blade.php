<div class="min-h-screen py-6 sm:py-10 px-3.5 sm:px-6 lg:px-8 font-sans antialiased">
    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Budget Settings</h1>
            <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1">
                Choose when your budget week resets and fix this week's allowance if needed.
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

        <form wire:submit.prevent="updateSettings" class="bg-white rounded-3xl p-5 sm:p-8 shadow-sm border border-slate-100 space-y-6">

            <!-- Reset Day -->
            <div class="space-y-1.5">
                <label for="reset_day" class="block text-xs font-bold text-slate-700">Weekly Reset Day</label>
                <select id="reset_day" wire:model.defer="reset_day"
                    class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm focus:bg-white focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] transition-all">
                    @foreach(\App\Http\Livewire\Student\Settings::DAYS as $day)
                        <option value="{{ $day }}">{{ $day }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] font-medium text-slate-500 leading-normal">
                    Pick the day your allowance usually arrives. A new reset day takes effect at your next weekly reset,
                    and that first week runs until the next {{ $reset_day }}.
                </p>
                @error('reset_day')
                    <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex flex-col-reverse sm:flex-row items-center sm:justify-end gap-2.5 sm:gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}"
                   class="w-full sm:w-auto text-center px-5 py-2.5 rounded-full text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all">
                   Cancel
                </a>

                <button type="submit" wire:loading.attr="disabled"
                    class="w-full sm:w-auto justify-center px-8 py-3 bg-[var(--brand)] hover:opacity-90 text-white rounded-full font-bold text-xs shadow-lg shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.3)] transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="updateSettings">Save Changes</span>
                    <span wire:loading wire:target="updateSettings">Saving...</span>
                </button>
            </div>
        </form>

    </div>
</div>