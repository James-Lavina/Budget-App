<div class="max-w-5xl mx-auto space-y-6">

    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Activity Logs</h1>
        <p class="text-sm text-slate-500 mt-1">Review important actions performed within the {{ \App\Models\AppSetting::current()->application_name }} system.</p>
    </div>

    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6 space-y-5">

        {{-- Search --}}
        <div class="relative">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" wire:model.debounce.300ms="search" placeholder="Search logs..."
                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-600/20 focus:border-indigo-600 transition-all">
        </div>

        {{-- Log Rows --}}
        <div class="space-y-3">
            @forelse ($logs as $log)
                @php
                    $status = $this->statusFor($log->event_type);
                @endphp
                <div class="p-4 bg-slate-50/60 border border-slate-100 rounded-2xl flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="h-9 w-9 rounded-xl bg-[var(--brand-primary)]/10 text-[var(--brand-primary)] flex items-center justify-center shrink-0">
                            <x-heroicon-o-sparkles class="w-4 h-4" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-800 truncate">
                                {{ $log->user->name ?? 'Unknown User' }}
                                <span class="font-medium text-slate-500">{{ $this->labelFor($log->event_type) }}</span>
                            </p>
                            @if($log->details)
                                <p class="text-xs text-slate-400 font-medium truncate mt-0.5">{{ $log->details }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-4 shrink-0">
                        <span class="text-xs font-semibold text-slate-400 whitespace-nowrap">
                            {{ $log->created_at->format('M j, Y') }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $status['class'] }}">
                            {{ $status['label'] }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="py-14 text-center space-y-2">
                    <div class="h-10 w-10 bg-slate-50 rounded-xl flex items-center justify-center mx-auto text-slate-400 border border-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-700">No activity logged yet</p>
                    @if($search)
                        <p class="text-xs text-slate-400">No results matched "{{ $search }}".</p>
                    @endif
                </div>
            @endforelse
        </div>

        @if($logs->hasPages())
            <div class="pt-2">
                {{ $logs->links() }}
            </div>
        @endif

    </div>
</div>