<div class="min-h-screen py-10 px-4 sm:px-6 lg:px-8 font-sans">
    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Header -->
        <div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">Profile Settings</h1>
            <p class="text-xs font-medium text-slate-500 mt-1">
                Manage your personal credentials and account security settings.
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

        <!-- Profile Form Card -->
        <form wire:submit.prevent="updateProfile" class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">
            
            <!-- Section 1: Account Information -->
            <div class="space-y-4">
                <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Personal Details</h2>
                
                <!-- Display Name -->
                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-bold text-slate-700">
                        Display Name
                    </label>
                    <input id="name" type="text" wire:model.defer="name" placeholder="John Doe"
                        class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('name')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email & Confirm Email -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-bold text-slate-700">
                            Email Address
                        </label>
                        <input id="email" type="email" wire:model.defer="email" placeholder="student@example.com"
                            class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('email')
                            <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="email_confirmation" class="block text-xs font-bold text-slate-700">
                            Confirm Email Address
                        </label>
                        <input id="email_confirmation" type="email" wire:model.defer="email_confirmation" placeholder="Confirm email address"
                            class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="pt-2 border-t border-slate-100"></div>

            <!-- Section 2: Password / Security -->
            <div class="space-y-4">
                <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Security & Password</h2>

                <!-- Current Password -->
                <div class="space-y-1.5">
                    <label for="current_password" class="block text-xs font-bold text-slate-700">
                        Current Password
                    </label>
                    <input id="current_password" type="password" wire:model.defer="current_password" autocomplete="current-password" placeholder="••••••••"
                        class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    @error('current_password')
                        <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- New Password & Confirm New Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="new_password" class="block text-xs font-bold text-slate-700">
                            New Password
                        </label>
                        <input id="new_password" type="password" wire:model.defer="new_password" autocomplete="new-password" placeholder="Minimum 8 characters"
                            class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('new_password')
                            <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="new_password_confirmation" class="block text-xs font-bold text-slate-700">
                            Confirm New Password
                        </label>
                        <input id="new_password_confirmation" type="password" wire:model.defer="new_password_confirmation" autocomplete="new-password" placeholder="Match new password"
                            class="w-full px-4 py-3 bg-slate-100/80 border-0 rounded-2xl text-slate-900 font-semibold text-sm placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}"
                   class="px-5 py-2.5 rounded-full text-xs font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-all">
                   Cancel
                </a>

                <button type="submit" wire:loading.attr="disabled"
                    class="px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full font-bold text-xs shadow-lg shadow-indigo-200 transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                    <span wire:loading.remove wire:target="updateProfile">Update Profile</span>
                    <span wire:loading wire:target="updateProfile">Saving...</span>
                </button>
            </div>
        </form>

    </div>
</div>