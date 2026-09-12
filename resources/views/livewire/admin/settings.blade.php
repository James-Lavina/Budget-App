<div class="max-w-4xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Settings</h1>
        <p class="text-sm text-slate-500 mt-1">Configure general {{ \App\Models\AppSetting::current()->application_name }} application preferences.</p>
    </div>

    @if (session()->has('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold flex items-center gap-2">
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-6">
            <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                <div class="h-10 w-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">General Settings</h3>
                    <p class="text-xs text-slate-400 font-medium">Basic application configuration</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Application Name</label>
                    <input type="text" wire:model.defer="application_name"
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 font-semibold text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
                    @error('application_name') <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-slate-700">Primary Color</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(\App\Http\Livewire\Admin\Settings::COLOR_PRESETS as $key => $preset)
                            <button type="button" wire:click="$set('primary_color', '{{ $preset['value'] }}')" title="{{ $preset['label'] }}"
                                class="h-11 w-11 rounded-xl flex items-center justify-center transition-all {{ $primary_color === $preset['value'] ? 'ring-2 ring-offset-2 ring-slate-800' : '' }}"
                                style="background-color: {{ $preset['value'] }};">
                                @if($primary_color === $preset['value'])
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @error('primary_color') <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-700">Logo Upload</label>

                @if($existing_logo_path && !$logo)
                    <div class="flex items-center gap-3 mb-2">
                        <img src="{{ Storage::url($existing_logo_path) }}" class="h-10 w-10 rounded-xl object-cover border border-slate-200">
                        <button type="button" wire:click="removeLogo" class="text-xs font-bold text-rose-600 hover:text-rose-700">Remove logo</button>
                    </div>
                @endif

                @if($logo)
                    <div class="mb-2">
                        <img src="{{ $logo->temporaryUrl() }}" class="h-10 w-10 rounded-xl object-cover border border-slate-200">
                    </div>
                @endif

                <input type="file" wire:model="logo" accept="image/*"
                    class="block w-full text-xs font-semibold text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-slate-100 file:text-slate-700 file:font-bold hover:file:bg-slate-200 transition-all">
                <div wire:loading wire:target="logo" class="text-[11px] text-indigo-500 font-semibold">Uploading...</div>
                @error('logo') <span class="text-[11px] font-semibold text-rose-500 block mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 pt-6 pb-4">
                <h3 class="text-sm font-extrabold text-slate-900">System Preferences</h3>
            </div>

            <label class="flex items-center justify-between gap-4 px-6 py-4 border-t border-slate-100 cursor-pointer">
                <div>
                    <span class="text-sm font-bold text-slate-800 block">Email Notifications</span>
                    <span class="text-xs text-slate-400 font-medium">Allow system-generated email notifications.</span>
                </div>
                {{-- Email Notifications toggle --}}
                <input type="checkbox" wire:model.defer="email_notifications_enabled" class="sr-only peer">
                <div class="relative w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-[var(--brand-primary)] transition-colors shrink-0
                    after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
            </label>

            <label class="flex items-center justify-between gap-4 px-6 py-4 border-t border-slate-100 cursor-pointer">
                <div>
                    <span class="text-sm font-bold text-slate-800 block">Maintenance Mode</span>
                    <span class="text-xs text-slate-400 font-medium">Temporarily restrict normal user access.</span>
                </div>
                <input type="checkbox" wire:model.defer="maintenance_mode_enabled" class="sr-only peer">
                <div class="relative w-11 h-6 bg-slate-200 rounded-full peer-checked:bg-rose-600 transition-colors shrink-0
                    after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></div>
            </label>
        </div>

        <div class="flex justify-end">
            {{-- Save Settings button --}}
            <button type="submit" wire:loading.attr="disabled"
            class="px-8 py-3 bg-[var(--brand-primary)] hover:opacity-90 text-white rounded-2xl font-bold text-xs shadow-lg transition-all transform active:scale-95 disabled:opacity-50 flex items-center gap-2">
                <span wire:loading.remove wire:target="save">Save Settings</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>
</div>