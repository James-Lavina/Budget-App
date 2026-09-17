@php
    $appSettings = \App\Models\AppSetting::current();

    $eventLabels = [
        'expense_logged'            => 'Logged Expense',
        'expense_scanned'           => 'Scanned Receipt',
        'expense_deleted'           => 'Deleted Expense',
        'expense_bulk_deleted'      => 'Bulk Deleted Expenses',
        'expense_edited'            => 'Updated Expense',
        'ai_forecast_requested'     => 'Requested AI Forecast',
        'ai_simulation_requested'   => 'Requested AI Simulation',
        'user_edited'               => 'Edited User',
        'user_suspended'            => 'Suspended User',
        'user_reactivated'          => 'Reactivated User',
        'user_deleted'              => 'Deleted User',
        'category_created'          => 'Created Category',
        'category_edited'           => 'Edited Category',
        'category_disabled'         => 'Disabled Category',
        'category_enabled'          => 'Enabled Category',
        'category_deleted'          => 'Deleted Category',
        'risk_rules_updated'        => 'Updated Risk Rules',
        'ocr_ai_settings_updated'   => 'Updated OCR & AI Settings',
        'app_settings_updated'      => 'Updated Application Settings',
        'maintenance_mode_enabled'  => 'Enabled Maintenance Mode',
        'maintenance_mode_disabled' => 'Disabled Maintenance Mode',
    ];
@endphp

