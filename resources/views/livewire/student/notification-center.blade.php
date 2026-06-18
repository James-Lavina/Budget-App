<div class="relative">
    <button id="notifBellBtn" class="relative p-2 text-gray-600 hover:text-indigo-600 focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        
        @if($notifications->count() > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full transform translate-x-1/3 -translate-y-1/3">
                {{ $notifications->count() }}
            </span>
        @endif
    </button>

    <div id="notifDropdownMenu" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-100 py-2 z-50">
        
        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Notifications</h3>
            @if($notifications->count() > 0)
                <button wire:click="markAllAsRead" class="text-xs text-indigo-600 hover:underline">Mark all read</button>
            @endif
        </div>

        <div class="max-h-64 overflow-y-auto">
            @forelse($notifications as $notification)
                <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-50 flex flex-col justify-between items-start">
                    <div class="flex items-center mb-1">
                        <span class="text-xs font-bold px-2 py-0.5 rounded uppercase
                            {{ ($notification->data['severity_tier'] ?? 'low') === 'high' ? 'bg-red-100 text-red-800' : 
                               (($notification->data['severity_tier'] ?? 'low') === 'medium' ? 'bg-yellow-100 text-yellow-800' : 
                               (($notification->data['severity_tier'] ?? 'low') === 'success' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800')) }}">
                            {{ $notification->data['severity_tier'] ?? 'low' }}
                        </span>
                        <span class="text-xs text-gray-400 ml-2">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <p class="text-xs text-gray-600 mb-2">{{ $notification->data['description'] ?? 'No details provided.' }}</p>
                    <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-gray-400 hover:text-indigo-600 transition-colors">
                        ✓ Mark as read
                    </button>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-400">
                    Your financial tracking parameters are clear. No active anomalies logged!
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bellBtn = document.getElementById('notifBellBtn');
        const dropdownMenu = document.getElementById('notifDropdownMenu');

        // Toggle dropdown when clicking the bell icon
        bellBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            dropdownMenu.classList.toggle('hidden');
        });

        // Close dropdown instantly if clicking anywhere else outside on the page
        document.addEventListener('click', function (event) {
            if (!dropdownMenu.contains(event.target) && event.target !== bellBtn) {
                dropdownMenu.classList.add('hidden');
            }
        });
        
        // Prevent closing dropdown when clicking inside the menu drawer itself
        dropdownMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });
</script>