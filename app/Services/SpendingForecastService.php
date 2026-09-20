<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\IntegrationSetting;
use App\Models\WeeklyBudget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SpendingForecastService
{
    /**
     * Fast, local-only forecast. No external HTTP calls, safe on every page load.
     *
     * Model: money left today, minus (average daily spend x days after today).
     * "Safe pace" uses the exact Dashboard formula: (remaining + spentToday) / daysRemaining.
     */
    public function computeLocalForecast($user)
    {
        $budget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        if (!$budget) {
            return ['status' => 'error', 'message' => 'Set up your weekly budget first to see your forecast.'];
        }

        $cycle = app(BudgetCycleService::class)->resolve($budget, $user);

        $startDate     = $cycle['startDate'];
        $endDate       = $cycle['endDate'];
        $daysElapsed   = (int) $cycle['daysElapsed'];
        $daysRemaining = max(1, (int) $cycle['daysRemaining']);
        $resetDay      = $cycle['targetResetDay'];

        $remaining = (float) $budget->remaining_allowance;

        // ---- Per-day totals for this cycle -------------------------------------------------
        $cycleExpenses = Expense::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $allByDay   = []; // everything that left the budget (spending + savings transfers)
        $spendByDay = []; // real spending only

        foreach ($cycleExpenses as $e) {
            $key      = $e->transaction_date->format('Y-m-d');
            $isSaving = !is_null($e->savings_goal_id) || stripos($e->category->name ?? '', 'savings') !== false;

            $allByDay[$key] = ($allByDay[$key] ?? 0) + (float) $e->amount;

            if (!$isSaving) {
                $spendByDay[$key] = ($spendByDay[$key] ?? 0) + (float) $e->amount;
            }
        }

        // Money at the start of the cycle, reconstructed so the chart's last real point == remaining.
        $pool = $remaining + array_sum($allByDay);

        $spentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($q) {
                $q->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        // ---- Chart series: money left at the end of each day -------------------------------
        $labels     = [];
        $actual     = [];
        $totalSpent = 0.0;
        $running    = 0.0;

        for ($i = 0; $i < 7; $i++) {
            $date     = $startDate->copy()->addDays($i);
            $key      = $date->format('Y-m-d');
            $labels[] = $date->format('D');

            if ($i + 1 <= $daysElapsed) {
                $running    += $allByDay[$key] ?? 0;
                $totalSpent += $spendByDay[$key] ?? 0;
                $actual[]    = round(max(0, $pool - $running), 2);
            } else {
                $actual[] = null;
            }
        }

        // ---- Projection --------------------------------------------------------------------
        $isFinalDay = $daysElapsed >= 7;
        $pace       = $daysElapsed > 0 ? $totalSpent / $daysElapsed : 0.0;
        $futureDays = $isFinalDay ? 0 : 7 - $daysElapsed;

        $projectedSpend     = $pace * $futureDays;
        $projectedRemaining = max(0, $remaining - $projectedSpend);
        $shortfall          = max(0, $projectedSpend - $remaining);

        $projected = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $i + 1;
            if ($isFinalDay || $day < $daysElapsed) {
                $projected[] = null;
            } elseif ($day === $daysElapsed) {
                $projected[] = round($remaining, 2); // anchor so the dashed line connects
            } else {
                $projected[] = round(max(0, $remaining - $pace * ($day - $daysElapsed)), 2);
            }
        }

        // ---- Safe pace (same formula as Dashboard) -----------------------------------------
        $quota     = ($remaining + $spentToday) / $daysRemaining;
        $safeToday = max(0, $quota - $spentToday);

        // ---- When does the money run out? --------------------------------------------------
        $runsOutLabel = null;
        $shortDays    = 0;

        if (!$isFinalDay && $remaining > 0 && $pace > 0 && $projectedSpend > $remaining) {
            $covered      = (int) floor($remaining / $pace);
            $firstMissing = $daysElapsed + $covered + 1; // 1-based day index with no money
            $runsOutLabel = $startDate->copy()->addDays($firstMissing - 1)->format('l');
            $shortDays    = max(1, 8 - $firstMissing);
        }

        // ---- Single verdict ----------------------------------------------------------------
        $pctLeft = $pool > 0 ? (int) round(($projectedRemaining / $pool) * 100) : 0;

        if ($remaining <= 0) {
            $state = 'depleted';
        } elseif ($isFinalDay) {
            $state = 'final_day';
        } elseif ($totalSpent <= 0) {
            $state = 'fresh_start';
        } elseif ($projectedSpend > $remaining) {
            $state = 'runs_out';
        } elseif ($pctLeft < 15) {
            $state = 'tight';
        } else {
            $state = 'on_track';
        }

        $ctx = [
            'remaining'          => $remaining,
            'projected_remaining' => $projectedRemaining,
            'shortfall'          => $shortfall,
            'pace'               => $pace,
            'quota'              => $quota,
            'days_left'          => $daysRemaining,
            'reset_day'          => $resetDay,
            'runs_out_label'     => $runsOutLabel,
            'short_days'         => $shortDays,
            'pct_left'           => $pctLeft,
        ];

        return [
            'status'  => 'success',
            'metrics' => [
                'state'               => $state,
                'remaining'           => $remaining,
                'projected_remaining' => $projectedRemaining,
                'pace'                => $pace,
                'safe_per_day'        => $quota,
                'safe_today'          => $safeToday,
                'spent_today'         => $spentToday,
                'days_left'           => $daysRemaining,
                'is_final_day'        => $isFinalDay,
                'reset_day'           => $resetDay,
                'end_label'           => $endDate->format('l'),
                'runs_out_label'      => $runsOutLabel,
            ],
            'text'  => $this->narrative($state, $ctx),
            'chart' => [
                'labels'      => $labels,
                'actual'      => $actual,
                'projected'   => $projected,
                'pool'        => round($pool, 2),
                'today_index' => max(0, $daysElapsed - 1),
            ],
            'raw' => [
                'remaining'           => $remaining,
                'pace'                => $pace,
                'safe_per_day'        => $quota,
                'projected_remaining' => $projectedRemaining,
                'runs_out_label'      => $runsOutLabel,
                'days_left'           => $daysRemaining,
                'reset_day'           => $resetDay,
                'state'               => $state,
                'budget_id'           => $budget->id,
                'budget_updated_at'   => optional($budget->updated_at)->timestamp,
            ],
        ];
    }

    /**
     * Headline / sub-line / one concrete action. Pure local logic, works offline.
     */
    private function narrative(string $state, array $c): array
    {
        $fmt   = function ($n) {
            return '₱' . number_format($n, 2);
        };
        $days  = Str::plural('day', $c['days_left']);
        $short = Str::plural('day', $c['short_days']);

        switch ($state) {
            case 'depleted':
                return [
                    'headline' => "You've used your whole budget.",
                    'sub'      => "{$c['days_left']} {$days} left until your {$c['reset_day']} reset.",
                    'action'   => "Add funds or hold off on spending until {$c['reset_day']}.",
                ];

            case 'final_day':
                return [
                    'headline' => 'Last day — ' . $fmt($c['remaining']) . ' left.',
                    'sub'      => "Anything you don't spend rolls over to next week.",
                    'action'   => 'You can spend up to that today, or keep it for next week.',
                ];

            case 'fresh_start':
                return [
                    'headline' => 'Fresh week — nothing logged yet.',
                    'sub'      => "Log a few expenses and we'll show where your money is heading.",
                    'action'   => 'A comfortable pace is about ' . $fmt($c['quota']) . ' a day.',
                ];

            case 'runs_out':
                return [
                    'headline' => "At this pace, you'll run short on {$c['runs_out_label']}.",
                    'sub'      => "About {$c['short_days']} {$short} before your reset, roughly " . $fmt($c['shortfall']) . ' short.',
                    'action'   => 'Try to stay under ' . $fmt($c['quota']) . " a day to make it to {$c['reset_day']}.",
                ];

            case 'tight':
                return [
                    'headline' => "You'll finish with about " . $fmt($c['projected_remaining']) . '.',
                    'sub'      => "That's only {$c['pct_left']}% of your allowance — a tight finish.",
                    'action'   => 'Stay under ' . $fmt($c['quota']) . ' a day to keep a buffer.',
                ];

            default: // on_track
                return [
                    'headline' => "You're on track to finish with about " . $fmt($c['projected_remaining']) . '.',
                    'sub'      => 'Based on your average of ' . $fmt($c['pace']) . ' a day so far.',
                    'action'   => 'You can spend up to ' . $fmt($c['quota']) . ' a day and still be fine.',
                ];
        }
    }

    /**
     * Optional extras: up to 2 short AI tips. Returns no tips when offline,
     * because the page already has a complete local action. Failures are NOT cached.
     */
    public function fetchAiInsight($user, array $localForecast)
    {
        if (($localForecast['status'] ?? '') !== 'success') {
            return ['is_online' => false, 'tips' => []];
        }

        $raw      = $localForecast['raw'];
        $fallback = ['is_online' => false, 'tips' => $this->localTips($raw['state'])];

        $cacheKey = sprintf(
            'forecast_ai:v3:%d:%d:%d:%d:%s',
            $user->id,
            $raw['budget_id'],
            $raw['budget_updated_at'],
            round($raw['remaining']),
            $raw['state']
        );

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $settings = IntegrationSetting::current();
            $apiKey   = $settings->groq_api_key ?: env('GROQ_API_KEY');

            if (empty($apiKey)) {
                return $fallback;
            }

            $userPrompt = 'Money left: PHP ' . number_format($raw['remaining'], 2) . '. '
                . 'Days left in the week (including today): ' . $raw['days_left'] . '. '
                . 'Average spending so far: PHP ' . number_format($raw['pace'], 2) . ' per day. '
                . 'Safe amount per day: PHP ' . number_format($raw['safe_per_day'], 2) . '. '
                . ($raw['runs_out_label'] ? "Money is projected to run out on {$raw['runs_out_label']}. " : '')
                . "Situation: {$raw['state']}.";

            $response = Http::withToken($apiKey)
                ->timeout(6)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'      => $settings->groq_text_model,
                    'max_tokens' => 600,
                    'messages'   => [
                        [
                            'role'    => 'system',
                            'content' => 'You are a supportive, money-savvy college student peer in Maasin City, Southern Leyte, Philippines. '
                                . 'Give exactly 3 short, practical tips: one about food, one about getting around, one about a spending habit. '
                                . 'Use only things that exist in a small Philippine city: carinderias, the palengke, tricycles, habal-habal, '
                                . 'jeepneys, baon, mobile load promos, sharing photocopy costs. Never mention trains, subways, MRT, prepaid transport cards, '
                                . 'apps or services that need a big city. Do not repeat the numbers back. '
                                . 'Use plain friendly English, no technical terms. Separate the tips ONLY with a pipe character (|). '
                                . 'No hyphens, bullets or markdown. Each tip under 18 words.',
                        ],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('Spending Forecast: Groq request failed', [
                    'status' => $response->status(),
                    'body'   => Str::limit($response->body(), 300),
                ]);
                return $fallback;
            }

            $content = $response->json()['choices'][0]['message']['content'] ?? '';

            if (!str_contains($content, '|')) {
                $content = preg_replace('/[\r\n]+/', '|', $content);
            }

            $tips = array_values(array_filter(array_map(function ($item) {
                return trim(preg_replace('/^[\s\-\*•\d\.\)]+/', '', $item));
            }, explode('|', $content)), function ($tip) {
                return $tip !== '' && mb_strlen($tip) <= 160;
            }));

            if (count($tips) < 3) {
                return $fallback;
            }

            $result = ['is_online' => true, 'tips' => array_slice($tips, 0, 3)];
            Cache::put($cacheKey, $result, now()->addHour());

            return $result;
        } catch (\Exception $e) {
            Log::warning('Spending Forecast AI offline: ' . $e->getMessage());
            return $fallback;
        }
    }

    /**
     * Offline tips written for a Maasin City student. Used when the AI is unavailable.
     */
    private function localTips(string $state): array
    {
        $sets = [
            'runs_out' => [
                'Eat at carinderias or buy from the palengke instead of cafes; a rice and ulam plate goes a long way.',
                'Walk short distances and share tricycle or habal-habal fares with classmates.',
                'Hold off on non-essential buys until your reset; write down only what you truly need this week.',
            ],
            'depleted' => [
                'Bring baon from home for the rest of the week to avoid daily food spending.',
                'Walk or share tricycle rides, and skip trips you can do on another day.',
                'Ask family for help only for essentials like fare and food, and avoid extras until reset.',
            ],
            'default' => [
                'Buy rice, vegetables and fish at the palengke; cooking or bringing baon costs less than eating out.',
                'Share tricycle or habal-habal fares with classmates who go the same way.',
                'Set aside tomorrow\'s fare and food money first, then spend what is left on extras.',
            ],
        ];

        return $sets[$state] ?? $sets['default'];
    }
}