<div class="space-y-6" wire:poll.30s>

    {{-- Header --}}
    <div>
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Dashboard</h1>
        <p class="text-sm text-slate-500 font-medium mt-1">Overview of {{ \App\Models\AppSetting::current()->application_name }} system activity.</p>
    </div>

    {{-- STAT CARDS --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">

        {{-- Total Registered Users --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm space-y-3">
            <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-8a4 4 0 11-8 0 4 4 0 018 0zm6 3a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 block">Total Registered Users</span>
                <div class="flex items-baseline gap-1.5 mt-0.5">
                    <span class="text-xl font-black text-slate-900 font-mono">{{ number_format($totalUsers) }}</span>
                </div>
                <span class="text-[10px] font-bold {{ $userGrowthPct >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">
                    {{ $userGrowthPct >= 0 ? '+' : '' }}{{ $userGrowthPct }}%
                </span>
            </div>
        </div>

        {{-- Active Users Today --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm space-y-3">
            <div class="h-9 w-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 block">Active Users Today</span>
                <div class="flex items-baseline gap-1.5 mt-0.5">
                    <span class="text-xl font-black text-slate-900 font-mono">{{ number_format($activeToday) }}</span>
                </div>
                <span class="text-[10px] font-bold {{ $activeChangePct >= 0 ? 'text-emerald-600' : 'text-rose-500' }}">
                    {{ $activeChangePct >= 0 ? '+' : '' }}{{ $activeChangePct }}%
                </span>
            </div>
        </div>

        {{-- Total Expenses Recorded --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm space-y-3">
            <div class="h-9 w-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 block">Total Expenses Recorded</span>
                <div class="flex items-baseline gap-1.5 mt-0.5">
                    <span class="text-xl font-black text-slate-900 font-mono">{{ number_format($totalExpenses) }}</span>
                </div>
            </div>
        </div>

        {{-- Weekly Budget Alerts --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm space-y-3">
            <div class="h-9 w-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 block">Weekly Budget Alerts</span>
                <div class="flex items-baseline gap-1.5 mt-0.5">
                    <span class="text-xl font-black text-slate-900 font-mono">{{ number_format($weeklyAlerts) }}</span>
                </div>
            </div>
        </div>

        {{-- OCR Scans Processed --}}
        <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm space-y-3">
            <div class="h-9 w-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3" stroke-width="2"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 block">OCR Scans Processed</span>
                <div class="flex items-baseline gap-1.5 mt-0.5">
                    <span class="text-xl font-black text-slate-900 font-mono">{{ number_format($ocrProcessed) }}</span>
                </div>
            </div>
        </div>

        {{-- AI Forecast Requests --}}
        {{-- <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm space-y-3">
            <div class="h-9 w-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <span class="text-[11px] font-semibold text-slate-500 block">AI Forecast Requests</span>
                <div class="flex items-baseline gap-1.5 mt-0.5">
                    <span class="text-xl font-black text-slate-900 font-mono">{{ number_format($aiForecastRequests) }}</span>
                </div>
                @if($aiForecastRequests === 0)
                    <span class="text-[9px] font-semibold text-slate-400">Not instrumented yet</span>
                @endif
            </div>
        </div> --}}
    </div>

    {{-- CHART ROW --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">

        {{-- Weekly User Registrations --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Weekly User Registrations</h3>
                    <p class="text-[10px] font-semibold text-slate-400 mt-0.5">Last 7 days</p>
                </div>
            </div>
            <div class="pt-4 h-40" wire:ignore>
                <canvas id="adminRegistrationsChart"></canvas>
            </div>
        </div>

        {{-- Expense Category Distribution --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-sm font-extrabold text-slate-900">Expense Category Distribution</h3>
                <span class="text-[10px] font-semibold text-slate-400">This month</span>
            </div>
            <div class="pt-4 space-y-4">
                @forelse($categoryDistribution as $cat)
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-600">{{ $cat['name'] }}</span>
                            <span class="font-bold text-slate-900 font-mono">{{ $cat['percentage'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="h-full bg-[var(--brand-primary)] rounded-full" style="width: {{ $cat['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 font-medium py-6 text-center">No expenses logged this month yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Daily Expense Activity --}}
        <div class="bg-white rounded-2xl sm:rounded-3xl p-4 sm:p-5 border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Daily Expense Activity</h3>
                    <p class="text-[10px] font-semibold text-slate-400 mt-0.5">Last 7 days</p>
                </div>
            </div>
            <div class="pt-4 h-40" wire:ignore>
                <canvas id="adminActivityChart"></canvas>
            </div>
        </div>
    </div>

    {{-- RECENT ACTIVITIES --}}
    <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Recent Activities</h3>
                <p class="text-[11px] text-slate-400 font-medium mt-0.5">Latest system activity</p>
            </div>
            <a href="{{ route('admin.activity-logs') }}" class="text-xs font-bold text-[var(--brand-primary)] hover:opacity-80">View all logs</a>
        </div>

        @if($recentActivities->isEmpty())
            <div class="p-10 text-center">
                <p class="text-xs font-semibold text-slate-400">No activity logged yet.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="px-5 sm:px-6 py-3">User</th>
                            <th class="px-5 sm:px-6 py-3">Activity</th>
                            <th class="px-5 sm:px-6 py-3">Date</th>
                            <th class="px-5 sm:px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentActivities as $activity)
                            <tr>
                                <td class="px-5 sm:px-6 py-3.5 font-semibold text-[var(--brand-primary)]">
                                    {{ $activity->user->name ?? 'Unknown User' }}
                                </td>
                                <td class="px-5 sm:px-6 py-3.5 text-slate-700 font-medium">
                                    {{ $eventLabels[$activity->event_type] ?? \Illuminate\Support\Str::headline($activity->event_type) }}
                                </td>
                                <td class="px-5 sm:px-6 py-3.5 text-slate-500">
                                    {{ $activity->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-5 sm:px-6 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700">
                                        Success
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
    (function () {
        let registrationsChart = null;
        let activityChart = null;

        function initAdminCharts() {
            const regCanvas = document.getElementById('adminRegistrationsChart');
            const actCanvas = document.getElementById('adminActivityChart');
            if (!regCanvas || !actCanvas) return;

            if (registrationsChart) registrationsChart.destroy();
            if (activityChart) activityChart.destroy();

            registrationsChart = new Chart(regCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($registrationLabels),
                    datasets: [{
                        data: @json($registrationCounts),
                        backgroundColor: '{{ $appSettings->primary_color }}',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8' } },
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 }, color: '#94a3b8', precision: 0 } },
                    },
                },
            });

            activityChart = new Chart(actCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($activityLabels),
                    datasets: [{
                        data: @json($activityCounts),
                        backgroundColor: '#64748b',
                        borderRadius: 6,
                        maxBarThickness: 28,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#94a3b8' } },
                        y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 9 }, color: '#94a3b8', precision: 0 } },
                    },
                },
            });
        }

        document.addEventListener('livewire:load', initAdminCharts);
        document.addEventListener('livewire:update', initAdminCharts);
    })();
</script>