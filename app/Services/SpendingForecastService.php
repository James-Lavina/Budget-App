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

        // 1. Force cycle window strictly to current calendar week (Monday to Sunday)
        $now       = Carbon::now();
        $startDate = $now->copy()->startOfWeek(Carbon::MONDAY);
        $endDate   = $now->copy()->endOfWeek(Carbon::SUNDAY);
        $today     = Carbon::today();

        // 2. Compute Days Elapsed & Remaining within the Mon-Sun week
        $daysElapsed         = max(1, min(7, (int)$startDate->diffInDays($today) + 1));
        $remainingDaysInWeek = 7 - $daysElapsed;
        $daysRemainingCount  = max(1, $remainingDaysInWeek + 1);

        $totalAllowance     = (float) ($activeBudget->total_allowance ?? 1000.00);
        $remainingAllowance = (float) ($activeBudget->remaining_allowance ?? 0.00);

        // 3. Compute Daily Safe-to-Spend quota (excluding savings transfers)
        $spentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $today)
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $startingBudgetForRemainingDays = $remainingAllowance + $spentToday;
        $todayStartingQuota             = $startingBudgetForRemainingDays / $daysRemainingCount;
        $safeToSpendToday               = max(0.00, $todayStartingQuota - $spentToday);

        // 4. Group Cycle Expenses Day-by-Day (Excluding savings goals & savings category)
        $expensesByDay = Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->get()
            ->groupBy(function ($expense) {
                return Carbon::parse($expense->transaction_date)->format('Y-m-d');
            });

        $labels          = [];
        $actualValues    = [];
        $predictedValues = [];
        $runningSpent    = 0;

        // 5. Calculate Actual Cumulative Spending (Monday through Sunday)
        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateKey     = $currentDate->format('Y-m-d');
            $labels[]    = $currentDate->format('D'); // Mon, Tue, Wed, Thu, Fri, Sat, Sun
            $dayIndex    = $i + 1;

            if ($dayIndex <= $daysElapsed) {
                $daySpent       = $expensesByDay->get($dateKey, collect())->sum('amount');
                $runningSpent  += $daySpent;
                $actualValues[] = round($runningSpent, 2);
            } else {
                $actualValues[] = null;
            }
        }

        $totalSpent    = $runningSpent;
        $dailyVelocity = $daysElapsed > 0 ? ($totalSpent / $daysElapsed) : 0;

        // 6. Project Spending Trajectory for Chart
        for ($i = 0; $i < 7; $i++) {
            $dayIndex = $i + 1;

            if ($daysElapsed === 7) {
                $predictedValues[] = null;
                continue;
            }

            if ($dayIndex < $daysElapsed) {
                $predictedValues[] = null;
            } elseif ($dayIndex === $daysElapsed) {
                // Bridge point connecting actual to predicted trajectory
                $predictedValues[] = $actualValues[$daysElapsed - 1] ?? 0;
            } else {
                $daysAhead          = $dayIndex - $daysElapsed;
                $predictedValues[]  = round($totalSpent + ($dailyVelocity * $daysAhead), 2);
            }
        }

        // 7. Anchor predicted remaining budget directly to live `remaining_allowance` from DB
        $futureProjectedSpent     = $dailyVelocity * $remainingDaysInWeek;
        $rawPredictedRemaining    = $remainingAllowance - $futureProjectedSpent;
        $predictedRemainingBudget = max(0, $rawPredictedRemaining);
        $projectedDeficit         = $rawPredictedRemaining < 0 ? abs($rawPredictedRemaining) : 0;
        $predictedEndOfWeekSpent  = $totalSpent + $futureProjectedSpent;

        // 8. Synchronized Risk & Pacing Triggers
        $isCriticalState = ($remainingAllowance <= 0) || ($predictedEndOfWeekSpent > $totalAllowance);
        $isFasterPacing  = !$isCriticalState && ($spentToday > $todayStartingQuota);

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
                    'description'   => "Over Budget Risk: Average daily pace of ₱" . number_format($dailyVelocity, 2) . "/day will strain balance.",
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
            'remaining_allowance' => number_format($remainingAllowance, 2),
            'predicted_remaining' => number_format($predictedRemainingBudget, 0),
            'projected_deficit'   => number_format($projectedDeficit, 0),
            'predicted_end_spent' => number_format($predictedEndOfWeekSpent, 2),
            'is_critical'         => $isCriticalState,
            'is_faster'           => $isFasterPacing,
            'days_left_in_week'   => $remainingDaysInWeek,
            'active_risks_count'  => $riskLogsCountThisWeek,
        ];

        try {
            $apiKey = env('GROQ_API_KEY');
            if (empty($apiKey)) {
                throw new \Exception('GROQ API key missing.');
            }

            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json'
                ])->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a supportive, money-savvy student peer. Use plain, friendly student terms (never use technical words like "velocity", "deficit", "trajectory", or "anomaly"). Provide exactly 3 short, actionable tips based on their daily spending pace. Separate each statement ONLY with a pipe character (|). Do not use hyphens, dashes, bullet points, or markdown bolding. Keep each point under 15 words.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Allowance limit: PHP {$totalAllowance}. Current money left: PHP {$remainingAllowance}. Spent so far: PHP {$totalSpent}. Daily pace: PHP {$dailyVelocity}/day. Estimated cash left by Sunday: PHP {$predictedRemainingBudget}. Safe spending limit for today: PHP {$safeToSpendToday}."
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $rawContent = $response->json()['choices'][0]['message']['content'] ?? '';
                if (!str_contains($rawContent, '|')) {
                    $rawContent = preg_replace('/[\r\n]+/', '|', $rawContent);
                    $rawContent = preg_replace('/(?<=\.|\!|\?)\s*-\s*/', '|', $rawContent);
                }
                $cleanedTips = array_filter(array_map(function ($item) {
                    return trim(preg_replace('/^[\s\-\*•\d\.\)]+/', '', $item));
                }, explode('|', $rawContent)));

                $aiText = implode('|', $cleanedTips);

                if (!empty($aiText)) {
                    return [
                        'status'        => 'success',
                        'is_online'     => true,
                        'metrics'       => $localForecastMetrics,
                        'ai_coach_text' => $aiText,
                        'source'        => 'Groq AI Predictive Engine',
                        'chart'         => [
                            'labels'    => $labels,
                            'actual'    => $actualValues,
                            'predicted' => $predictedValues,
                            'allowance' => $totalAllowance
                        ]
                    ];
                }
            }

            return $this->buildOfflineResponse($localForecastMetrics, $activeBudget, $daysElapsed, $labels, $actualValues, $predictedValues, $totalAllowance, $safeToSpendToday);

        } catch (\Exception $e) {
            Log::warning("Spending Forecast AI API offline: " . $e->getMessage());
            return $this->buildOfflineResponse($localForecastMetrics, $activeBudget, $daysElapsed, $labels, $actualValues, $predictedValues, $totalAllowance, $safeToSpendToday);
        }
    }

    private function buildOfflineResponse($metrics, $activeBudget, $daysElapsed, $labels, $actualValues, $predictedValues, $totalAllowance, $safeDaily = 0)
    {
        $remainingDays = 7 - $daysElapsed;
        
        if ($metrics['is_critical']) {
            $fallbackAdvice = "Over budget risk! Limit non-essential spending over the next {$remainingDays} day(s).|" .
                             "Cap daily spending to ₱" . number_format($safeDaily, 0) . " to restore safe weekly pacing.|" .
                             "Defer planned non-essential purchases until next week.";
        } elseif ($metrics['is_faster']) {
            $fallbackAdvice = "Your current spending rate (₱{$metrics['daily_velocity']}/day) is slightly ahead of pace.|" .
                             "Slowing down now protects your projected ₱{$metrics['predicted_remaining']} weekend balance.|" .
                             "Weekend spending usually rises—budget extra discretionary expenses carefully.";
        } else {
            $fallbackAdvice = "Great job! Your spending pace is comfortably within safe limits.|" .
                             "You are on track to finish Sunday with about ₱{$metrics['predicted_remaining']} left.|" .
                             "Maintain your current spending habits through the rest of the week.";
        }

        return [
            'status'        => 'success',
            'is_online'     => false,
            'metrics'       => $metrics,
            'ai_coach_text' => $fallbackAdvice,
            'source'        => 'Local Rule Module (Offline)',
            'chart'         => [
                'labels'    => $labels,
                'actual'    => $actualValues,
                'predicted' => $predictedValues,
                'allowance' => $totalAllowance
            ]
        ];
    }
}