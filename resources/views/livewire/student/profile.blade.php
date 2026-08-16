<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 font-sans bg-slate-50/50">
    <div class="max-w-2xl mx-auto space-y-6">

        <!-- Header -->
        <div class="space-y-1">
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Profile Settings</h1>
            <p class="text-xs sm:text-sm font-medium text-slate-500">
                Update your basic info, email address, and account password.
            </p>
        </div>

        <!-- Success Toast Notification -->
        @if (session()->has('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200/60 rounded-2xl text-emerald-800 text-xs sm:text-sm font-semibold flex items-center gap-2.5 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <!-- Profile Card Header -->
        <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-100 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-indigo-600 text-white font-extrabold text-xl flex items-center justify-center shadow-md shadow-indigo-600/20 shrink-0">
                {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
            </div>
            <div class="space-y-0.5 min-w-0">
                <h3 class="text-base font-bold text-slate-900 truncate">
                    {{ auth()->user()->name ?? 'Student User' }}
                </h3>
                <p class="text-xs font-medium text-slate-500 truncate">
                    {{ auth()->user()->email ?? 'student@example.com' }}
                </p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 mt-1">
                    Student Account
                </span>
            </div>
        </div>

        <!-- Profile Form Card -->
        <form wire:submit.prevent="updateProfile" class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-6">
            
            <!-- Section 1: Personal Details -->
            <div class="space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Personal Details</h2>
                </div>
                
                <!-- Display Name -->
                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-bold text-slate-700">
                        Display name
                    </label>
                    <input id="name" type="text" wire:model.defer="name" placeholder="John Doe"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                    @error('name')
                        <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- Email Address -->
                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-bold text-slate-700">
                        Email address
                    </label>
                    <input id="email" type="email" wire:model.defer="email" placeholder="student@example.com"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                    @error('email')
                        <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <!-- Section 2: Security & Password -->
            <div class="space-y-4 pt-2">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Change Password</h2>
                </div>

                <!-- Current Password -->
                <div class="space-y-1.5">
                    <label for="current_password" class="block text-xs font-bold text-slate-700">
                        Current password
                    </label>
                    <input id="current_password" type="password" wire:model.defer="current_password" autocomplete="current-password" placeholder="••••••••"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                    @error('current_password')
                        <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- New Password & Confirm New Password -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="new_password" class="block text-xs font-bold text-slate-700">
                            New password
                        </label>
                        <input id="new_password" type="password" wire:model.defer="new_password" autocomplete="new-password" placeholder="8+ characters"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                        @error('new_password')
                            <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="new_password_confirmation" class="block text-xs font-bold text-slate-700">
                            Confirm new password
                        </label>
                        <input id="new_password_confirmation" type="password" wire:model.defer="new_password_confirmation" autocomplete="new-password" placeholder="Repeat new password"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                    </div>
                </div>
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}"
                   class="px-5 py-3 rounded-2xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all">
                   Cancel
                </a>

                <button type="submit" wire:loading.attr="disabled"
                    class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white rounded-2xl font-bold text-xs shadow-lg shadow-indigo-600/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <span wire:loading.remove wire:target="updateProfile">Save Changes</span>
                    <span wire:loading wire:target="updateProfile" class="inline-flex items-center gap-2">
                        <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Saving...</span>
                    </span>
                </button>
            </div>
        </form>

    </div>
</div>