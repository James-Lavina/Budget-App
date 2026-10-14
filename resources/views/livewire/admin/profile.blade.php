<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">My Profile</h1>
        <p class="text-sm text-slate-500 mt-1">Update your name, email, and password.</p>
    </div>

    @if (session()->has('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="updateProfile" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-5">
        <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700">Full name</label>
            <input type="text" wire:model.defer="name"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
            @error('name') <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700">Email</label>
            <input type="email" wire:model.defer="email"
                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-semibold focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
            @error('email') <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span> @enderror
        </div>

        <div class="pt-2 border-t border-slate-100 space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 pt-3">Change password</h2>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Current password</label>
                <input type="password" wire:model.defer="current_password" autocomplete="current-password"
                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                @error('current_password') <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">New password</label>
                    <input type="password" wire:model.defer="new_password" autocomplete="new-password" placeholder="8+ characters"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                    @error('new_password') <span class="text-[11px] font-semibold text-rose-500 block">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Confirm new password</label>
                    <input type="password" wire:model.defer="new_password_confirmation" autocomplete="new-password"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" wire:loading.attr="disabled"
                class="px-8 py-3 bg-[var(--brand-primary)] hover:opacity-90 text-white rounded-2xl font-bold text-xs shadow-lg transition-all disabled:opacity-50">
                <span wire:loading.remove wire:target="updateProfile">Save Changes</span>
                <span wire:loading wire:target="updateProfile">Saving...</span>
            </button>
        </div>
    </form>
</div>