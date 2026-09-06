<?php

namespace App\Http\Livewire\Admin;

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
    public function render()
    {
        $today          = Carbon::today();
        $startOfWeek    = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek      = Carbon::now()->endOfWeek(Carbon::SUNDAY);
        $startLastWeek  = $startOfWeek->copy()->subWeek();
        $endLastWeek    = $endOfWeek->copy()->subWeek();

        // ---------------------------------------------------------------
        // Stat cards
        // ---------------------------------------------------------------

        // Total registered students, with week-over-week signup growth.
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
        // (activity_logs only fires on edit/delete, not on create, so it
        // would undercount routine usage — expenses.transaction_date is the
        // more honest signal for "did someone actually use the app today".)
        $activeToday = Expense::whereDate('transaction_date', $today)
            ->distinct()
            ->count('user_id');
        $activeYesterday = Expense::whereDate('transaction_date', $today->copy()->subDay())
            ->distinct()
            ->count('user_id');
        $activeChangePct = $activeYesterday > 0
            ? round((($activeToday - $activeYesterday) / $activeYesterday) * 100, 1)
            : ($activeToday > 0 ? 100.0 : 0.0);

        // Total expenses ever logged, system-wide.
        $totalExpenses = Expense::count();

        // Budget alerts fired this calendar week. RiskDetectionService only
        // writes to risk_logs for the pacing/velocity check — low-allowance,
        // category-concentration, and large-transaction alerts only ever hit
        // the notifications table, so both sources need counting or this
        // number badly undercounts real alert volume.
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

        // Receipts the OCR pipeline successfully turned into expenses.
        $ocrProcessed = Receipt::where('status', 'processed')->count();

        // AI forecast/simulation calls. NOT YET INSTRUMENTED anywhere in
        // SpendingForecastService or WhatIfSimulator — this will read 0
        // until those services log an ActivityLog row on a successful
        // Groq call. See the note at the bottom of this file.
        $aiForecastRequests = ActivityLog::whereIn('event_type', [
            'ai_forecast_requested',
            'ai_simulation_requested',
        ])->count();

        // ---------------------------------------------------------------
        // Weekly User Registrations (rolling last 7 days, daily counts)
        // ---------------------------------------------------------------
        $registrationLabels = [];
        $registrationCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $registrationLabels[] = $date->format('D');
            $registrationCounts[] = User::where('role', 'student')
                ->whereDate('created_at', $date)
                ->count();
        }

        // ---------------------------------------------------------------
        // Expense Category Distribution (this month, excluding Savings)
        // ---------------------------------------------------------------
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

        // ---------------------------------------------------------------
        // Daily Expense Activity (last 7 days, transaction counts)
        // ---------------------------------------------------------------
        $activityLabels = [];
        $activityCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $activityLabels[] = $date->format('D');
            $activityCounts[] = Expense::whereDate('transaction_date', $date)->count();
        }

        // ---------------------------------------------------------------
        // Recent Activities feed
        // ---------------------------------------------------------------
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
        ])->layout('layouts.admin');
    }
}

/*
 * OPTIONAL: to make "AI Forecast Requests" real, add one line each to:
 *
 * 1. App\Services\SpendingForecastService::fetchAiInsight(), right after
 *    a successful Groq response (inside the `if ($response->successful())`
 *    block, before returning):
 *
 *      ActivityLog::create([
 *          'user_id'    => $user->id,
 *          'event_type' => 'ai_forecast_requested',
 *          'ip_address' => request()->ip(),
 *          'user_agent' => request()->userAgent(),
 *          'details'    => 'Spending forecast AI tips generated.',
 *      ]);
 *
 * 2. App\Http\Livewire\Student\WhatIfSimulator::generateSimulationInsight(),
 *    right after `$this->aiInsight = trim($rawText);`:
 *
 *      ActivityLog::create([
 *          'user_id'    => Auth::id(),
 *          'event_type' => 'ai_simulation_requested',
 *          'ip_address' => request()->ip(),
 *          'user_agent' => request()->userAgent(),
 *          'details'    => "AI insight generated for simulated purchase: {$item}",
 *      ]);
 *
 * Both files already import/have access to what they need (User model in
 * the forecast service via the $user param, Auth facade already imported
 * in the simulator) — just add `use App\Models\ActivityLog;` to each.
 */