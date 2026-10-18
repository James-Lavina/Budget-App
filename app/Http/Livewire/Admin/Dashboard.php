<?php

namespace App\Http\Livewire\Admin;

use App\Http\Livewire\Concerns\EnsuresAdmin;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\Receipt;
use App\Models\RiskLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    use EnsuresAdmin;

    public function statusFor(string $eventType): array
    {
        if (strpos($eventType, 'failed') !== false) {
            return ['label' => 'Failed', 'class' => 'bg-rose-50 text-rose-600'];
        }

        return ['label' => 'Success', 'class' => 'bg-emerald-50 text-emerald-700'];
    }

    public function render()
    {
        $today          = Carbon::today();
        $startOfWeek    = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek      = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $startLastWeek  = $startOfWeek->copy()->subWeek();
        $endLastWeek    = $endOfWeek->copy()->subWeek();

        $totalUsers   = User::where('role', 'student')->count();

        $newThisWeek  = User::where('role', 'student')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $newLastWeek  = User::where('role', 'student')
            ->whereBetween('created_at', [$startLastWeek, $endLastWeek])
            ->count();

        $userGrowthPct = $newLastWeek > 0
            ? round((($newThisWeek - $newLastWeek) / $newLastWeek) * 100, 1)
            : ($newThisWeek > 0 ? 100.0 : 0.0);

        // "Active today" = distinct students who logged a real expense today.
        $activeToday = Expense::whereDate('transaction_date', $today)
            ->distinct()
            ->count('user_id');

        $activeYesterday = Expense::whereDate('transaction_date', $today->copy()->subDay())
            ->distinct()
            ->count('user_id');

        $activeChangePct = $activeYesterday > 0
            ? round((($activeToday - $activeYesterday) / $activeYesterday) * 100, 1)
            : ($activeToday > 0 ? 100.0 : 0.0);

        $totalExpenses = Expense::count();

        // Alerts are split across risk_logs and notification-only types, so count both.
        $riskLogAlerts = RiskLog::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

        $notificationAlerts = DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\User')
            ->whereIn('type', [
                'App\\Notifications\\LowAllowanceWarning',
                'App\\Notifications\\CategoryConcentrationWarning',
                'App\\Notifications\\LargeTransactionAlert',
            ])
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->count();

        $weeklyAlerts = $riskLogAlerts + $notificationAlerts;

        $ocrProcessed = Receipt::where('status', 'processed')->count();

        $aiForecastCount = ActivityLog::where('event_type', 'ai_forecast_requested')->count();
        $aiSimulationCount = ActivityLog::where('event_type', 'ai_simulation_requested')->count();
        $aiForecastRequests = $aiForecastCount + $aiSimulationCount;

        $registrationLabels = [];
        $registrationCounts = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $registrationLabels[] = $date->format('D');
            $registrationCounts[] = User::where('role', 'student')
                ->whereDate('created_at', $date)
                ->count();
        }

        $categoryRows = Expense::join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereMonth('transaction_date', $today->month)
            ->whereYear('transaction_date', $today->year)
            ->where('expense_categories.name', 'NOT LIKE', '%Savings%')
            ->select('expense_categories.name', DB::raw('SUM(expenses.amount) as total'))
            ->groupBy('expense_categories.name')
            ->orderByDesc('total')
            ->take(4)
            ->get();

        $categoryGrandTotal = $categoryRows->sum('total');

        $categoryDistribution = $categoryRows->map(function ($row) use ($categoryGrandTotal) {
            return [
                'name'       => $row->name,
                'percentage' => $categoryGrandTotal > 0
                    ? (int) round(($row->total / $categoryGrandTotal) * 100)
                    : 0,
            ];
        })->values();

        $activityLabels = [];
        $activityCounts = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $activityLabels[] = $date->format('D');
            $activityCounts[] = Expense::whereDate('transaction_date', $date)->count();
        }

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(8)
            ->get();

        return view('livewire.admin.dashboard', [
            'totalUsers'            => $totalUsers,
            'userGrowthPct'         => $userGrowthPct,
            'activeToday'           => $activeToday,
            'activeChangePct'       => $activeChangePct,
            'totalExpenses'         => $totalExpenses,
            'weeklyAlerts'          => $weeklyAlerts,
            'ocrProcessed'          => $ocrProcessed,
            'aiForecastRequests'    => $aiForecastRequests,
            'registrationLabels'    => $registrationLabels,
            'registrationCounts'    => $registrationCounts,
            'categoryDistribution'  => $categoryDistribution,
            'activityLabels'        => $activityLabels,
            'activityCounts'        => $activityCounts,
            'recentActivities'      => $recentActivities,
            'aiForecastCount'       => $aiForecastCount,
            'aiSimulationCount'     => $aiSimulationCount,
        ])->layout('layouts.admin');
    }
}