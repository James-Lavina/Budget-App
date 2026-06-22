<div class="max-w-2xl mx-auto px-4 sm:px-6 py-12">
    <div class="mb-10 text-center sm:text-left">
        <h1 class="text-2xl font-black text-slate-900 tracking-tight sm:text-3xl">Profile Identity</h1>
        <p class="text-slate-500 text-sm font-medium mt-2">Manage your student credentials and account security access keys.</p>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-sm">
            <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border border-slate-200/70 rounded-3xl shadow-[0_10px_30px_-10px_rgba(0,0,0,0.04)] overflow-hidden">
        <form wire:submit.prevent="updateProfile" class="p-6 sm:p-10 space-y-8">
            
            <div class="space-y-6">
                <div class="space-y-2">
                    <label for="name" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Display Name</label>
                    <input type="text" wire:model.defer="name" id="name" 
                           class="block w-full h-14 rounded-2xl border-0 bg-slate-50 px-4 text-sm font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all duration-150">
                    @error('name') <span class="text-rose-600 text-xs font-bold mt-1 block pl-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Email Address</label>
                        <input type="email" wire:model.defer="email" id="email" 
                               class="block w-full h-14 rounded-2xl border-0 bg-slate-50 px-4 text-sm font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all duration-150">
                        @error('email') <span class="text-rose-600 text-xs font-bold mt-1 block pl-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="email_confirmation" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Confirm Email Address</label>
                        <input type="email" wire:model.defer="email_confirmation" id="email_confirmation" 
                               class="block w-full h-14 rounded-2xl border-0 bg-slate-50 px-4 text-sm font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all duration-150">
                    </div>
                </div>
            </div>

            <div class="relative py-2">
                <div class="absolute inset-0 flex items-center" aria-hidden="true">
                    <div class="w-full border-t border-slate-100"></div>
                </div>
                <div class="relative flex justify-start">
                    <span class="bg-white pr-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Security Access</span>
                </div>
            </div>

            <div class="space-y-6">
                <div class="space-y-2">
                    <label for="current_password" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Current Password</label>
                    <input type="password" wire:model.defer="current_password" id="current_password" autocomplete="current-password"
                           placeholder="••••••••"
                           class="block w-full h-14 rounded-2xl border-0 bg-slate-50 px-4 text-sm font-semibold text-slate-800 placeholder-slate-300 focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all duration-150">
                    @error('current_password') <span class="text-rose-600 text-xs font-bold mt-1 block pl-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="new_password" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">New Password</label>
                        <input type="password" wire:model.defer="new_password" id="new_password" autocomplete="new-password"
                               placeholder="Minimum 8 characters"
                               class="block w-full h-14 rounded-2xl border-0 bg-slate-50 px-4 text-sm font-semibold text-slate-800 placeholder-slate-300 focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all duration-150">
                        @error('new_password') <span class="text-rose-600 text-xs font-bold mt-1 block pl-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="new_password_confirmation" class="block text-xs font-bold text-slate-500 uppercase tracking-widest">Confirm New Password</label>
                        <input type="password" wire:model.defer="new_password_confirmation" id="new_password_confirmation" autocomplete="new-password"
                               placeholder="Match new password"
                               class="block w-full h-14 rounded-2xl border-0 bg-slate-50 px-4 text-sm font-semibold text-slate-800 placeholder-slate-300 focus:bg-white focus:ring-2 focus:ring-indigo-600 focus:border-transparent transition-all duration-150">
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full h-14 flex items-center justify-center rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-lg shadow-indigo-600/20 active:scale-[0.98] transition-all duration-150">
                    Update Account Details
                </button>
            </div>

        </form>
    </div>
</div>