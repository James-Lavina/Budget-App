<div class="max-w-5xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/70 shadow-sm">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Notification Center</h1>
            <p class="text-sm font-medium text-slate-500 mt-1">
                All your system alerts, budget updates, and activity history.
            </p>
        </div>
        
        @if($notifications->where('read_at', null)->count() > 0)
            <button 
                wire:click="markAllAsRead" 
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-2xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 font-semibold text-sm transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Mark All as Read
            </button>
        @endif
    </div>

    <!-- Notification List -->
    <div class="bg-white rounded-3xl border border-slate-200/70 shadow-sm overflow-hidden">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = is_null($notification->read_at);
                $severity = $data['severity_tier'] ?? 'info';
            @endphp

            <div class="p-5 border-b border-slate-100 last:border-b-0 transition-colors flex items-start justify-between gap-4 {{ $isUnread ? 'bg-indigo-50/30' : 'hover:bg-slate-50/60' }}">
                <div class="flex items-start gap-4">
                    <!-- Icon -->
                    <div class="shrink-0 p-3 rounded-2xl mt-0.5
                        @if($severity === 'high') bg-red-100 text-red-600
                        @elseif($severity === 'medium') bg-amber-100 text-amber-600
                        @elseif($severity === 'success') bg-emerald-100 text-emerald-600
                        @else bg-indigo-100 text-indigo-600 @endif">
                        
                        @if($severity === 'high' || $severity === 'medium')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        @elseif($severity === 'success')
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @else
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-slate-800 leading-relaxed">
                            {{ $data['description'] ?? ($data['message'] ?? ($data['title'] ?? 'System notification received.')) }}
                        </p>
                        <span class="text-xs font-semibold text-slate-400 block">
                            {{ $notification->created_at->diffForHumans() }}
                        </span>
                    </div>
                </div>

                <!-- Action Button -->
                @if($isUnread)
                    <button 
                        wire:click="markAsRead('{{ $notification->id }}')" 
                        title="Mark as read"
                        class="shrink-0 p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </button>
                @endif
            </div>
        @empty
            <div class="p-12 text-center space-y-3">
                <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-400 mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 01-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-800">No notifications yet</h3>
                <p class="text-xs text-slate-500 max-w-sm mx-auto">
                    When your budget alerts trigger, they will be listed here.
                </p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-2">
        {{ $notifications->links() }}
    </div>
</div>