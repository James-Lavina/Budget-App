<div class="relative" data-notif-widget>
    <button type="button" data-notif-bell class="relative p-2 text-gray-600 hover:text-[var(--brand)] focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        {{-- NEW: uses totalUnreadCount (all unread), not $notifications->count()
             — the list itself is now capped to 6 most recent, so the old
             count would have silently frozen at 6 once a user had more than
             that many unread. --}}
        @if($totalUnreadCount > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full transform translate-x-1/3 -translate-y-1/3">
                {{ $totalUnreadCount > 99 ? '99+' : $totalUnreadCount }}
            </span>
        @endif
    </button>

    <div data-notif-dropdown class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-100 py-2 z-50">
        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
            @if($totalUnreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-[var(--brand)] hover:opacity-80 transition-opacity">Mark all read</button>
            @endif
        </div>
        <div class="max-h-64 overflow-y-auto">
            @forelse($notifications as $notification)
                @php
                    // NEW: same resolved-flag read as the full Notification
                    // Center page. A notification can be unread AND already
                    // resolved (the backend clears the underlying condition
                    // independently of the student reading it), so this is
                    // a separate signal from read/unread.
                    $isResolved = $notification->data['resolved'] ?? false;
                    $severityTier = $notification->data['severity_tier'] ?? 'low';
                @endphp
                <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-50 flex flex-col justify-between items-start {{ $isResolved ? 'opacity-60' : '' }}">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="text-xs font-bold px-2 py-0.5 rounded uppercase
                            {{ $severityTier === 'high' ? 'bg-red-100 text-red-800' :
                               ($severityTier === 'medium' ? 'bg-yellow-100 text-yellow-800' :
                               ($severityTier === 'success' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800')) }}">
                            {{ $severityTier }}
                        </span>
                        @if($isResolved)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-emerald-50 text-emerald-600">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                </svg>
                                Resolved
                            </span>
                        @endif
                        <span class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-xs mb-2 {{ $isResolved ? 'text-gray-400' : 'text-gray-600' }}">{{ $notification->data['description'] ?? 'No details provided.' }}</p>
                    <div class="flex items-center gap-3">
                        <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-gray-400 hover:text-[var(--brand)] transition-colors">
                            ✓ Mark as read
                        </button>
                        {{-- NEW: dismiss() already existed on the component
                             but nothing in this view called it. --}}
                        <button wire:click="dismiss('{{ $notification->id }}')" class="text-xs text-gray-400 hover:text-rose-600 transition-colors">
                            ✕ Dismiss
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-400">
                    Your financial tracking parameters are clear. No active anomalies logged!
                </div>
            @endforelse
        </div>
        {{-- NEW: link out to the full Notification Center, since this
             dropdown now intentionally only shows the 6 most recent
             unread — anything older or already read/resolved lives on
             the full page, not here. --}}
        <div class="px-4 pt-2 border-t border-gray-100">
            <a href="{{ route('student.notifications') }}" class="block text-center text-xs font-semibold text-[var(--brand)] hover:opacity-80 py-1.5 transition-opacity">
                View all notifications →
            </a>
        </div>
    </div>
</div>

<script>
    if (!window.__notifDropdownDelegationBound) {
        window.__notifDropdownDelegationBound = true;
        document.addEventListener('click', function (event) {
            const bell = event.target.closest('[data-notif-bell]');
            if (bell) {
                event.stopPropagation();
                const widget = bell.closest('[data-notif-widget]');
                const dropdown = widget.querySelector('[data-notif-dropdown]');
                document.querySelectorAll('[data-notif-dropdown]').forEach(function (el) {
                    if (el !== dropdown) el.classList.add('hidden');
                });
                dropdown.classList.toggle('hidden');
                return;
            }
            if (!event.target.closest('[data-notif-dropdown]')) {
                document.querySelectorAll('[data-notif-dropdown]').forEach(function (el) {
                    el.classList.add('hidden');
                });
            }
        });
    }
</script>