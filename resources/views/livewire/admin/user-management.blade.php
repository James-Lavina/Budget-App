<div>

    <div class="space-y-6">

        {{-- Header --}}
        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">User Management</h1>
            <p class="text-sm text-slate-500 font-medium mt-1">Manage registered users and review their budget behavior.</p>
        </div>

        @if (session()->has('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse shrink-0"></span>
                {{ session('success') }}
            </div>
        @endif

        {{-- Filter bar --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm flex flex-col sm:flex-row items-center gap-3">
            <div class="relative w-full sm:flex-1">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input type="text" wire:model.debounce.300ms="search" placeholder="Search name, email, or school..."
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 focus:bg-white transition-all">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
                <span class="text-xs font-bold text-slate-500">Status:</span>
                <select wire:model="statusFilter"
                    class="px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 focus:bg-white transition-all">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="suspended">Inactive</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            @if($users->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-xs font-semibold text-slate-400">No matching users found.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="px-5 sm:px-6 py-3">Profile</th>
                                <th class="px-5 sm:px-6 py-3">Full Name</th>
                                <th class="px-5 sm:px-6 py-3">Email</th>
                                <th class="px-5 sm:px-6 py-3">School</th>
                                <th class="px-5 sm:px-6 py-3">Weekly Allowance</th>
                                <th class="px-5 sm:px-6 py-3">Remaining</th>
                                <th class="px-5 sm:px-6 py-3">Status</th>
                                <th class="px-5 sm:px-6 py-3">Registered</th>
                                <th class="px-5 sm:px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($users as $user)
                                <tr class="{{ $user->status === 'suspended' ? 'opacity-60' : '' }}">
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <div class="h-8 w-8 rounded-full bg-[var(--brand-primary)] text-white font-extrabold text-[11px] flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($user->name, 0, 1) . substr(strrchr(' '.$user->name, ' '), 1, 1)) }}
                                        </div>
                                    </td>
                                    <td class="px-5 sm:px-6 py-3.5 font-semibold text-slate-800 whitespace-nowrap">{{ $user->name }}</td>
                                    <td class="px-5 sm:px-6 py-3.5 text-[var(--brand-primary)] font-medium whitespace-nowrap">{{ $user->email }}</td>
                                    <td class="px-5 sm:px-6 py-3.5 text-slate-600 whitespace-nowrap">{{ $user->school ?? '—' }}</td>
                                    <td class="px-5 sm:px-6 py-3.5 text-slate-700 font-mono font-semibold whitespace-nowrap">
                                        ₱{{ number_format($user->latestWeeklyBudget->total_allowance ?? $user->default_allowance ?? 0, 2) }}
                                    </td>
                                    <td class="px-5 sm:px-6 py-3.5 text-slate-700 font-mono font-semibold whitespace-nowrap">
                                        @if($user->latestWeeklyBudget)
                                            ₱{{ number_format($user->latestWeeklyBudget->remaining_allowance, 2) }}
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide {{ $user->status === 'suspended' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ $user->status === 'suspended' ? 'inactive' : 'active' }}
                                        </span>
                                    </td>
                                    <td class="px-5 sm:px-6 py-3.5 text-slate-500 whitespace-nowrap">{{ $user->created_at->format('Y-m-d') }}</td>
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <button wire:click="viewUser({{ $user->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">View</button>
                                            <button wire:click="openEdit({{ $user->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">Edit</button>
                                            <button wire:click="confirmSuspend({{ $user->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                                                {{ $user->status === 'suspended' ? 'Reactivate' : 'Suspend' }}
                                            </button>
                                            <button wire:click="confirmDelete({{ $user->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-5 sm:px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- VIEW MODAL --}}
    @if($viewingUser)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="closeView">
            <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6 space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        {{-- Avatar circle --}}
                            <div class="h-12 w-12 rounded-2xl bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] font-extrabold flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($viewingUser->name, 0, 1)) }}
                            </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">{{ $viewingUser->name }}</h3>
                            <p class="text-xs text-slate-500 font-medium">{{ $viewingUser->email }}</p>
                        </div>
                    </div>
                    <button wire:click="closeView" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-slate-400 font-bold uppercase tracking-wide text-[10px] block mb-0.5">School</span>
                        <span class="font-semibold text-slate-800">{{ $viewingUser->school ?? '—' }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-slate-400 font-bold uppercase tracking-wide text-[10px] block mb-0.5">Registered</span>
                        <span class="font-semibold text-slate-800">{{ $viewingUser->created_at->format('Y-m-d') }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-slate-400 font-bold uppercase tracking-wide text-[10px] block mb-0.5">Allowance</span>
                        <span class="font-semibold text-slate-800 font-mono">₱{{ number_format($viewingUser->latestWeeklyBudget->total_allowance ?? $viewingUser->default_allowance ?? 0, 2) }}</span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-slate-400 font-bold uppercase tracking-wide text-[10px] block mb-0.5">Remaining</span>
                        <span class="font-semibold text-slate-800 font-mono">
                            {{ $viewingUser->latestWeeklyBudget ? '₱'.number_format($viewingUser->latestWeeklyBudget->remaining_allowance, 2) : '—' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-slate-400 font-bold uppercase tracking-wide text-[10px] block mb-0.5">Savings Goal</span>
                        <span class="font-semibold text-slate-800 font-mono">
                            {{ $viewingExtras['topGoal'] ? '₱'.number_format($viewingExtras['topGoal']->target_amount, 2) : '—' }}
                        </span>
                    </div>
                    <div class="p-3 bg-slate-50 rounded-xl">
                        <span class="text-slate-400 font-bold uppercase tracking-wide text-[10px] block mb-0.5">Risk Score</span>
                        <span class="font-semibold text-slate-800">{{ $viewingExtras['riskScore'] }}</span>
                    </div>
                </div>

                {{-- Weekly spending progress --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-700">Weekly spending</span>
                        <span class="font-bold text-slate-500">{{ $viewingExtras['percentUsed'] }}% used</span>
                    </div>
                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                        {{-- Weekly spending progress bar fill --}}
                        <div class="h-full bg-[var(--brand-primary)] rounded-full" style="width: {{ min(100, $viewingExtras['percentUsed']) }}%"></div>
                    </div>
                </div>

                {{-- Forecast banner --}}
                @php
                    $toneMap = [
                        'emerald' => 'bg-emerald-50 border-emerald-100 text-emerald-800',
                        'amber'   => 'bg-amber-50 border-amber-100 text-amber-800',
                        'rose'    => 'bg-rose-50 border-rose-100 text-rose-800',
                        'slate'   => 'bg-slate-50 border-slate-100 text-slate-600',
                    ];
                    $toneClass = $toneMap[$viewingExtras['forecastTone']] ?? $toneMap['slate'];
                @endphp
                <div class="p-3.5 rounded-xl border {{ $toneClass }}">
                    <span class="text-xs font-extrabold block">Forecast: {{ $viewingExtras['forecastLabel'] }}</span>
                    <span class="text-[11px] font-medium opacity-80">Current behavior assessment for the weekly budget.</span>
                </div>

                {{-- Recent expenses --}}
                <div class="space-y-2">
                    <h4 class="text-xs font-extrabold text-slate-800">Recent Expenses</h4>
                    @forelse($viewingExtras['recentExpenses'] as $exp)
                        <div class="flex items-center justify-between px-3.5 py-2.5 bg-slate-50 rounded-xl text-xs">
                            <div>
                                <span class="font-semibold text-slate-700 block">{{ $exp->item_name }}</span>
                                <span class="text-[10px] text-slate-400 font-medium">{{ $exp->transaction_date->format('M j') }}</span>
                            </div>
                            <span class="font-bold text-slate-900 font-mono">₱{{ number_format($exp->amount, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-[11px] text-slate-400 font-medium">No expenses logged yet.</p>
                    @endforelse
                </div>

                <button wire:click="closeView" class="w-full py-2.5 rounded-2xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Close</button>
            </div>
        </div>
    @endif

    {{-- EDIT MODAL --}}
    @if($editingUserId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="closeEdit">
            <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6 space-y-5">
                <div class="flex items-start justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">Edit User</h3>
                    <button wire:click="closeEdit" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveEdit" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Full Name</label>
                        <input type="text" wire:model.defer="edit_name" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('edit_name') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Email</label>
                        <input type="email" wire:model.defer="edit_email" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('edit_email') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">School</label>
                        <input type="text" wire:model.defer="edit_school" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('edit_school') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Default Allowance</label>
                            <input type="number" step="1" wire:model.defer="edit_default_allowance" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                            @error('edit_default_allowance') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-slate-700">Reset Day</label>
                            <select wire:model.defer="edit_default_reset_day" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                                    <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" wire:click="closeEdit" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- SUSPEND / REACTIVATE CONFIRM --}}
    @if($confirmingSuspendId)
        @php $target = $users->firstWhere('id', $confirmingSuspendId); @endphp
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="cancelSuspend">
            <div class="bg-white rounded-3xl shadow-xl max-w-sm w-full p-6 space-y-4">
                <div class="h-11 w-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">
                        {{ $target && $target->status === 'suspended' ? 'Reactivate this user?' : 'Suspend this user?' }}
                    </h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        {{ $target && $target->status === 'suspended'
                            ? 'They will regain access to their account immediately.'
                            : 'They will be unable to log in until reactivated. Their data is kept intact.' }}
                    </p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button wire:click="cancelSuspend" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="toggleSuspend" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition-colors">Confirm</button>
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
                    <h3 class="text-sm font-extrabold text-slate-900">Permanently delete this user?</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">This removes their account and every associated expense, budget, goal, and log. This can't be undone.</p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button wire:click="cancelDelete" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="deleteUser" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors">Delete Permanently</button>
                </div>
            </div>
        </div>
    @endif

</div>