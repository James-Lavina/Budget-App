<div class="max-w-5xl mx-auto space-y-6 px-4 sm:px-6 lg:px-8">
    {{-- Header --}}
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

    {{-- Notification List --}}
    <div class="bg-white rounded-3xl border border-slate-200/70 shadow-sm overflow-hidden">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $isUnread = is_null($notification->read_at);
                // NEW: surfaces the 'resolved' flag RiskDetectionService now
                // maintains on low_allowance_threshold, pacing/velocity,
                // category_concentration, overspending_threshold,
                // rapid_spending, no_expense_logs, and large_transaction
                // notifications. Notifications created before that flag
                // existed simply won't have this key, so they fall through
                // to "not resolved" (i.e. display unchanged) — expected,
                // not a bug.
                $isResolved = $data['resolved'] ?? false;
                $severity = $data['severity_tier'] ?? 'info';
                $type = $data['anomaly_type'] ?? 'default';

                $iconMap = [
                    'goal_achieved'           => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'path' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'savings_milestone'       => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'path' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
                    'weekly_review'           => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    'pacing_warning'          => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600', 'path' => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                    'low_allowance_threshold' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                    'early_week_depletion'    => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                    'rapid_overspending'      => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                    'default'                 => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'path' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'category_concentration' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700', 'path' => 'M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z'],
                    'large_transaction'       => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'path' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'],
                    // NEW: Risk Detection Rules — Overspending Threshold
                    'overspending_threshold'  => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'path' => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
                    // NEW: Risk Detection Rules — Rapid Spending Detection
                    'rapid_spending'          => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'path' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                    // NEW: Risk Detection Rules — Consecutive No Expense Logs
                    'no_expense_logs'         => ['bg' => 'bg-slate-100', 'text' => 'text-slate-500', 'path' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    'daily_safe_to_spend' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600', 'path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                ];

                $icon = $iconMap[$type] ?? $iconMap['default'];

                // NEW: resolved notifications get their icon muted to slate,
                // regardless of type — visually signals "this no longer
                // applies" at a glance, distinct from the colorful "still
                // active" icons above.
                if ($isResolved) {
                    $icon = ['bg' => 'bg-slate-100', 'text' => 'text-slate-400', 'path' => $icon['path']];
                }

                switch ($type) {
                    case 'goal_achieved':
                    case 'savings_milestone':
                        $link = route('student.goals');
                        break;
                    case 'pacing_warning':
                    case 'early_week_depletion':
                    case 'rapid_overspending':
                    case 'overspending_threshold':
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
                    case 'rapid_spending':
                        $link = route('student.expenses.index');
                        break;
                    case 'no_expense_logs':
                        $link = route('student.expenses.create');
                        break;
                    case 'daily_safe_to_spend':
                        $link = route('student.dashboard');
                        break;
                    default:
                        $link = null;
                }
            @endphp

            <div class="p-5 border-b border-slate-100 last:border-b-0 transition-colors flex items-start justify-between gap-4 {{ $isResolved ? 'opacity-60' : ($isUnread ? 'bg-indigo-50/30' : 'hover:bg-slate-50/60') }}">
                <div class="flex items-start gap-4">
                    {{-- Icon (mapped by notification type, muted when resolved) --}}
                    <div class="shrink-0 p-3 rounded-2xl mt-0.5 {{ $icon['bg'] }} {{ $icon['text'] }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon['path'] }}"/>
                        </svg>
                    </div>

                    {{-- Content --}}
                    <div class="space-y-1">
                        <p class="text-sm font-medium leading-relaxed {{ $isResolved ? 'text-slate-500' : 'text-slate-800' }}">
                            {{ $data['description'] ?? ($data['message'] ?? ($data['title'] ?? 'System notification received.')) }}
                        </p>
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-xs font-semibold text-slate-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                            @if($isResolved)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide bg-emerald-50 text-emerald-600 border border-emerald-100">
                                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                    </svg>
                                    Resolved
                                </span>
                            @endif
                            @if($link && !$isResolved)
                                <a href="{{ $link }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">View →</a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-1 shrink-0">
                    @if($isUnread)
                        <button
                            wire:click="markAsRead('{{ $notification->id }}')"
                            title="Mark as read"
                            class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    @endif
                    <button
                        wire:click="delete('{{ $notification->id }}')"
                        title="Delete"
                        class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-14v4M1 7h22"/>
                        </svg>
                    </button>
                </div>
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

    {{-- Pagination --}}
    <div class="pt-2">
        {{ $notifications->links() }}
    </div>
</div>