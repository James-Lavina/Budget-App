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
     * Fast, local-only forecast. No external HTTP calls, safe on every page load.
     *
     * Pace and the advised daily rate come from BudgetCycleService::pace(), the same
     * call the Dashboard uses, so both pages always agree.
     */
    public function computeLocalForecast($user)
    {
        $budget = WeeklyBudget::where('user_id', $user->id)->latest()->first();

        if (!$budget) {
            return ['status' => 'error', 'message' => 'Set up your weekly budget first to see your forecast.'];
        }

        $cycleService = app(BudgetCycleService::class);
        $cycle        = $cycleService->resolve($budget, $user);

        $startDate     = $cycle['startDate'];
        $endDate       = $cycle['endDate'];
        $daysElapsed   = (int) $cycle['daysElapsed'];
        $daysRemaining = max(1, (int) $cycle['daysRemaining']);
        $cycleLength   = max(1, (int) $startDate->diffInDays($cycle['nextResetDate']));
        $resetDay      = $cycle['targetResetDay'];

        $remaining = (float) $budget->remaining_allowance;

        // ---- Per-day / per-category / per-item totals for this cycle -----------------------
        $cycleExpenses = Expense::with('category')
            ->where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();

        $allByDay   = []; // everything that left the budget, savings transfers included
        $spendByDay = []; // real spending only (drives pace, bars, categories)
        $byCategory = [];
        $byItem     = [];

        foreach ($cycleExpenses as $e) {
            $key    = $e->transaction_date->format('Y-m-d');
            $amount = (float) $e->amount;

            $allByDay[$key] = ($allByDay[$key] ?? 0) + $amount;

            if ($e->isSavingsTransfer()) {
                continue;
            }

            $spendByDay[$key] = ($spendByDay[$key] ?? 0) + $amount;

            $catName = $e->category->name ?? 'Uncategorized';
            if (!isset($byCategory[$catName])) {
                $byCategory[$catName] = [
                    'name'  => $catName,
                    'total' => 0.0,
                    'color' => ExpenseCategory::colorToHex($e->category->color ?? null),
                ];
            }
            $byCategory[$catName]['total'] += $amount;

            $itemKey = Str::lower(trim($e->item_name));
            if (!isset($byItem[$itemKey])) {
                $byItem[$itemKey] = ['name' => trim($e->item_name), 'count' => 0, 'total' => 0.0];
            }
            $byItem[$itemKey]['count']++;
            $byItem[$itemKey]['total'] += $amount;
        }

        // Money at the start of the cycle, reconstructed so the chart's last real point == remaining.
        $pool = $remaining + array_sum($allByDay);

        // Same definition as the Dashboard: savings transfers count toward today's outflow.
        $spentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->sum('amount');

        $realSpentToday = (float) Expense::where('user_id', $user->id)
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->excludingSavings()
            ->sum('amount');

        // ---- Series for the (legacy) chart payload -----------------------------------------
        $labels       = [];
        $actual       = [];
        $totalOutflow = 0.0; // spending + savings
        $totalSpent   = 0.0; // spending only
        $running      = 0.0;

        for ($i = 0; $i < $cycleLength; $i++) {
            $date     = $startDate->copy()->addDays($i);
            $key      = $date->format('Y-m-d');
            $labels[] = $date->format('D');

            if ($i + 1 <= $daysElapsed) {
                $dayAll   = $allByDay[$key] ?? 0;
                $daySpend = $spendByDay[$key] ?? 0;

                $running      += $dayAll;
                $totalOutflow += $dayAll;
                $totalSpent   += $daySpend;
                $actual[]      = round(max(0, $pool - $running), 2);
            } else {
                $actual[] = null;
            }
        }

        $totalSaved = max(0.0, $totalOutflow - $totalSpent);

        // ---- Projection (same pace logic as the Dashboard) ---------------------------------
        $realSpent = $totalSpent;

        $p = $cycleService->pace($cycle, $remaining, $realSpent, $realSpentToday, $totalSaved);

        $isFinalDay = $p['daysAfterToday'] <= 0;
        $pace       = (float) $p['velocity'];
        $futureDays = (int) $p['daysAfterToday'];
        $overPace   = (bool) $p['isOverPace'];

        $projectedSpend     = $pace * $futureDays;
        $projectedRemaining = max(0, $remaining - $projectedSpend);
        $shortfall          = max(0, $projectedSpend - $remaining);

        $projected = [];
        for ($i = 0; $i < $cycleLength; $i++) {
            $day = $i + 1;
            if ($isFinalDay || $day < $daysElapsed) {
                $projected[] = null;
            } elseif ($day === $daysElapsed) {
                $projected[] = round($remaining, 2);
            } else {
                $projected[] = round(max(0, $remaining - $pace * ($day - $daysElapsed)), 2);
            }
        }

        // ---- Daily limit (same formula as Dashboard) ---------------------------------------
        $quota     = ($remaining + $spentToday) / $daysRemaining;
        $safeToday = max(0, $quota - $spentToday);

        // Advised rate: once today's limit is spent, spread what's left over the days after today.
        $daysAfterToday = (int) $p['daysAfterToday'];
        $advisedRate    = ($safeToday < 1 && $daysAfterToday > 0)
            ? $remaining / $daysAfterToday
            : (float) ($p['paceQuota'] ?? $quota);

        // ---- When does the money run out? --------------------------------------------------
        $runsOutLabel = null;
        $shortDays    = 0;

        if (!$isFinalDay && $remaining > 0 && $pace > 0 && $overPace) {
            $covered      = (int) floor($remaining / $pace);
            $firstMissing = $daysElapsed + $covered + 1;
            $runsOutLabel = $startDate->copy()->addDays($firstMissing - 1)->format('l');
            $shortDays    = max(1, ($cycleLength + 1) - $firstMissing);
        }

        // ---- Single verdict ----------------------------------------------------------------
        $pctLeft = $pool > 0 ? (int) round(($projectedRemaining / $pool) * 100) : 0;

        if ($remaining <= 0) {
            $state = 'depleted';
        } elseif ($isFinalDay) {
            $state = 'final_day';
        } elseif ($realSpent <= 0) {
            $state = 'fresh_start';
        } elseif ($overPace) {
            $state = 'runs_out';
        } elseif ($pctLeft < 15) {
            $state = 'tight';
        } else {
            $state = 'on_track';
        }

        // ---- Week strip --------------------------------------------------------------------
        $days     = [];
        $maxSpend = 0.0;

        for ($i = 0; $i < $cycleLength; $i++) {
            $date   = $startDate->copy()->addDays($i);
            $key    = $date->format('Y-m-d');
            $day    = $i + 1;
            $status = $day < $daysElapsed ? 'past' : ($day === $daysElapsed ? 'today' : 'future');
            $spent  = $status === 'future' ? 0.0 : (float) ($spendByDay[$key] ?? 0);

            $maxSpend = max($maxSpend, $spent);
            $days[]   = [
                'label'  => $date->format('D'),
                'date'   => $date->format('M j'),
                'status' => $status,
                'spent'  => round($spent, 2),
            ];
        }

        $scale = max(1.0, $quota, $maxSpend) * 1.15;

        // ---- Where the money went / repeat purchases ---------------------------------------
        $categories = collect($byCategory)
            ->map(function ($c) use ($realSpent) {
                $c['total']   = round($c['total'], 2);
                $c['percent'] = $realSpent > 0 ? (int) round(($c['total'] / $realSpent) * 100) : 0;
                return $c;
            })
            ->sortByDesc('total')
            ->take(3)
            ->values()
            ->all();

        $topCategory = $categories[0] ?? null;

        $repeat = collect($byItem)
            ->filter(fn ($i) => $i['count'] >= 3)
            ->sortByDesc('total')
            ->first();

        $cut = in_array($state, ['runs_out', 'tight'], true) ? max(0, $pace - $advisedRate) : 0.0;

        $goal = null;
        if ($state === 'on_track') {
            $goal = SavingsGoal::where('user_id', $user->id)
                ->where('status', 'active')
                ->orderByDesc('current_saved')
                ->first();
        }

        $ctx = [
            'remaining'           => $remaining,
            'projected_remaining' => $projectedRemaining,
            'shortfall'           => $shortfall,
            'pace'                => $pace,
            'quota'               => $advisedRate, // amount we tell the student to stay under
            'limit'               => $quota,       // today's daily limit
            'spent_today'         => $spentToday,
            'days_left'           => $daysRemaining,
            'days_elapsed'        => $daysElapsed,
            'is_final'            => $isFinalDay,
            'reset_day'           => $resetDay,
            'end_label'           => $endDate->format('l'),
            'runs_out_label'      => $runsOutLabel,
            'short_days'          => $shortDays,
            'pct_left'            => $pctLeft,
            'top_category'        => $topCategory,
            'repeat'              => $repeat,
            'cut'                 => $cut,
            'goal_name'           => $goal->target_name ?? null,
        ];

        return [
            'status'  => 'success',
            'metrics' => [
                'state'               => $state,
                'remaining'           => $remaining,
                'projected_remaining' => $projectedRemaining,
                'pace'                => $pace,
                'safe_per_day'        => $quota,
                'advised_per_day'     => $advisedRate,
                'safe_today'          => $safeToday,
                'spent_today'         => $spentToday,
                'saved'               => $totalSaved,
                'days_left'           => $daysRemaining,
                'is_final_day'        => $isFinalDay,
                'reset_day'           => $resetDay,
                'end_label'           => $endDate->format('l'),
                'runs_out_label'      => $runsOutLabel,
            ],
            'text'       => $this->narrative($state, $ctx),
            'advice'     => $this->advice($state, $ctx),
            'days'       => $days,
            'day_scale'  => round($scale, 2),
            'categories' => $categories,
            'chart'      => [
                'labels'      => $labels,
                'actual'      => $actual,
                'projected'   => $projected,
                'pool'        => round($pool, 2),
                'today_index' => max(0, $daysElapsed - 1),
            ],
            'raw' => [
                'remaining'           => $remaining,
                'pace'                => $pace,
                'safe_per_day'        => $advisedRate,
                'projected_remaining' => $projectedRemaining,
                'runs_out_label'      => $runsOutLabel,
                'days_left'           => $daysRemaining,
                'reset_day'           => $resetDay,
                'state'               => $state,
                'top_category'        => $topCategory,
                'repeat'              => $repeat,
                'budget_id'           => $budget->id,
                'budget_updated_at'   => optional($budget->updated_at)->timestamp,
            ],
        ];
    }

    /**
     * Plain-language verdict: headline / one sentence / one action. Works offline.
     */
    private function narrative(string $state, array $c): array
    {
        $fmt   = fn ($n) => '₱' . number_format($n, 2);
        $cap   = fn ($n) => '₱' . number_format(floor(round($n * 100, 4)) / 100, 2);
        $days  = Str::plural('day', $c['days_left']);
        $short = Str::plural('day', $c['short_days']);

        switch ($state) {
            case 'depleted':
                return [
                    'headline' => 'Your budget is used up.',
                    'sub'      => "{$c['days_left']} {$days} to go until your {$c['reset_day']} reset.",
                    'action'   => 'Stick to essentials like food and fare until reset.',
                ];

            case 'final_day':
                return [
                    'headline' => 'Last day of the week.',
                    'sub'      => "You have {$fmt($c['remaining'])} left. Whatever you don't spend rolls over to next week.",
                    'action'   => 'Spend only what you need. Saving it today gives you a head start next week.',
                ];

            case 'fresh_start':
                return [
                    'headline' => 'Fresh week — nothing logged yet.',
                    'sub'      => "Log your first expenses and we'll show where your money is heading.",
                    'action'   => 'Spending about ' . $cap($c['quota']) . ' a day will carry you to ' . $c['reset_day'] . '.',
                ];

            case 'runs_out':
                return [
                    'headline' => "You may run out on {$c['runs_out_label']}.",
                    'sub'      => "At your current pace, your money runs out about {$c['short_days']} {$short} before reset, roughly " . $fmt($c['shortfall']) . ' short.',
                    'action'   => 'Keep it under ' . $cap($c['quota']) . " a day and you'll make it to {$c['reset_day']}.",
                ];

            case 'tight':
                return [
                    'headline' => "You'll make it, but barely.",
                    'sub'      => 'At this pace you\'ll have about ' . $fmt($c['projected_remaining']) . " left by {$c['end_label']}.",
                    'action'   => 'Keep it under ' . $cap($c['quota']) . ' a day to leave yourself a buffer.',
                ];

            default: // on_track
                return [
                    'headline' => "You're on track.",
                    'sub'      => 'At this pace you\'ll have about ' . $fmt($c['projected_remaining']) . " left by {$c['end_label']}.",
                    'action'   => 'You can spend up to ' . $cap($c['quota']) . ' a day and still be fine.',
                ];
        }
    }

    /**
     * Specific, data-driven advice (max 3). Every item comes from the student's own spending.
     * Each item: icon (Heroicons v1 outline name), tone (rose|amber|emerald|slate), title, body.
     */
    private function advice(string $state, array $c): array
    {
        $fmt   = fn ($n) => '₱' . number_format($n, 2);
        $over  = in_array($state, ['runs_out', 'tight'], true);
        $items = [];

        if ($state === 'depleted') {
            return [[
                'icon'  => 'exclamation-circle',
                'tone'  => 'rose',
                'title' => "Essentials only until {$c['reset_day']}",
                'body'  => 'Cover food and fare first. If your family sends extra, use Add Funds so your daily limit updates.',
            ]];
        }

        if (!$c['is_final'] && $c['limit'] > 0 && $c['spent_today'] >= $c['limit'] && $c['spent_today'] > 0) {
            $items[] = [
                'icon'  => 'clock',
                'tone'  => 'amber',
                'title' => "You've hit today's limit",
                'body'  => 'Anything more today is taken from tomorrow. Hold off on non-essentials if you can.',
            ];
        }

        $top = $c['top_category'];
        if ($top && $top['percent'] >= 40 && ($over || $top['percent'] >= 50)) {
            $perDay = $c['days_elapsed'] > 0 ? $top['total'] / $c['days_elapsed'] : 0;

            if ($over && $c['cut'] > 0) {
                $body = $perDay >= $c['cut']
                    ? 'Spending about ' . $fmt($c['cut']) . ' less a day here would put you back on track.'
                    : 'Most of your money goes here, so trimming it is the fastest way back on track.';
            } else {
                $body = 'Not a problem by itself. Just keep an eye on it.';
            }

            $items[] = [
                'icon'  => 'tag',
                'tone'  => $over ? 'amber' : 'slate',
                'title' => "{$top['name']} is {$top['percent']}% of your spending",
                'body'  => $body,
            ];
        }

        $rep = $c['repeat'];
        if ($rep) {
            $items[] = [
                'icon'  => 'refresh',
                'tone'  => $over ? 'amber' : 'slate',
                'title' => ucfirst($rep['name']) . " — bought {$rep['count']} times",
                'body'  => "That's " . $fmt($rep['total']) . ' so far.' . ($over ? ' Skipping a couple would help.' : ' Small buys add up, so it\'s worth knowing.'),
            ];
        }

        if ($state === 'on_track' && $c['goal_name']) {
            $amount = floor(($c['projected_remaining'] * 0.4) / 10) * 10;
            if ($amount >= 50) {
                $items[] = [
                    'icon'  => 'cash',
                    'tone'  => 'emerald',
                    'title' => 'You have room to save',
                    'body'  => "You're ahead of pace. Moving " . $fmt($amount) . " to \"{$c['goal_name']}\" still keeps you on budget.",
                ];
            }
        }

        if (empty($items)) {
            $items[] = $state === 'fresh_start'
                ? [
                    'icon'  => 'light-bulb',
                    'tone'  => 'slate',
                    'title' => 'Log as you spend',
                    'body'  => 'The forecast gets useful after a day or two of logged expenses.',
                ]
                : [
                    'icon'  => 'check-circle',
                    'tone'  => 'emerald',
                    'title' => 'Nothing to fix right now',
                    'body'  => 'Keep logging purchases so the forecast stays accurate.',
                ];
        }

        return array_slice($items, 0, 3);
    }

    /**
     * Optional extra tips (max 2) from Groq. The model only gets the student's real facts,
     * so it can't fall back to generic advice. Returns no tips when offline. Failures are NOT cached.
     */
    public function fetchAiInsight($user, array $localForecast)
    {
        if (($localForecast['status'] ?? '') !== 'success') {
            return ['is_online' => false, 'tips' => []];
        }

        $raw      = $localForecast['raw'];
        $fallback = ['is_online' => false, 'tips' => []];

        $userPrompt = "Situation: {$raw['state']}. "
            . 'Money left: PHP ' . number_format($raw['remaining'], 2) . '. '
            . 'Days left in the week (including today): ' . $raw['days_left'] . '. '
            . 'Average spending so far: PHP ' . number_format($raw['pace'], 2) . ' per day. '
            . 'Safe amount per day: PHP ' . number_format($raw['safe_per_day'], 2) . '. '
            . ($raw['runs_out_label'] ? "Money is projected to run out on {$raw['runs_out_label']}. " : '')
            . ($raw['top_category']
                ? "Biggest category: {$raw['top_category']['name']}, {$raw['top_category']['percent']}% of spending (PHP " . number_format($raw['top_category']['total'], 2) . '). '
                : '')
            . ($raw['repeat']
                ? "Most repeated purchase: {$raw['repeat']['name']}, {$raw['repeat']['count']} times, PHP " . number_format($raw['repeat']['total'], 2) . ' total. '
                : '');

        $cacheKey = 'forecast_ai:v4:' . $user->id . ':' . md5($userPrompt);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $settings = IntegrationSetting::current();
            $apiKey   = $settings->groq_api_key ?: config('services.groq.key');

            if (empty($apiKey)) {
                return $fallback;
            }

            $response = Http::withToken($apiKey)
                ->timeout(6)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'      => $settings->groq_text_model,
                    'max_tokens' => 800,
                    'messages'   => [
                        [
                            'role'    => 'system',
                            'content' => 'You are a friendly college-student peer in Maasin City, Southern Leyte, Philippines. '
                                . 'Give 1 or 2 short, specific money tips that directly use the facts in the message '
                                . '(the biggest category or the repeated purchase). Where it fits, suggest a realistic cheaper local option '
                                . '(carinderia, palengke, baon, sharing tricycle or habal-habal fare, mobile load promo). '
                                . 'Do not invent facts, do not repeat the numbers back, no generic advice, never mention big-city services. '
                                . 'Plain friendly English. Separate tips ONLY with a pipe character (|). '
                                . 'No hyphens, bullets or markdown. Each tip under 22 words.',
                        ],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                ] + (str_contains(strtolower((string) $settings->groq_text_model), 'gpt-oss')
                    ? ['reasoning_effort' => 'low']
                    : []));

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

            if (count($tips) < 1) {
                return $fallback;
            }

            $result = ['is_online' => true, 'tips' => array_slice($tips, 0, 2)];
            Cache::put($cacheKey, $result, now()->addHour());

            ActivityLog::create([
                'user_id'    => $user->id,
                'event_type' => 'ai_forecast_requested',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'details'    => 'Spending forecast AI tips generated.',
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::warning('Spending Forecast AI offline: ' . $e->getMessage());
            return $fallback;
        }
    }
}