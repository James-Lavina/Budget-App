<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
use App\Notifications\BudgetRiskNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SpendingForecastService
{
    public function generateForecast($user)
    {
        $activeBudget = WeeklyBudget::where('user_id', $user->id)->latest()->first();
        if (!$activeBudget) {
            return ['status' => 'error', 'message' => 'No active tracking budget period established yet.'];
        }

        // Force cycle to always align Monday to Sunday
        $startDate = Carbon::parse($activeBudget->cycle_start_date)->startOfWeek(Carbon::MONDAY);
        $today = Carbon::today();

        // Calculate days elapsed (1 to 7)
        $daysElapsed = min(7, max(1, (int)$startDate->diffInDays($today) + 1));
        $totalAllowance = (float) ($activeBudget->total_allowance ?? 1000.00);

        // Fetch expenses grouped by day
        $expensesByDay = Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $startDate->copy()->addDays(6))
            ->get()
            ->groupBy(function ($expense) {
                return Carbon::parse($expense->transaction_date)->format('Y-m-d');
            });

        $labels = [];
        $actualValues = [];
        $predictedValues = [];
        $runningSpent = 0;

        // 1. Calculate Actual Cumulative Spending (Mon -> Sun)
        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateKey = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('D'); // Mon, Tue, Wed, Thu, Fri, Sat, Sun

            $dayIndex = $i + 1;

            if ($dayIndex <= $daysElapsed) {
                $daySpent = $expensesByDay->get($dateKey, collect())->sum('amount');
                $runningSpent += $daySpent;
                $actualValues[] = round($runningSpent, 2);
            } else {
                $actualValues[] = null;
            }
        }

        $totalSpent = $runningSpent;
        $dailyVelocity = $daysElapsed > 0 ? ($totalSpent / $daysElapsed) : 0;

        // 2. Project Spending from Today through Sunday
        for ($i = 0; $i < 7; $i++) {
            $dayIndex = $i + 1;
            
            // If it's Sunday (Day 7), we don't need a prediction line since the week is complete
            if ($daysElapsed === 7) {
                $predictedValues[] = null;
                continue;
            }

            if ($dayIndex < $daysElapsed) {
                $predictedValues[] = null;
            } elseif ($dayIndex === $daysElapsed) {
                // Bridge point connecting actual to predicted
                $predictedValues[] = $actualValues[$daysElapsed - 1] ?? 0;
            } else {
                $daysAhead = $dayIndex - $daysElapsed;
                $predictedValues[] = round($totalSpent + ($dailyVelocity * $daysAhead), 2);
            }
        }

        $predictedEndOfWeekSpent = end($predictedValues) ?: $totalSpent;
        $predictedRemainingBudget = max(0, $totalAllowance - $predictedEndOfWeekSpent);

        $isCriticalState = $predictedEndOfWeekSpent > $totalAllowance;
        $isFasterPacing = !$isCriticalState && ($dailyVelocity > ($totalAllowance / 7));

        if ($isCriticalState) {
            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
                ->where('created_at', '>=', Carbon::today())
                ->where('description', 'LIKE', '%Deficit Risk%')
                ->exists();

            if (!$alreadyLoggedToday) {
                $riskLog = RiskLog::create([
                    'user_id'       => $user->id,
                    'anomaly_type'  => 'early_week_depletion',
                    'severity_tier' => 'high',                
                    'description'   => "Deficit Risk: Daily spend rate of ₱" . number_format($dailyVelocity, 2) . "/day will exceed allowance by Sunday.",
                    'resolved'      => false,
                ]);
                $user->notify(new BudgetRiskNotification($riskLog));
            }
        }

        $riskLogsCountThisWeek = RiskLog::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $localForecastMetrics = [
            'daily_velocity'      => number_format($dailyVelocity, 2),
            'total_allowance'     => number_format($totalAllowance, 2),
            'predicted_remaining' => number_format($predictedRemainingBudget, 0),
            'predicted_end_spent' => number_format($predictedEndOfWeekSpent, 2),
            'is_critical'          => $isCriticalState,
            'is_faster'            => $isFasterPacing,
            'days_left_in_week'   => 7 - $daysElapsed,
            'active_risks_count'  => $riskLogsCountThisWeek,
        ];

        try {
            $response = Http::timeout(6)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type'  => 'application/json'
                ])->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => env('GROQ_MODEL'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a supportive, money-savvy student peer. Provide 3 short, actionable bullet advice statements for a student based on their weekly spending trend. Keep each point under 15 words. Do not use markdown bolding.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Allowance limit: PHP {$totalAllowance}. Spent so far: PHP {$totalSpent}. Speed: PHP {$dailyVelocity}/day. Projected end-of-week remaining: PHP {$predictedRemainingBudget}."
                        ]
                    ]
                ]);

            $aiText = $response->successful() ? $response->json()['choices'][0]['message']['content'] : null;

            return [
                'status' => 'success',
                'metrics' => $localForecastMetrics,
                'ai_coach_text' => $aiText ?? "Keeping your daily expenses controlled ensures you finish smoothly within budget.|Weekend activities usually cost more—plan ahead to save ~₱200.|You have " . (7 - $daysElapsed) . " days left with ₱" . number_format($activeBudget->remaining_allowance, 0) . " remaining.",
                'source' => $response->successful() ? 'Groq AI Predictive Engine' : 'Local Backup Framework',
                'chart' => [
                    'labels'    => $labels,
                    'actual'    => $actualValues,
                    'predicted' => $predictedValues,
                    'allowance' => $totalAllowance
                ]
            ];
        } catch (\Exception $e) {
            Log::error("Spending Forecast Service Exception: " . $e->getMessage());
            $fallbackAdvice = "Pacing your daily expenses keeps you comfortably within budget.|Weekend spending tends to rise—keep an eye on casual purchases.|You are on track to end Sunday with ₱" . $localForecastMetrics['predicted_remaining'] . " remaining.";
            
            return [
                'status' => 'success',
                'metrics' => $localForecastMetrics,
                'ai_coach_text' => $fallbackAdvice,
                'source' => 'Local Statistical Module (System Offline)',
                'chart' => [
                    'labels'    => $labels,
                    'actual'    => $actualValues,
                    'predicted' => $predictedValues,
                    'allowance' => $totalAllowance
                ]
            ];
        }
    }
}