<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\IntegrationSetting;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SpendingForecastService
{
    /**
     * Fast, local-only forecast.
     *
     * Savings transfers count as money out of the budget,
     * but are excluded from actual spending pace.
     */
    public function computeLocalForecast($user)
    {
        $budget = WeeklyBudget::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$budget) {
            return [
                'status' => 'error',
                'message' => 'Set up your weekly budget first to see your forecast.'
            ];
        }

        $cycleService = app(BudgetCycleService::class);
        $cycle = $cycleService->resolve($budget, $user);

        $startDate = $cycle['startDate'];
        $endDate = $cycle['endDate'];

        $daysElapsed = (int) $cycle['daysElapsed'];
        $daysRemaining = max(1, (int) $cycle['daysRemaining']);

        $cycleLength = max(
            1,
            (int) $startDate->diffInDays($cycle['nextResetDate'])
        );

        $resetDay = $cycle['targetResetDay'];

        $remaining = max(0, (float) $budget->remaining_allowance);

        // ---------------------------------------------------------
        // Per-day, per-category, and per-item totals
        // ---------------------------------------------------------

        $cycleExpenses = Expense::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $allByDay = [];
        $byCategory = [];
        $realByCategory = [];
        $byItem = [];

        $realSpentTotal = 0.0;

        foreach ($cycleExpenses as $e) {
            $key = $e->transaction_date->format('Y-m-d');

            $amount = (float) $e->amount;

            $catName = $e->category->name ?? 'Uncategorized';

            $color = ExpenseCategory::colorToHex(
                $e->category->color ?? null
            );

            // All money leaving the allowance, including savings.
            $allByDay[$key] = ($allByDay[$key] ?? 0) + $amount;

            if (!isset($byCategory[$catName])) {
                $byCategory[$catName] = [
                    'name' => $catName,
                    'total' => 0.0,
                    'color' => $color,
                ];
            }

            $byCategory[$catName]['total'] += $amount;

            // Savings transfers are not actual spending.
            if ($e->isSavingsTransfer()) {
                continue;
            }

            $realSpentTotal += $amount;

            if (!isset($realByCategory[$catName])) {
                $realByCategory[$catName] = [
                    'name' => $catName,
                    'total' => 0.0,
                    'color' => $color,
                ];
            }

            $realByCategory[$catName]['total'] += $amount;

            $itemKey = Str::lower(trim($e->item_name));

            if (!isset($byItem[$itemKey])) {
                $byItem[$itemKey] = [
                    'name' => trim($e->item_name),
                    'count' => 0,
                    'total' => 0.0,
                ];
            }

            $byItem[$itemKey]['count']++;
            $byItem[$itemKey]['total'] += $amount;
        }

        // Reconstruct the starting balance for the chart.
        $pool = $remaining + array_sum($allByDay);

        // Today's total outflow, including savings transfers.
        $spentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->sum('amount');

        // Today's actual spending, excluding savings transfers.
        $realSpentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->excludingSavings()
            ->sum('amount');

        // ---------------------------------------------------------
        // Actual balance series
        // ---------------------------------------------------------

        $labels = [];
        $actual = [];

        $totalOutflow = 0.0;
        $running = 0.0;

        for ($i = 0; $i < $cycleLength; $i++) {
            $date = $startDate->copy()->addDays($i);

            $key = $date->format('Y-m-d');

            $labels[] = $date->format('D');

            if ($i + 1 <= $daysElapsed) {
                $dayAll = $allByDay[$key] ?? 0;

                $running += $dayAll;
                $totalOutflow += $dayAll;

                $actual[] = round(
                    max(0, $pool - $running),
                    2
                );
            } else {
                $actual[] = null;
            }
        }

        $totalSpent = $realSpentTotal;

        $totalSaved = max(
            0.0,
            $totalOutflow - $totalSpent
        );

        // ---------------------------------------------------------
        // Projection
        // ---------------------------------------------------------

        $realSpent = $totalSpent;

        $p = $cycleService->pace(
            $cycle,
            $remaining,
            $realSpent,
            $realSpentToday,
            $totalSaved
        );

        $isFinalDay = $p['daysAfterToday'] <= 0;

        $pace = (float) $p['velocity'];

        $futureDays = (int) $p['daysAfterToday'];

        $overPace = (bool) $p['isOverPace'];

        /*
         * IMPORTANT:
         *
         * Savings transfers have already left the student's
         * spending allowance. Do not add them back here.
         */
        $spendable = $remaining;

        $projectedSpend = $pace * $futureDays;

        $projectedRemaining = max(
            0,
            $spendable - $projectedSpend
        );

        $shortfall = max(
            0,
            $projectedSpend - $spendable
        );

        // ---------------------------------------------------------
        // Projected balance series
        // ---------------------------------------------------------

        $projected = [];

        for ($i = 0; $i < $cycleLength; $i++) {
            $day = $i + 1;

            if ($isFinalDay || $day < $daysElapsed) {
                $projected[] = null;
            } elseif ($day === $daysElapsed) {
                $projected[] = round($remaining, 2);
            } else {
                $projected[] = round(
                    max(
                        0,
                        $remaining - $pace * ($day - $daysElapsed)
                    ),
                    2
                );
            }
        }

        // ---------------------------------------------------------
        // Safe spending path
        // ---------------------------------------------------------

        $safePath = [];

        $safeStep = $futureDays > 0
            ? $remaining / $futureDays
            : 0;

        for ($i = 0; $i < $cycleLength; $i++) {
            $day = $i + 1;

            if ($isFinalDay || $day < $daysElapsed) {
                $safePath[] = null;
            } elseif ($day === $daysElapsed) {
                $safePath[] = round($remaining, 2);
            } else {
                $safePath[] = round(
                    max(
                        0,
                        $remaining - $safeStep * ($day - $daysElapsed)
                    ),
                    2
                );
            }
        }

        // ---------------------------------------------------------
        // Daily limit
        // ---------------------------------------------------------

        $quota = ($remaining + $spentToday) / $daysRemaining;

        $safeToday = max(
            0,
            $quota - $spentToday
        );

        $daysAfterToday = (int) $p['daysAfterToday'];

        $advisedRate = ($safeToday < 1 && $daysAfterToday > 0)
            ? $remaining / $daysAfterToday
            : (float) ($p['paceQuota'] ?? $quota);

        // ---------------------------------------------------------
        // Run-out prediction
        // ---------------------------------------------------------

        $runsOutLabel = null;
        $runsOutIndex = null;
        $shortDays = 0;

        /*
         * Do not rely only on $overPace.
         *
         * The actual question is whether the remaining allowance
         * can cover the projected spending until reset.
         */
        $willRunOut = !$isFinalDay
            && $remaining > 0
            && $pace > 0
            && $projectedSpend > $remaining;

        if ($willRunOut) {
            // Number of complete future days the balance can cover.
            $covered = (int) floor($remaining / $pace);

            // First day when the money is expected to be insufficient.
            $firstMissing = $daysElapsed + $covered + 1;

            // Keep the predicted day inside the current cycle.
            $firstMissing = min($cycleLength, $firstMissing);

            $runsOutLabel = $startDate
                ->copy()
                ->addDays($firstMissing - 1)
                ->format('l');

            $runsOutIndex = $firstMissing - 1;

            $shortDays = max(
                1,
                ($cycleLength + 1) - $firstMissing
            );
        }

        // ---------------------------------------------------------
        // Verdict
        // ---------------------------------------------------------

        $pctLeft = $pool > 0
            ? (int) round(($projectedRemaining / $pool) * 100)
            : 0;

        $overAmount = $daysRemaining > 0
            ? max(0.0, $realSpentToday - $quota)
            : 0.0;

        if ($remaining <= 0) {
            $state = 'depleted';

        } elseif ($willRunOut) {
            $state = 'runs_out';

        } elseif ($overAmount >= 1.0) {
            $state = 'over_quota';

        } elseif ($safeToday < 1.0) {
            $state = 'quota_hit';

        } elseif ($isFinalDay) {
            $state = 'final_day';

        } elseif ($totalOutflow <= 0) {
            $state = 'fresh_start';

        } elseif ($pctLeft < 15) {
            $state = 'tight';

        } else {
            $state = 'on_track';
        }

        // ---------------------------------------------------------
        // Categories
        // ---------------------------------------------------------

        $categories = collect($byCategory)
            ->map(function ($c) use ($totalOutflow) {
                $c['total'] = round($c['total'], 2);

                $c['percent'] = $totalOutflow > 0
                    ? (int) round(
                        ($c['total'] / $totalOutflow) * 100
                    )
                    : 0;

                return $c;
            })
            ->sortByDesc('total')
            ->take(4)
            ->values()
            ->all();

        // ---------------------------------------------------------
        // Top spending category
        // ---------------------------------------------------------

        $topCategory = collect($realByCategory)
            ->map(function ($c) use ($realSpent) {
                $c['total'] = round($c['total'], 2);

                $c['percent'] = $realSpent > 0
                    ? (int) round(
                        ($c['total'] / $realSpent) * 100
                    )
                    : 0;

                return $c;
            })
            ->sortByDesc('total')
            ->values()
            ->first();

        // ---------------------------------------------------------
        // Repeated purchases
        // ---------------------------------------------------------

        $repeat = collect($byItem)
            ->filter(fn ($i) => $i['count'] >= 3)
            ->sortByDesc('total')
            ->first();

        $cut = in_array($state, ['runs_out', 'tight'], true)
            ? max(0, $pace - $advisedRate)
            : 0.0;

        // ---------------------------------------------------------
        // Active savings goal
        // ---------------------------------------------------------

        $goal = null;

        if ($state === 'on_track') {
            $goal = SavingsGoal::where('user_id', $user->id)
                ->where('status', 'active')
                ->orderByDesc('current_saved')
                ->first();
        }

        // ---------------------------------------------------------
        // Narrative context
        // ---------------------------------------------------------

        $ctx = [
            'remaining' => $remaining,
            'projected_remaining' => $projectedRemaining,
            'shortfall' => $shortfall,
            'pace' => $pace,
            'quota' => $advisedRate,
            'limit' => $quota,
            'spent_today' => $spentToday,
            'over_today' => $overAmount,
            'days_left' => $daysRemaining,
            'days_elapsed' => $daysElapsed,
            'is_final' => $isFinalDay,
            'reset_day' => $resetDay,
            'end_label' => $endDate->format('l'),
            'runs_out_label' => $runsOutLabel,
            'short_days' => $shortDays,
            'pct_left' => $pctLeft,
            'top_category' => $topCategory,
            'repeat' => $repeat,
            'cut' => $cut,
            'goal_name' => $goal->target_name ?? null,
        ];

        // ---------------------------------------------------------
        // Return forecast
        // ---------------------------------------------------------

        return [
            'status' => 'success',

            'metrics' => [
                'state' => $state,
                'remaining' => $remaining,
                'projected_remaining' => $projectedRemaining,
                'pace' => $pace,
                'safe_per_day' => $quota,
                'advised_per_day' => $advisedRate,
                'safe_today' => $safeToday,
                'spent_today' => $spentToday,
                'saved' => $totalSaved,
                'days_left' => $daysRemaining,
                'is_final_day' => $isFinalDay,
                'reset_day' => $resetDay,
                'end_label' => $endDate->format('l'),
                'runs_out_label' => $runsOutLabel,
            ],

            'text' => $this->narrative($state, $ctx),

            'advice' => $this->advice($state, $ctx),

            'categories' => $categories,

            'chart' => [
                'labels' => $labels,
                'actual' => $actual,
                'projected' => $projected,
                'safe_path' => $safePath,
                'pool' => round($pool, 2),
                'today_index' => max(0, $daysElapsed - 1),
                'runs_out_index' => $runsOutIndex,
            ],

            'raw' => [
                'remaining' => $remaining,
                'pace' => $pace,
                'safe_per_day' => $advisedRate,
                'projected_remaining' => $projectedRemaining,
                'runs_out_label' => $runsOutLabel,
                'days_left' => $daysRemaining,
                'reset_day' => $resetDay,
                'state' => $state,
                'top_category' => $topCategory,
                'repeat' => $repeat,
                'budget_id' => $budget->id,
                'budget_updated_at' => optional($budget->updated_at)->timestamp,
            ],
        ];
    }

    /**
     * Plain-language verdict.
     */
    private function narrative(string $state, array $c): array
    {
        $fmt = fn ($n) => '₱' . number_format($n, 2);

        $cap = fn ($n) => '₱' . number_format(
            floor(round($n * 100, 4)) / 100,
            2
        );

        $days = Str::plural('day', $c['days_left']);

        $short = Str::plural('day', $c['short_days']);

        switch ($state) {
            case 'depleted':
                return [
                    'headline' => 'Your budget is used up.',
                    'sub' => "{$c['days_left']} {$days} to go until your {$c['reset_day']} reset.",
                    'action' => 'Stick to essentials like food and fare until reset.',
                ];

            case 'final_day':
                return [
                    'headline' => 'Last day of the week.',
                    'sub' => "You have {$fmt($c['remaining'])} left. Whatever you don't spend rolls over to next week.",
                    'action' => 'Spend only what you need. Saving it today gives you a head start next week.',
                ];

            case 'fresh_start':
                return [
                    'headline' => 'Fresh week — nothing logged yet.',
                    'sub' => "Log your first expenses and we'll show where your money is heading.",
                    'action' => 'Spending about ' . $cap($c['quota']) . ' a day will carry you to ' . $c['reset_day'] . '.',
                ];

            case 'over_quota':
                return [
                    'headline' => "You're over today's limit.",
                    'sub' => "You've spent {$fmt($c['over_today'])} more than today's safe amount.",
                    'action' => $c['is_final']
                        ? 'Less will roll over to next week, so go easy for the rest of today.'
                        : "Go easy for the rest of today. From tomorrow you can spend about {$fmt($c['quota'])} a day.",
                ];

            case 'quota_hit':
                return [
                    'headline' => "You've reached today's limit.",
                    'sub' => "You've used {$fmt($c['spent_today'])} of today's {$fmt($c['limit'])} limit, including anything moved to savings.",
                    'action' => $c['is_final']
                        ? "Anything you don't spend rolls over to next week."
                        : "Try not to spend more today. Tomorrow you'll have about {$fmt($c['quota'])}.",
                ];

            case 'runs_out':
                return [
                    'headline' => "You may run out on {$c['runs_out_label']}.",
                    'sub' => "At your current pace, your money runs out about {$c['short_days']} {$short} before reset, roughly " . $fmt($c['shortfall']) . ' short.',
                    'action' => 'Keep it under ' . $cap($c['quota']) . " a day and you'll make it to {$c['reset_day']}.",
                ];

            case 'tight':
                return [
                    'headline' => "You'll make it, but barely.",
                    'sub' => 'At this pace you\'ll have about ' . $fmt($c['projected_remaining']) . " left by {$c['end_label']}.",
                    'action' => 'Keep it under ' . $cap($c['quota']) . ' a day to leave yourself a buffer.',
                ];

            default:
                return [
                    'headline' => "You're on track.",
                    'sub' => 'At this pace you\'ll have about ' . $fmt($c['projected_remaining']) . " left by {$c['end_label']}.",
                    'action' => 'You can spend up to ' . $cap($c['quota']) . ' a day and still be fine.',
                ];
        }
    }

    /**
     * Specific, data-driven advice.
     */
    private function advice(string $state, array $c): array
    {
        $fmt = fn ($n) => '₱' . number_format($n, 2);

        $over = in_array($state, ['runs_out', 'tight'], true);

        $items = [];

        if ($state === 'depleted') {
            return [[
                'icon' => 'exclamation-circle',
                'tone' => 'rose',
                'title' => "Essentials only until {$c['reset_day']}",
                'body' => 'Cover food and fare first. If your family sends extra, use Add Funds so your daily limit updates.',
            ]];
        }

        if (
            !$c['is_final']
            && $c['limit'] > 0
            && $c['spent_today'] >= $c['limit']
            && $c['spent_today'] > 0
        ) {
            $items[] = [
                'icon' => 'clock',
                'tone' => 'amber',
                'title' => "You've hit today's limit",
                'body' => 'Anything more today is taken from tomorrow. Hold off on non-essentials if you can.',
            ];
        }

        $top = $c['top_category'];

        if ($top && $top['percent'] >= 40 && ($over || $top['percent'] >= 50)) {
            $perDay = $c['days_elapsed'] > 0
                ? $top['total'] / $c['days_elapsed']
                : 0;

            if ($over && $c['cut'] > 0) {
                $body = $perDay >= $c['cut']
                    ? 'Spending about ' . $fmt($c['cut']) . ' less a day here would put you back on track.'
                    : 'Most of your money goes here, so trimming it is the fastest way back on track.';
            } else {
                $body = 'Not a problem by itself. Just keep an eye on it.';
            }

            $items[] = [
                'icon' => 'tag',
                'tone' => $over ? 'amber' : 'slate',
                'title' => "{$top['name']} is {$top['percent']}% of your spending",
                'body' => $body,
            ];
        }

        $rep = $c['repeat'];

        if ($rep) {
            $items[] = [
                'icon' => 'refresh',
                'tone' => $over ? 'amber' : 'slate',
                'title' => ucfirst($rep['name']) . " — bought {$rep['count']} times",
                'body' => "That's " . $fmt($rep['total']) . ' so far.'
                    . ($over
                        ? ' Skipping a couple would help.'
                        : ' Small buys add up, so it\'s worth knowing.'),
            ];
        }

        if ($state === 'on_track' && $c['goal_name']) {
            $amount = floor(($c['projected_remaining'] * 0.4) / 10) * 10;

            if ($amount >= 50) {
                $items[] = [
                    'icon' => 'cash',
                    'tone' => 'emerald',
                    'title' => 'You have room to save',
                    'body' => "You're ahead of pace. Moving " . $fmt($amount) . " to \"{$c['goal_name']}\" still keeps you on budget.",
                ];
            }
        }

        if (empty($items)) {
            $items[] = $state === 'fresh_start'
                ? [
                    'icon' => 'light-bulb',
                    'tone' => 'slate',
                    'title' => 'Log as you spend',
                    'body' => 'The forecast gets useful after a day or two of logged expenses.',
                ]
                : [
                    'icon' => 'check-circle',
                    'tone' => 'emerald',
                    'title' => 'Nothing to fix right now',
                    'body' => 'Keep logging purchases so the forecast stays accurate.',
                ];
        }

        return array_slice($items, 0, 3);
    }

    /**
     * Optional extra tips from Groq.
     */
    public function fetchAiInsight($user, array $localForecast)
    {
        if (($localForecast['status'] ?? '') !== 'success') {
            return [
                'is_online' => false,
                'tips' => [],
            ];
        }

        $raw = $localForecast['raw'];

        $fallback = [
            'is_online' => false,
            'tips' => [],
        ];

        $userPrompt = "Situation: {$raw['state']}. "
            . 'Money left: PHP ' . number_format($raw['remaining'], 2) . '. '
            . 'Days left in the week (including today): ' . $raw['days_left'] . '. '
            . 'Average spending so far: PHP ' . number_format($raw['pace'], 2) . ' per day. '
            . 'Safe amount per day: PHP ' . number_format($raw['safe_per_day'], 2) . '. '
            . ($raw['runs_out_label']
                ? "Money is projected to run out on {$raw['runs_out_label']}. "
                : '')
            . ($raw['top_category']
                ? "Biggest category: {$raw['top_category']['name']}, {$raw['top_category']['percent']}% of spending (PHP "
                    . number_format($raw['top_category']['total'], 2) . '). '
                : '')
            . ($raw['repeat']
                ? "Most repeated purchase: {$raw['repeat']['name']}, {$raw['repeat']['count']} times, PHP "
                    . number_format($raw['repeat']['total'], 2) . ' total. '
                : '');

        $cacheKey = 'forecast_ai:v4:' . $user->id . ':' . md5($userPrompt);

        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        try {
            $settings = IntegrationSetting::current();

            $apiKey = $settings->groq_api_key
                ?: config('services.groq.key');

            if (empty($apiKey)) {
                return $fallback;
            }

            $payload = [
                'model' => $settings->groq_text_model,
                'max_tokens' => 800,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a friendly college-student peer in Maasin City, Southern Leyte, Philippines. '
                            . 'Give 1 or 2 short, specific money tips that directly use the facts in the message '
                            . '(the biggest category or the repeated purchase). Where it fits, suggest a realistic cheaper local option '
                            . '(carinderia, palengke, baon, sharing tricycle or habal-habal fare, mobile load promo). '
                            . 'Do not invent facts, do not repeat the numbers back, no generic advice, never mention big-city services. '
                            . 'Plain friendly English. Separate tips ONLY with a pipe character (|). '
                            . 'No hyphens, bullets or markdown. Each tip under 22 words.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
            ];

            if (str_contains(strtolower((string) $settings->groq_text_model), 'gpt-oss')) {
                $payload['reasoning_effort'] = 'low';
            }

            $response = Http::withToken($apiKey)
                ->timeout(6)
                ->post(
                    'https://api.groq.com/openai/v1/chat/completions',
                    $payload
                );

            if (!$response->successful()) {
                Log::warning('Spending Forecast: Groq request failed', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 300),
                ]);

                return $fallback;
            }

            $content = $response->json()['choices'][0]['message']['content'] ?? '';

            if (!str_contains($content, '|')) {
                $content = preg_replace('/[\r\n]+/', '|', $content);
            }

            $tips = array_values(array_filter(
                array_map(
                    fn ($item) => trim(
                        preg_replace('/^[\s\-\*•\d\.\)]+/', '', $item)
                    ),
                    explode('|', $content)
                ),
                fn ($tip) => $tip !== '' && mb_strlen($tip) <= 160
            ));

            if (count($tips) < 1) {
                return $fallback;
            }

            $result = [
                'is_online' => true,
                'tips' => array_slice($tips, 0, 2),
            ];

            Cache::put(
                $cacheKey,
                $result,
                now()->addHour()
            );

            ActivityLog::create([
                'user_id' => $user->id,
                'event_type' => 'ai_forecast_requested',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details' => 'Spending forecast AI tips generated.',
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::warning(
                'Spending Forecast AI offline: ' . $e->getMessage()
            );

            return $fallback;
        }
    }
}