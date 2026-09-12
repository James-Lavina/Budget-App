<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">OCR & AI Settings</h1>
        <p class="text-sm text-slate-500 mt-1">Manage the Groq services used for receipt scanning and AI guidance.</p>
    </div>

    @if (session()->has('success'))
        <div class="p-3 bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-xl text-xs font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-6">
        <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
            <div class="h-10 w-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <x-heroicon-o-chip class="w-5 h-5" />
            </div>
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Groq AI</h3>
                <p class="text-xs text-slate-500">Single provider — powers both receipt scanning and AI coaching text.</p>
            </div>
        </div>

        <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">API Key</label>
            <input type="password" wire:model.defer="groq_api_key" autocomplete="new-password"
                placeholder="{{ $groq_api_key ? '••••••••••••••••' : 'sk-...' }}"
                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
            <p class="text-[11px] text-slate-400">Stored encrypted at rest. Leave blank and save to keep the current key.</p>
            @error('groq_api_key') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">

            {{-- Vision Model --}}
            <div class="p-4 bg-slate-50 rounded-2xl space-y-3">
                <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                    Vision Model <span class="font-normal text-slate-400 normal-case">(Receipt Scanner)</span>
                </h4>
                <input type="text" wire:model.defer="groq_vision_model"
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-mono bg-white focus:ring-2 focus:ring-indigo-500">
                @error('groq_vision_model') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror

                <button type="button" wire:click="testVisionConnection" wire:loading.attr="disabled" wire:target="testVisionConnection"
                    class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold text-[11px] transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="testVisionConnection">Test Vision Model</span>
                    <span wire:loading wire:target="testVisionConnection">Testing...</span>
                </button>

                @if ($visionTestStatus)
                    <div class="p-2.5 rounded-lg text-[11px] font-semibold {{ $visionTestStatus === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                        {{ $visionTestMessage }}
                    </div>
                @endif
            </div>

            {{-- Text Model --}}
            <div class="p-4 bg-slate-50 rounded-2xl space-y-3">
                <h4 class="text-xs font-extrabold text-slate-700 uppercase tracking-wider">
                    Text Model <span class="font-normal text-slate-400 normal-case">(AI Coach)</span>
                </h4>
                <input type="text" wire:model.defer="groq_text_model"
                    class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs font-mono bg-white focus:ring-2 focus:ring-indigo-500">
                @error('groq_text_model') <span class="text-xs text-rose-500 font-semibold">{{ $message }}</span> @enderror

                <div class="grid grid-cols-2 gap-2">
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Temperature</label>
                        <input type="number" step="0.1" min="0" max="2" wire:model.defer="groq_temperature"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        @error('groq_temperature') <span class="text-[10px] text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Max Tokens</label>
                        <input type="number" min="50" max="4000" wire:model.defer="groq_max_tokens"
                            class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs bg-white focus:ring-2 focus:ring-indigo-500">
                        @error('groq_max_tokens') <span class="text-[10px] text-rose-500 font-semibold">{{ $message }}</span> @enderror
                    </div>
                </div>

                <button type="button" wire:click="testTextConnection" wire:loading.attr="disabled" wire:target="testTextConnection"
                    class="w-full px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold text-[11px] transition-all disabled:opacity-50">
                    <span wire:loading.remove wire:target="testTextConnection">Test Text Model</span>
                    <span wire:loading wire:target="testTextConnection">Testing...</span>
                </button>

                @if ($textTestStatus)
                    <div class="p-2.5 rounded-lg text-[11px] font-semibold {{ $textTestStatus === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                        {{ $textTestMessage }}
                    </div>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
            <button type="submit" wire:loading.attr="disabled" wire:target="save"
                class="px-5 py-2.5 bg-[var(--brand-primary)] hover:opacity-90 text-white rounded-xl font-bold text-xs transition-all disabled:opacity-50">
                <span wire:loading.remove wire:target="save">Save Settings</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>
</div>