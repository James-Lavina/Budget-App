<div class="relative">
    <button id="notifBellBtn" class="relative p-2 text-gray-600 hover:text-indigo-600 focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
       
        @if($notifications->count() > 0)
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full transform translate-x-1/3 -translate-y-1/3">
                {{ $totalUnreadCount > 99 ? '99+' : $totalUnreadCount }}
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

        <div class="max-h-72 overflow-y-auto">
            @forelse($notifications as $notification)
                @php
                    $type = $notification->data['anomaly_type'] ?? 'default';
                    $severity = $notification->data['severity_tier'] ?? 'low';

                    $iconMap = [
                        'goal_achieved'          => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'savings_milestone'      => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'path' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
                        'weekly_review'          => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        'pacing_warning'         => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'path' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                        'low_allowance_threshold'=> ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                        'early_week_depletion'   => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                        'rapid_overspending'     => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                        'default'                => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        'category_concentration' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'path' => 'M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z'],
                        'large_transaction'       => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'path' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
                    ];
                    $icon = $iconMap[$type] ?? $iconMap['default'];

                    // Deep link per notification type
                    switch ($type) {
                        case 'goal_achieved':
                        case 'savings_milestone':
                            $link = route('student.goals');
                            break;
                        case 'pacing_warning':
                        case 'early_week_depletion':
                        case 'rapid_overspending':
                            $link = route('student.forecast');
                            break;
                        case 'low_allowance_threshold':
                        case 'weekly_review':
                            $link = route('student.dashboard');
                            break;
                        case 'category_concentration':
                            $link = route('student.dashboard');
                            break;
                        case 'large_transaction':
                            $link = route('student.expenses.index');
                            break;
                        default:
                            $link = null;
                    }
                @endphp
                <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-50 flex gap-3 items-start">
                    <div class="h-8 w-8 rounded-full {{ $icon['bg'] }} {{ $icon['text'] }} flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon['path'] }}"></path>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs text-gray-400">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-xs text-gray-600 mb-2 leading-relaxed">{{ $notification->data['description'] ?? 'No details provided.' }}</p>
                        <div class="flex items-center gap-3">
                            @if($link)
                                <a href="{{ $link }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold">View →</a>
                            @endif
                            <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-gray-400 hover:text-indigo-600 transition-colors">
                                ✓ Mark as read
                            </button>
                            <button wire:click="dismiss('{{ $notification->id }}')" class="text-xs text-gray-400 hover:text-rose-600 transition-colors">
                                Dismiss
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-center text-sm text-gray-400">
                    Your financial tracking parameters are clear. No active anomalies logged!
                </div>
            @endforelse
        </div>

        @if($totalUnreadCount > 6)
            <div class="px-4 pt-2 border-t border-gray-100 text-center">
                <a href="{{ route('student.notifications') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">
                    View all {{ $totalUnreadCount }} notifications →
                </a>
            </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const bellBtn = document.getElementById('notifBellBtn');
        const dropdownMenu = document.getElementById('notifDropdownMenu');

        bellBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            dropdownMenu.classList.toggle('hidden');
        });

        document.addEventListener('click', function (event) {
            if (!dropdownMenu.contains(event.target) && event.target !== bellBtn) {
                dropdownMenu.classList.add('hidden');
            }
        });
       
        dropdownMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    });
</script>