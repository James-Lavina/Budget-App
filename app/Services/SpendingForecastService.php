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

        $startDate = Carbon::parse($activeBudget->cycle_start_date);
        $daysElapsed = max(1, $startDate->diffInDays(Carbon::now()) + 1);

        $totalSpent = Expense::where('user_id', $user->id)
            ->where('transaction_date', '>=', $activeBudget->cycle_start_date)
            ->sum('amount');

        $actualStartingPool = $activeBudget->remaining_allowance + $totalSpent;
        $dailyVelocity = $totalSpent / $daysElapsed;
        $remainingBalance = $activeBudget->remaining_allowance;

        $projectedDaysLeft = $dailyVelocity > 0 ? ($remainingBalance / $dailyVelocity) : 7;
        $estimatedDepletionDate = Carbon::now()->addDays(floor($projectedDaysLeft))->format('M d, Y');
        
        $daysRemainingInCycle = 7 - $daysElapsed;
        $isCriticalState = $projectedDaysLeft < $daysRemainingInCycle;

        if ($isCriticalState) {
            $alreadyLoggedToday = RiskLog::where('user_id', $user->id)
                ->where('created_at', '>=', Carbon::today())
                ->where('description', 'LIKE', '%Deficit Risk%')
                ->exists();

            if (!$alreadyLoggedToday) {
                // CHANGED HERE: Captured the model in a variable and notified the user
                $riskLog = RiskLog::create([
                    'user_id'       => $user->id,
                    'anomaly_type'  => 'early_week_depletion', 
                    'severity_tier' => 'high',                 
                    'description'   => "Deficit Risk Warning: Student burn velocity is PHP " . number_format($dailyVelocity, 2) . "/day. Balance projection indicates early depletion in " . round($projectedDaysLeft, 1) . " days.",
                    'resolved'      => false,
                ]);

                $user->notify(new BudgetRiskNotification($riskLog));
            }
        }

        $riskLogsCountThisWeek = RiskLog::where('user_id', $user->id)
            ->where('created_at', '>=', $activeBudget->cycle_start_date)
            ->count();

        $localForecastMetrics = [
            'daily_velocity' => number_format($dailyVelocity, 2),
            'projected_days_left' => round($projectedDaysLeft, 1),
            'depletion_date' => $estimatedDepletionDate,
            'is_critical' => $isCriticalState,
            'active_risks_count' => $riskLogsCountThisWeek
        ];

        // NEW: Generate the linear projection points for the burn trajectory chart
        $chartLabels = [];
        $chartValues = [];
        $totalProjectedSteps = max(1, min(14, (int)ceil($projectedDaysLeft))); // Safeguard window bounds to max 14 days

        for ($i = 0; $i <= $totalProjectedSteps; $i++) {
            $chartLabels[] = Carbon::now()->addDays($i)->format('M d');
            $calculatedValue = $remainingBalance - ($dailyVelocity * $i);
            $chartValues[] = max(0, round($calculatedValue, 2));
        }

        try {
            $response = Http::timeout(6)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
                    'Content-Type' => 'application/json'
                ])->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => env('GROQ_MODEL'), 
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are a friendly, savvy upperclassman student who is great with money. Speak in a casual, supportive, peer-to-peer tone using simple language. Do not use complex financial or technical words like "velocity", "parameters", "metrics", "expenditure", or "deficit". Give exactly 2 sentences of practical advice based on their current wallet balance and budget habits. If their risk log count is high, gently but firmly remind them that they are repeating a bad spending habit this week. Do not use markdown bolding.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Total Weekly Allowance Pool: PHP {$actualStartingPool}. Left in Wallet: PHP {$remainingBalance}. Spending Speed: PHP {$dailyVelocity}/day. Days left before money runs out: {$projectedDaysLeft} days. Times they blew their budget limits this week: {$riskLogsCountThisWeek}."
                        ]
                    ]
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'metrics' => $localForecastMetrics,
                    'ai_coach_text' => $response->json()['choices'][0]['message']['content'],
                    'source' => 'Groq AI Predictive Engine',
                    'chart' => [
                        'labels' => $chartLabels,
                        'values' => $chartValues
                    ]
                ];
            }

            throw new \Exception('API response failure');

        } catch (\Exception $e) {
            Log::error("Spending Forecast Service Exception: " . $e->getMessage());

            $fallbackAdvice = "Based on how fast you are spending right now (PHP " . $localForecastMetrics['daily_velocity'] . "/day), your remaining allowance will only last about " . $localForecastMetrics['projected_days_left'] . " more days. You have triggered " . $riskLogsCountThisWeek . " budget safety alerts this week, so it might be a good time to pace your expenses.";

            return [
                'status' => 'success',
                'metrics' => $localForecastMetrics,
                'ai_coach_text' => $fallbackAdvice,
                'source' => 'Local Statistical Module (System Offline)',
                'chart' => [
                    'labels' => $chartLabels,
                    'values' => $chartValues
                ]
            ];
        }
    }
}