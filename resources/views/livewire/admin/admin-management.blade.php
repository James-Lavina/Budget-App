<div>
    <div class="space-y-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Admin Accounts</h1>
                <p class="text-sm text-slate-500 font-medium mt-1">Grant or revoke admin access. Restricted to super admins.</p>
            </div>
            <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-[var(--brand-primary)] hover:opacity-90 active:scale-[0.98] text-white font-extrabold text-xs px-5 py-3 rounded-2xl shadow-lg transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>New Admin</span>
            </button>
        </div>

        @if (session()->has('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl text-rose-800 text-xs font-bold flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-rose-500 animate-pulse shrink-0"></span>
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            @if($admins->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-xs font-semibold text-slate-400">No admin accounts yet — create one above.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="px-5 sm:px-6 py-3">Profile</th>
                                <th class="px-5 sm:px-6 py-3">Full Name</th>
                                <th class="px-5 sm:px-6 py-3">Email</th>
                                <th class="px-5 sm:px-6 py-3">Granted</th>
                                <th class="px-5 sm:px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($admins as $admin)
                                <tr>
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <div class="h-8 w-8 rounded-full bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] font-extrabold text-[11px] flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($admin->name, 0, 1)) }}
                                        </div>
                                    </td>
                                    <td class="px-5 sm:px-6 py-3.5 font-semibold text-slate-800 whitespace-nowrap">{{ $admin->name }}</td>
                                    <td class="px-5 sm:px-6 py-3.5 text-[var(--brand-primary)] font-medium whitespace-nowrap">{{ $admin->email }}</td>
                                    <td class="px-5 sm:px-6 py-3.5 text-slate-500 whitespace-nowrap">{{ $admin->created_at->format('Y-m-d') }}</td>
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <button wire:click="confirmDemote({{ $admin->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">Revoke Access</button>
                                            <button wire:click="confirmDelete({{ $admin->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $admins->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- CREATE MODAL --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="closeCreate">
            <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-start justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">Create New Admin</h3>
                    <button wire:click="closeCreate" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="store" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Full Name</label>
                        <input type="text" wire:model.defer="create_name" placeholder="e.g., Maria Santos"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-[var(--brand-primary)] transition-all">
                        @error('create_name') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Email</label>
                        <input type="email" wire:model.defer="create_email" placeholder="admin@example.com"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-[var(--brand-primary)] transition-all">
                        @error('create_email') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Password</label>
                            <input type="password" wire:model.defer="create_password"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-[var(--brand-primary)] transition-all">
                            @error('create_password') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Confirm Password</label>
                            <input type="password" wire:model.defer="create_password_confirmation"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-[var(--brand-primary)] transition-all">
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" wire:click="closeCreate" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-[var(--brand-primary)] hover:opacity-90 rounded-xl transition-colors">Create Admin</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- REVOKE CONFIRM --}}
    @if($confirmingDemoteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="cancelDemote">
            <div class="bg-white rounded-3xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <div class="h-11 w-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Revoke admin access?</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">Their account will be demoted to a regular student account. Their login and data stay intact.</p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button wire:click="cancelDemote" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="demote" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition-colors">Revoke</button>
                </div>
            </div>
        </div>
    @endif

    {{-- DELETE CONFIRM --}}
    @if($confirmingDeleteId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="cancelDelete">
            <div class="bg-white rounded-3xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <div class="h-11 w-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Permanently delete this admin?</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">This removes their account entirely. This can't be undone.</p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button wire:click="cancelDelete" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="deleteAdmin" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors">Delete Permanently</button>
                </div>
            </div>
        </div>
    @endif
</div>