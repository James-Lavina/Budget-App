<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\WeeklyBudget;
use App\Models\RiskLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\BudgetCycleService;
use Carbon\Carbon;

class SpendingForecastService
{
    /**
     * Fast, local-only forecast computation. No external HTTP calls.
     * Safe to run synchronously on every page load.
     */
    public function computeLocalForecast($user)
    {
        $activeBudget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        if (!$activeBudget) {
            return ['status' => 'error', 'message' => 'No active tracking budget period established yet.'];
        }

        $cycle = app(BudgetCycleService::class)->resolve($activeBudget, $user);

        $startDate          = $cycle['startDate'];
        $endDate            = $cycle['endDate'];
        $daysElapsed        = $cycle['daysElapsed'];
        $daysRemainingCount = $cycle['daysRemaining'];
        $spentTodayDate     = $cycle['spentTodayDate'];
        $targetResetDay     = $cycle['targetResetDay'];

        $baseAllowance      = (float) ($activeBudget->total_allowance ?? 1000.00);
        $remainingAllowance = (float) ($activeBudget->remaining_allowance ?? 0.00);

        $spentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $spentTodayDate)
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($query) {
                $query->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        $startingBudgetForRemainingDays = $remainingAllowance + $spentToday;
        $todayStartingQuota             = $startingBudgetForRemainingDays / $daysRemainingCount;
        $safeToSpendToday               = max(0.00, $todayStartingQuota - $spentToday);

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

        for ($i = 0; $i < 7; $i++) {
            $currentDate = $startDate->copy()->addDays($i);
            $dateKey     = $currentDate->format('Y-m-d');
            $labels[]    = $currentDate->format('D');
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

        \Log::info('FORECAST_DEBUG', [
            'budget_id'        => $activeBudget->id,
            'startDate'        => $startDate->toDateTimeString(),
            'endDate'          => $endDate->toDateTimeString(),
            'evalDate'         => $cycle['evalDate']->toDateTimeString(),
            'daysElapsed'      => $daysElapsed,
            'daysRemainingCount' => $daysRemainingCount,
            'totalSpent'       => $totalSpent,
            'dailyVelocity'    => $dailyVelocity,
            'remainingAllowance' => $remainingAllowance,
        ]);

        $effectiveTotalAllowance = max($baseAllowance, $remainingAllowance + $totalSpent);

        for ($i = 0; $i < 7; $i++) {
            $dayIndex = $i + 1;

            if ($daysElapsed === 7) {
                $predictedValues[] = null;
                continue;
            }

            if ($dayIndex < $daysElapsed) {
                $predictedValues[] = null;
            } elseif ($dayIndex === $daysElapsed) {
                $predictedValues[] = $actualValues[$daysElapsed - 1] ?? 0;
            } else {
                $daysAhead          = $dayIndex - $daysElapsed;
                $predictedValues[]  = round($totalSpent + ($dailyVelocity * $daysAhead), 2);
            }
        }

        $futureProjectedSpent = $dailyVelocity * $daysRemainingCount;
        $rawPredictedRemaining    = $remainingAllowance - $futureProjectedSpent;
        $predictedRemainingBudget = max(0, $rawPredictedRemaining);
        $projectedDeficit         = $rawPredictedRemaining < 0 ? abs($rawPredictedRemaining) : 0;
        $predictedEndOfWeekSpent  = $totalSpent + $futureProjectedSpent;

        $isCriticalState = ($remainingAllowance <= 0) || ($predictedEndOfWeekSpent > $effectiveTotalAllowance);
        $isFasterPacing  = !$isCriticalState && ($spentToday > $todayStartingQuota);

        // NOTE: Risk logging/notifications are no longer created here.
        // RiskDetectionService is the single source of truth for that — see
        // SpendingForecast::mount(), which calls it once before rendering.
        // This avoids the two services independently writing conflicting
        // RiskLog rows / duplicate notifications for the same event.

        $riskLogsCountThisWeek = RiskLog::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        $metrics = [
            'daily_velocity'      => number_format($dailyVelocity, 2),
            'total_allowance'     => number_format($effectiveTotalAllowance, 2),
            'remaining_allowance' => number_format($remainingAllowance, 2),
            'predicted_remaining' => number_format($predictedRemainingBudget, 2),
            'projected_deficit'   => number_format($projectedDeficit, 2),
            'predicted_end_spent' => number_format($predictedEndOfWeekSpent, 2),
            'is_critical'         => $isCriticalState,
            'is_faster'           => $isFasterPacing,
            'days_left_in_week'   => $daysRemainingCount,
            'active_risks_count'  => $riskLogsCountThisWeek,
            'reset_day'           => $targetResetDay, // used for dynamic "Sunday" text
        ];

        return [
            'status'  => 'success',
            'metrics' => $metrics,
            'chart'   => [
                'labels'    => $labels,
                'actual'    => $actualValues,
                'predicted' => $predictedValues,
                'allowance' => $effectiveTotalAllowance,
            ],
            // used by fetchAiInsight() so it doesn't need to recompute anything
            'raw' => [
                'daily_velocity'       => $dailyVelocity,
                'effective_allowance'  => $effectiveTotalAllowance,
                'remaining_allowance'  => $remainingAllowance,
                'total_spent'          => $totalSpent,
                'predicted_remaining'  => $predictedRemainingBudget,
                'safe_to_spend_today'  => $safeToSpendToday,
                'days_elapsed'         => $daysElapsed,
                'budget_id'            => $activeBudget->id,
                'budget_updated_at'    => optional($activeBudget->updated_at)->timestamp,
            ],
        ];
    }

    /**
     * Slow path: calls Groq for the 3 coaching tips. Cached per-budget so a
     * student re-visiting the page repeatedly doesn't re-trigger an API call
     * for advice that hasn't meaningfully changed.
     */
    public function fetchAiInsight($user, array $localForecast)
    {
        if (($localForecast['status'] ?? '') !== 'success') {
            return ['is_online' => false, 'ai_coach_text' => '', 'source' => 'Local Rule Module (Offline)'];
        }

        $raw     = $localForecast['raw'];
        $metrics = $localForecast['metrics'];

        // Cache key: changes only when the budget row changes or the rounded
        // remaining balance shifts by more than a peso — keeps advice fresh
        // without re-calling the API on every single page visit.
        $cacheKey = sprintf(
            'forecast_ai:%d:%d:%d:%d',
            $user->id,
            $raw['budget_id'],
            $raw['budget_updated_at'],
            round($raw['remaining_allowance'])
        );

        return Cache::remember($cacheKey, now()->addHour(), function () use ($raw, $metrics) {
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
                                'content' => "Allowance limit: PHP {$raw['effective_allowance']}. Current money left: PHP {$raw['remaining_allowance']}. Spent so far: PHP {$raw['total_spent']}. Daily pace: PHP {$raw['daily_velocity']}/day. Estimated cash left by {$metrics['reset_day']}: PHP {$raw['predicted_remaining']}. Safe spending limit for today: PHP {$raw['safe_to_spend_today']}."
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
                            'is_online'     => true,
                            'ai_coach_text' => $aiText,
                            'source'        => 'Groq AI Predictive Engine',
                        ];
                    }
                }

                return $this->buildOfflineAdvice($metrics, $raw);
            } catch (\Exception $e) {
                Log::warning("Spending Forecast AI API offline: " . $e->getMessage());
                return $this->buildOfflineAdvice($metrics, $raw);
            }
        });
    }

    private function buildOfflineAdvice(array $metrics, array $raw)
    {
        // FIX: use the already-correct days_left_in_week from local metrics
        // instead of recalculating from $daysElapsed, which produced a
        // different (off-by-one) number when the cycle was fast-forwarded.
        $remainingDays = $metrics['days_left_in_week'];
        $resetDay      = $metrics['reset_day'];

        if ($metrics['is_critical']) {
            $fallbackAdvice = "Pace Warning! Consider trimming non-essential spending over the next {$remainingDays} day(s).|" .
                             "Aim for a daily cap of ₱" . number_format($raw['safe_to_spend_today'], 0) . " to restore safe weekly pacing.|" .
                             "Defer non-essential purchases until next week's reset.";
        } elseif ($metrics['is_faster']) {
            $fallbackAdvice = "Your current spending rate (₱{$metrics['daily_velocity']}/day) is slightly ahead of pace.|" .
                             "Slowing down now protects your projected ₱{$metrics['predicted_remaining']} {$resetDay} balance.|" .
                             "Weekend expenses usually rise—keep a small buffer ready.";
        } else {
            $fallbackAdvice = "Great job! Your spending pace is comfortably within safe limits.|" .
                             "You are on track to finish {$resetDay} with about ₱{$metrics['predicted_remaining']} left.|" .
                             "Maintain your current spending habits through the rest of the week.";
        }

        return [
            'is_online'     => false,
            'ai_coach_text' => $fallbackAdvice,
            'source'        => 'Local Rule Module (Offline)',
        ];
    }
}