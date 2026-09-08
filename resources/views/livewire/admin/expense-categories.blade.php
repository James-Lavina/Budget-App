<div>

    <div class="space-y-6">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Expense Categories</h1>
                <p class="text-sm text-slate-500 font-medium mt-1">Manage the categories users can assign to expenses.</p>
            </div>
            <button wire:click="openCreate" class="inline-flex items-center gap-2 bg-[var(--brand-primary)] hover:opacity-90 active:scale-[0.98] text-white font-extrabold text-xs px-5 py-3 rounded-2xl shadow-lg transition-all shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Add Category</span>
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

        {{-- Table --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            @if($categories->isEmpty())
                <div class="p-12 text-center">
                    <p class="text-xs font-semibold text-slate-400">No categories yet — add your first one above.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="px-5 sm:px-6 py-3">Category</th>
                                <th class="px-5 sm:px-6 py-3">Icon</th>
                                <th class="px-5 sm:px-6 py-3">Color</th>
                                <th class="px-5 sm:px-6 py-3">Status</th>
                                <th class="px-5 sm:px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($categories as $cat)
                                @php
                                    $swatch = collect($palette)->first(fn($p) => $p['value'] === $cat->color)['swatch'] ?? 'bg-slate-400';
                                @endphp
                                <tr class="{{ $cat->status === 'disabled' ? 'opacity-50' : '' }}">
                                    <td class="px-5 sm:px-6 py-3.5 font-semibold text-[var(--brand-primary)] whitespace-nowrap">{{ $cat->name }}</td>
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <div class="h-8 w-8 rounded-xl {{ $cat->color }} flex items-center justify-center">
                                            <x-category-icon :type="$cat->icon" />
                                        </div>
                                    </td>
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <span class="inline-block h-5 w-5 rounded-full {{ $swatch }}"></span>
                                    </td>
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide {{ $cat->status === 'disabled' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ $cat->status }}
                                        </span>
                                    </td>
                                    <td class="px-5 sm:px-6 py-3.5">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <button wire:click="openEdit({{ $cat->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">Edit</button>
                                            <button wire:click="toggleStatus({{ $cat->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                                                {{ $cat->status === 'disabled' ? 'Enable' : 'Disable' }}
                                            </button>
                                            <button wire:click="confirmDelete({{ $cat->id }})" class="px-2.5 py-1.5 text-[11px] font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors">Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>

    {{-- CREATE MODAL --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="closeCreate">
            <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6 space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">Add Category</h3>
                    <button wire:click="closeCreate" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="store" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Name</label>
                        <input type="text" wire:model.defer="create_name" placeholder="e.g., Groceries" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('create_name') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Description <span class="font-normal text-slate-400">(optional)</span></label>
                        <input type="text" wire:model.defer="create_description" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>

                    {{-- ICON --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700">Icon</label>

                        <div class="grid grid-cols-5 gap-2">
                            @foreach($curatedIcons as $key => $label)
                                <button type="button" wire:click="$set('create_icon', '{{ $key }}')" title="{{ $label }}"
                                    class="h-11 rounded-xl border-2 flex items-center justify-center transition-all {{ $create_icon === $key ? 'border-indigo-600 bg-indigo-50' : 'border-slate-200 hover:border-slate-300' }}">
                                    <x-category-icon :type="$key" class="w-5 h-5 text-slate-600" />
                                </button>
                            @endforeach
                        </div>

                        <div class="relative pt-1">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-[calc(50%+2px)] -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                            </svg>
                            <input type="text" wire:model.debounce.300ms="create_icon_search" placeholder="Search more icons (e.g. heart, home, gift)..."
                                class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] font-medium placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>

                        @if(!empty($createSearchResults))
                            <div class="grid grid-cols-6 gap-1.5 p-2 bg-slate-50 rounded-xl max-h-40 overflow-y-auto">
                                @foreach($createSearchResults as $iconName)
                                    <button type="button" wire:click="$set('create_icon', '{{ $iconName }}')" title="{{ $iconName }}"
                                        class="h-10 rounded-lg border-2 flex items-center justify-center transition-all bg-white {{ $create_icon === $iconName ? 'border-indigo-600' : 'border-slate-200 hover:border-slate-300' }}">
                                        <x-category-icon :type="$iconName" class="w-4 h-4 text-slate-600" />
                                    </button>
                                @endforeach
                            </div>
                        @elseif(strlen(trim($create_icon_search)) >= 2)
                            <p class="text-[11px] text-slate-400 font-medium px-1">No icons matched "{{ $create_icon_search }}".</p>
                        @endif

                        @error('create_icon') <span class="text-[10px] font-bold text-rose-600 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Color</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($palette as $key => $preset)
                                <button type="button" wire:click="$set('create_color', '{{ $key }}')" title="{{ $preset['label'] }}"
                                    class="h-11 w-11 rounded-full {{ $preset['swatch'] }} flex items-center justify-center transition-all {{ $create_color === $key ? 'ring-[3px] ring-offset-2 ring-slate-900' : '' }}">
                                    @if($create_color === $key)
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                        @error('create_color') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    @if($createDuplicate)
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                            <p class="text-[11px] font-semibold text-amber-800 leading-snug">
                                "{{ $createDuplicate->name }}" already uses this icon and color — students may find them hard to tell apart. You can still save if that's fine.
                            </p>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" wire:click="closeCreate" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">Create</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- EDIT MODAL --}}
    @if($editingId)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" wire:click.self="closeEdit">
            <div class="bg-white rounded-3xl shadow-xl max-w-md w-full p-6 space-y-5 max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between">
                    <h3 class="text-sm font-extrabold text-slate-900">Edit Category</h3>
                    <button wire:click="closeEdit" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form wire:submit.prevent="update" class="space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Name</label>
                        <input type="text" wire:model.defer="edit_name" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        @error('edit_name') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Description <span class="font-normal text-slate-400">(optional)</span></label>
                        <input type="text" wire:model.defer="edit_description" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>

                    {{-- ICON --}}
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700">Icon</label>

                        <div class="grid grid-cols-5 gap-2">
                            @foreach($curatedIcons as $key => $label)
                                <button type="button" wire:click="$set('edit_icon', '{{ $key }}')" title="{{ $label }}"
                                    class="h-11 rounded-xl border-2 flex items-center justify-center transition-all {{ $edit_icon === $key ? 'border-indigo-600 bg-indigo-50' : 'border-slate-200 hover:border-slate-300' }}">
                                    <x-category-icon :type="$key" class="w-5 h-5 text-slate-600" />
                                </button>
                            @endforeach
                        </div>

                        <div class="relative pt-1">
                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-[calc(50%+2px)] -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                            </svg>
                            <input type="text" wire:model.debounce.300ms="edit_icon_search" placeholder="Search more icons (e.g. heart, home, gift)..."
                                class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-[11px] font-medium placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>

                        @if(!empty($editSearchResults))
                            <div class="grid grid-cols-6 gap-1.5 p-2 bg-slate-50 rounded-xl max-h-40 overflow-y-auto">
                                @foreach($editSearchResults as $iconName)
                                    <button type="button" wire:click="$set('edit_icon', '{{ $iconName }}')" title="{{ $iconName }}"
                                        class="h-10 rounded-lg border-2 flex items-center justify-center transition-all bg-white {{ $edit_icon === $iconName ? 'border-indigo-600' : 'border-slate-200 hover:border-slate-300' }}">
                                        <x-category-icon :type="$iconName" class="w-4 h-4 text-slate-600" />
                                    </button>
                                @endforeach
                            </div>
                        @elseif(strlen(trim($edit_icon_search)) >= 2)
                            <p class="text-[11px] text-slate-400 font-medium px-1">No icons matched "{{ $edit_icon_search }}".</p>
                        @endif

                        @error('edit_icon') <span class="text-[10px] font-bold text-rose-600 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700">Color</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($palette as $key => $preset)
                                <button type="button" wire:click="$set('edit_color', '{{ $key }}')" title="{{ $preset['label'] }}"
                                    class="h-9 w-9 rounded-full {{ $preset['swatch'] }} flex items-center justify-center transition-all {{ $edit_color === $key ? 'ring-2 ring-offset-2 ring-indigo-600' : '' }}">
                                    @if($edit_color === $key)
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                        @error('edit_color') <span class="text-[10px] font-bold text-rose-600 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    @if($editDuplicate)
                        <div class="p-3 bg-amber-50 border border-amber-100 rounded-xl flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                            <p class="text-[11px] font-semibold text-amber-800 leading-snug">
                                "{{ $editDuplicate->name }}" already uses this icon and color — students may find them hard to tell apart. You can still save if that's fine.
                            </p>
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-2">
                        <button type="button" wire:click="closeEdit" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-colors">Save Changes</button>
                    </div>
                </form>
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
                    <h3 class="text-sm font-extrabold text-slate-900">Delete this category?</h3>
                    <p class="text-xs text-slate-500 font-medium mt-1">This can't be undone. Categories currently used by logged expenses can't be deleted — disable them instead.</p>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <button wire:click="cancelDelete" class="flex-1 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors">Cancel</button>
                    <button wire:click="delete" wire:loading.attr="disabled" class="flex-1 px-4 py-2.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition-colors">Delete</button>
                </div>
            </div>
        </div>
    @endif

</div>