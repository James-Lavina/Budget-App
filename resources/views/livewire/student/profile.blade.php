<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8 font-sans">
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
            <div class="w-14 h-14 rounded-2xl bg-[var(--brand)] text-white font-extrabold text-xl flex items-center justify-center shadow-md shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.3)] shrink-0">
                {{ strtoupper(substr(auth()->user()->name ?? 'S', 0, 1)) }}
            </div>
            <div class="space-y-0.5 min-w-0">
                <h3 class="text-base font-bold text-slate-900 truncate">
                    {{ auth()->user()->name ?? 'Student User' }}
                </h3>
                <p class="text-xs font-medium text-slate-500 truncate">
                    {{ auth()->user()->email ?? 'student@example.com' }}
                </p>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-[rgba(var(--brand-rgb),0.08)] text-[var(--brand)] mt-1">
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

                <div class="space-y-1.5">
                    <label for="name" class="block text-xs font-bold text-slate-700">Display name</label>
                    <input id="name" type="text" wire:model.defer="name" placeholder="John Doe"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                    @error('name')
                        <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="space-y-1.5">
                    <label for="email" class="block text-xs font-bold text-slate-700">Email address</label>
                    <input id="email" type="email" wire:model.defer="email" placeholder="student@example.com"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                    @error('email')
                        <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <!-- School -->
            <div class="space-y-1.5">
                <label for="school" class="block text-xs font-bold text-slate-700">
                    School <span class="font-normal text-slate-400">(optional)</span>
                </label>
                <input id="school" type="text" wire:model.defer="school" placeholder="e.g., Saint Joseph College"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                @error('school')
                    <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                    </span>
                @enderror
            </div>

            <!-- Section 2: Security & Password -->
            <div class="space-y-4 pt-2">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Change Password</h2>
                </div>

                <div class="space-y-1.5">
                    <label for="current_password" class="block text-xs font-bold text-slate-700">Current password</label>
                    <input id="current_password" type="password" wire:model.defer="current_password" autocomplete="current-password" placeholder="••••••••"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                    @error('current_password')
                        <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="new_password" class="block text-xs font-bold text-slate-700">New password</label>
                        <input id="new_password" type="password" wire:model.defer="new_password" autocomplete="new-password" placeholder="8+ characters"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                        @error('new_password')
                            <span class="text-xs font-medium text-rose-600 flex items-center gap-1 mt-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 inline-block shrink-0"></span> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label for="new_password_confirmation" class="block text-xs font-bold text-slate-700">Confirm new password</label>
                        <input id="new_password_confirmation" type="password" wire:model.defer="new_password_confirmation" autocomplete="new-password" placeholder="Repeat new password"
                            class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-medium text-sm placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[rgba(var(--brand-rgb),0.2)] focus:border-[var(--brand)] transition-all">
                    </div>
                </div>
            </div>

            <!-- Section 3: Notifications -->
            <div class="space-y-4 pt-2">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Notifications</h2>
                </div>

                <label class="flex items-center justify-between gap-4 cursor-pointer">
                    <div>
                        <span class="text-sm font-bold text-slate-800 block">Email alerts</span>
                        <span class="text-xs text-slate-400 font-medium">Budget alerts and weekly summaries by email. In-app notifications always stay on.</span>
                    </div>
                    <input type="checkbox" wire:model.defer="email_notifications_enabled" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-[var(--brand)] transition-colors shrink-0
                        after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
                </label>

                @unless(\App\Models\AppSetting::current()->email_notifications_enabled)
                    <p class="text-[11px] text-amber-600 font-semibold">Email is currently disabled system-wide by an administrator.</p>
                @endunless
            </div>

            <!-- Footer Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('student.dashboard') }}"
                   class="px-5 py-3 rounded-2xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition-all">
                   Cancel
                </a>

                <button type="submit" wire:loading.attr="disabled"
                    class="px-6 py-3 bg-[var(--brand)] hover:opacity-90 active:scale-[0.99] text-white rounded-2xl font-bold text-xs shadow-lg shadow-[0_8px_16px_-4px_rgba(var(--brand-rgb),0.3)] transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
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

        {{-- Your data --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-100 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Your Data</h2>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <p class="text-xs font-medium text-slate-500 leading-relaxed">
                    Download a copy of your profile, budgets, expenses, goals, alerts and activity history as a JSON file.
                </p>
                <button type="button" wire:click="exportData" wire:loading.attr="disabled" wire:target="exportData"
                    class="px-5 py-2.5 rounded-2xl text-xs font-bold text-slate-700 border border-slate-200 hover:bg-slate-50 transition-all disabled:opacity-50 shrink-0">
                    <span wire:loading.remove wire:target="exportData">Download My Data</span>
                    <span wire:loading wire:target="exportData">Preparing...</span>
                </button>
            </div>
        </div>

        {{-- Danger zone --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-rose-100 space-y-4">
            <div class="border-b border-rose-100 pb-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-rose-500">Delete Account</h2>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <p class="text-xs font-medium text-slate-500 leading-relaxed">
                    Permanently removes your account and all of your expenses, budgets, goals, receipts and alerts. This can't be undone.
                </p>
                <button type="button" wire:click="openDeleteModal"
                    class="px-5 py-2.5 rounded-2xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition-all shrink-0">
                    Delete My Account
                </button>
            </div>
        </div>
    </div>

    @if($confirmingAccountDeletion)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="closeDeleteModal">
            <div class="bg-white rounded-3xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <div class="h-11 w-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>

                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Permanently delete your account?</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">Everything tied to your account is erased immediately. Consider downloading your data first.</p>
                </div>

                <form wire:submit.prevent="deleteAccount" class="space-y-3">
                    <div class="space-y-1.5">
                        <label for="delete_password" class="block text-xs font-bold text-slate-700">Confirm with your password</label>
                        <input id="delete_password" type="password" wire:model.defer="delete_password" autocomplete="current-password" autofocus
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 transition-all">
                        @error('delete_password')
                            <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <button type="button" wire:click="closeDeleteModal"
                            class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="deleteAccount"
                            class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors disabled:opacity-50">
                            <span wire:loading.remove wire:target="deleteAccount">Delete Forever</span>
                            <span wire:loading wire:target="deleteAccount">Deleting...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>