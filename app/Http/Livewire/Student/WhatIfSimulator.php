<?php

namespace App\Http\Livewire\Student;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\SavingsGoal;
use App\Models\WeeklyBudget;
use App\Services\BudgetCycleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class WhatIfSimulator extends Component
{
    // Form inputs
    public $itemName = '';
    public $purchaseAmount = '';
    public $scenarioType = '';

    // Overview cards (always visible), same formulas as Student\Dashboard
    public $hasBudget = true;
    public $weeklyAllowance = 0.00;
    public $remainingBudget = 0.00;
    public $daysRemaining = 1;       // includes today
    public $daysAfterToday = 0;
    public $spentToday = 0.00;
    public $currentDailyQuota = 0.00;   // (remaining + spentToday) / daysRemaining
    public $currentSafeToSpend = 0.00;  // max(0, quota - spentToday)  = "left today"
    public $currentOverToday = 0.00;    // max(0, spentToday - quota)
    public $purchaseCeiling = 50000.00;

    // Result state
    public $hasSimulated = false;
    public $simulatedItem = '';
    public $simulatedAmount = 0.00;
    public $newRemaining = 0.00;
    public $newLeftToday = 0.00;      // raw: negative means over today's limit
    public $newSafeToSpend = 0.00;    // max(0, newLeftToday)
    public $newOverToday = 0.00;      // max(0, -newLeftToday)
    public $tomorrowBefore = 0.00;    // per-day amount from tomorrow, before the purchase
    public $tomorrowAfter = 0.00;     // per-day amount from tomorrow, after the purchase
    public $tomorrowDrop = 0.00;
    public $percentBefore = 0;
    public $percentAfter = 0;
    public $riskLevel = 'low'; // low | medium | high
    public $hasGoal = false;
    public $savingsGoalName = '';
    public $savingsGoalNeeded = 0.00;
    public $savingsFreeAfter = 0.00;
    public $isDeficit = false;
    public $isCriticalZero = false;
    public $isOfflineMode = false;
    public $aiInsight = '';

    // Kept so SimulationWidget's redirect (?purchaseAmount=..&scenarioType=..) still works
    protected $queryString = [
        'purchaseAmount' => ['except' => ''],
        'scenarioType'   => ['except' => ''],
    ];

    protected function rules()
    {
        return [
            'itemName'       => 'nullable|string|max:255',
            'purchaseAmount' => 'required|numeric|min:1|max:' . $this->purchaseCeiling,
        ];
    }

    protected $messages = [
        'purchaseAmount.required' => 'Enter the price of what you want to buy.',
        'purchaseAmount.numeric'  => 'Numbers only, please.',
        'purchaseAmount.min'      => 'Amount must be at least ₱1.',
        'purchaseAmount.max'      => 'That is unusually high for a simulation — try a smaller value (up to ₱:max).',
    ];

    public function mount()
    {
        $this->refreshOverview();

        $incoming = str_replace(',', '', (string) $this->purchaseAmount);
        if ($this->hasBudget && is_numeric($incoming) && (float) $incoming > 0) {
            $this->purchaseAmount = number_format((float) $incoming, 2, '.', '');
            if (trim($this->itemName) === '') {
                $this->itemName = $this->scenarioType === 'major_purchase' ? 'Quick Tested Item' : '';
            }
            $this->simulate();
        }
    }

    /**
     * Loads the overview cards with the same quota formula as
     * Student\Dashboard::computeBehavioralMetrics().
     */
    private function refreshOverview(): void
    {
        $budget = WeeklyBudget::where('user_id', Auth::id())->latest()->first();

        if (!$budget) {
            $this->hasBudget = false;
            return;
        }

        $this->hasBudget = true;

        $cycle = app(BudgetCycleService::class)->resolve($budget, Auth::user());

        $this->purchaseCeiling = (float) max($budget->total_allowance * 3, $budget->total_allowance + 5000);
        $this->weeklyAllowance = (float) max(1, $cycle['effectiveTotalAllowance']);
        $this->remainingBudget = (float) $budget->remaining_allowance;

        $periodEnded         = $cycle['today']->gte($cycle['nextResetDate']);
        $this->daysRemaining = $periodEnded ? 0 : (int) $cycle['daysRemaining'];
        $this->daysAfterToday = max(0, $this->daysRemaining - 1);

        // Everything that left the budget today, savings transfers included (same as the Dashboard).
        $this->spentToday = (float) Expense::where('user_id', Auth::id())
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->sum('amount');

        if ($this->daysRemaining > 0) {
            $this->currentDailyQuota = ($this->remainingBudget + $this->spentToday) / $this->daysRemaining;
        } else {
            $this->currentDailyQuota = 0.00;
        }

        $this->currentSafeToSpend = max(0, $this->currentDailyQuota - $this->spentToday);
        $this->currentOverToday   = max(0, $this->spentToday - $this->currentDailyQuota);
    }

    public function applyPreset($amount, $name = '')
    {
        $this->itemName = $name;
        $this->purchaseAmount = number_format((float) $amount, 2, '.', '');
        $this->simulate();
    }

    public function simulate()
    {
        $this->purchaseAmount = str_replace(',', '', (string) $this->purchaseAmount);

        if (is_numeric($this->purchaseAmount)) {
            $this->purchaseAmount = number_format((float) $this->purchaseAmount, 2, '.', '');
        }

        $this->refreshOverview();

        if (!$this->hasBudget) {
            return;
        }

        $this->validate();

        $cost = (float) $this->purchaseAmount;

        $this->simulatedItem   = trim($this->itemName) !== '' ? trim($this->itemName) : 'Planned purchase';
        $this->simulatedAmount = $cost;

        $this->newRemaining   = $this->remainingBudget - $cost;
        $this->isDeficit      = $this->newRemaining < 0;
        $this->isCriticalZero = !$this->isDeficit && $this->newRemaining <= 0.01;

        // Today: the quota does not change. Buying something only uses up today's amount.
        $leftBefore          = $this->currentDailyQuota - $this->spentToday;
        $this->newLeftToday  = $leftBefore - $cost;
        $this->newSafeToSpend = max(0, $this->newLeftToday);
        $this->newOverToday   = max(0, -$this->newLeftToday);

        // From tomorrow: what is left, spread over the days after today.
        $this->tomorrowBefore = $this->tomorrowRate($this->remainingBudget, $leftBefore);
        $this->tomorrowAfter  = $this->isDeficit ? 0.00 : $this->tomorrowRate($this->newRemaining, $this->newLeftToday);
        $this->tomorrowDrop   = max(0, $this->tomorrowBefore - $this->tomorrowAfter);

        $this->percentBefore = (int) max(0, min(100, round(($this->remainingBudget / $this->weeklyAllowance) * 100)));
        $this->percentAfter  = (int) max(0, min(100, round((max(0, $this->newRemaining) / $this->weeklyAllowance) * 100)));

        $this->riskLevel = $this->resolveRiskLevel();
        $this->resolveSavingsGoal();

        $this->hasSimulated = true;

        $this->generateSimulationInsight($cost);
    }

    /**
     * Per-day amount for the days after today. If today's amount is not used up yet,
     * tomorrow's amount is the normal daily quota. Once it is, whatever is left is
     * spread over the remaining days (the same rule as the Dashboard banner).
     */
    private function tomorrowRate(float $remaining, float $leftToday): float
    {
        if ($this->daysAfterToday <= 0) {
            return 0.0;
        }

        if ($leftToday > 0.005) {
            return $this->currentDailyQuota;
        }

        return max(0.0, $remaining) / $this->daysAfterToday;
    }

    /**
     * Rated on TODAY first, then on what it does to the rest of the week.
     *
     * low    -> fits inside today's safe-to-spend amount
     * medium -> goes over today's amount, but the rest of the week still holds up
     * high   -> can't afford it, leaves almost nothing (<10% of the week),
     *           or cuts the daily amount from tomorrow by 30% or more
     */
    private function resolveRiskLevel(): string
    {
        if ($this->isDeficit || $this->isCriticalZero || $this->percentAfter < 10) {
            return 'high';
        }

        if ($this->newLeftToday >= -0.005) {
            return 'low';
        }

        // Last day (or period ended): going over only eats into what would roll over.
        if ($this->daysAfterToday <= 0) {
            return 'medium';
        }

        $dropRatio = $this->tomorrowBefore > 0
            ? $this->tomorrowDrop / $this->tomorrowBefore
            : 0;

        return $dropRatio >= 0.30 ? 'high' : 'medium';
    }

    /**
     * Concrete numbers for the top active goal instead of a vague label.
     */
    private function resolveSavingsGoal(): void
    {
        $goal = SavingsGoal::where('user_id', Auth::id())
            ->where('status', 'active')
            ->orderByDesc('current_saved')
            ->first();

        if (!$goal) {
            $this->hasGoal           = false;
            $this->savingsGoalName   = '';
            $this->savingsGoalNeeded = 0.00;
            $this->savingsFreeAfter  = 0.00;
            return;
        }

        $needed = max(0.0, (float) $goal->target_amount - (float) $goal->current_saved);

        $this->hasGoal           = true;
        $this->savingsGoalName   = $goal->target_name;
        $this->savingsGoalNeeded = $needed;
        $this->savingsFreeAfter  = $this->isDeficit ? 0.0 : min(max(0.0, $this->newRemaining), $needed);
    }

    public function resetSimulation()
    {
        $this->reset([
            'itemName', 'purchaseAmount', 'scenarioType', 'hasSimulated',
            'simulatedItem', 'simulatedAmount', 'newRemaining', 'newLeftToday',
            'newSafeToSpend', 'newOverToday', 'tomorrowBefore', 'tomorrowAfter',
            'tomorrowDrop', 'percentBefore', 'percentAfter', 'riskLevel',
            'hasGoal', 'savingsGoalName', 'savingsGoalNeeded', 'savingsFreeAfter',
            'isDeficit', 'isCriticalZero', 'isOfflineMode', 'aiInsight',
        ]);
        $this->resetErrorBag();
        $this->refreshOverview();
    }

    public function addAsExpense()
    {
        if (!$this->hasSimulated || $this->isDeficit) {
            return;
        }

        return redirect()->route('student.expenses.create', [
            'item'   => $this->simulatedItem === 'Planned purchase' ? '' : $this->simulatedItem,
            'amount' => $this->simulatedAmount,
        ]);
    }

    private function generateSimulationInsight(float $cost): void
    {
        $item = $this->simulatedItem;
        $this->isOfflineMode = false;
        $isLastDay   = $this->daysRemaining === 1;
        $roundedCost = (int) (round($cost / 5) * 5);
        $roundedLeft = (int) (round($this->newLeftToday / 5) * 5);

        $cacheKey = sprintf(
            'simulator_ai:v4:%d:%s:%d:%d:%d:%s',
            Auth::id(),
            Str::slug($item),
            $roundedCost,
            $roundedLeft,
            $this->daysRemaining,
            $this->riskLevel
        );

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $this->aiInsight     = $cached['text'];
            $this->isOfflineMode = $cached['offline'];
            return;
        }

        try {
            $settings = \App\Models\IntegrationSetting::current();
            $apiKey = $settings->groq_api_key ?: config('services.groq.key');

            if (!empty($apiKey)) {
                $prompt = "Analyze this college student's spending simulation:\n" .
                    "- Item: {$item}\n" .
                    "- Cost: ₱" . number_format($cost, 2) . "\n" .
                    "- Today's safe-to-spend amount: ₱" . number_format($this->currentDailyQuota, 2) . "\n" .
                    "- Already spent today: ₱" . number_format($this->spentToday, 2) . "\n" .
                    ($this->newLeftToday >= 0
                        ? "- Left today after this purchase: ₱" . number_format($this->newLeftToday, 2) . "\n"
                        : "- Over today's safe amount after this purchase by: ₱" . number_format($this->newOverToday, 2) . "\n") .
                    "- Days left after today: {$this->daysAfterToday}\n" .
                    "- Per-day amount from tomorrow: before ₱" . number_format($this->tomorrowBefore, 2) .
                    ", after ₱" . number_format($this->tomorrowAfter, 2) . "\n" .
                    "- Remaining budget after purchase: ₱" . number_format($this->newRemaining, 2) . "\n" .
                    "- Rating: {$this->riskLevel} (low = fits today, medium = over today but week is fine, high = hard on the week)\n" .
                    ($isLastDay
                        ? "- IMPORTANT: today is the LAST day of the week. Any unspent money rolls over to next week.\n"
                        : "") . "\n" .
                    "Give concise advice in ONE complete sentence (max 25 words) about TODAY first, then the rest of the week if it matters. " .
                    "If the rating is low, simply say it fits today's budget. Only suggest waiting or a cheaper option when the rating is high. Plain words, use '₱'.";

                $payload = [
                    'model'                 => $settings->groq_text_model,
                    'messages'              => [
                        ['role' => 'system', 'content' => 'You are an encouraging and practical student budgeting assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature'           => $settings->groq_temperature,
                    'max_completion_tokens' => max((int) $settings->groq_max_tokens, 600),
                ];

                if (str_contains(strtolower((string) $settings->groq_text_model), 'gpt-oss')) {
                    $payload['reasoning_effort'] = 'low';
                }

                $response = Http::withToken($apiKey)
                    ->timeout(8)
                    ->post('https://api.groq.com/openai/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $choice = $response->json()['choices'][0] ?? [];
                    $text   = trim($choice['message']['content'] ?? '');
                    $finish = $choice['finish_reason'] ?? 'stop';

                    if ($text !== '' && $finish === 'stop' && preg_match('/[.!?]["”)]?$/u', $text)) {
                        $this->aiInsight = $text;
                        Cache::put($cacheKey, ['text' => $text, 'offline' => false], now()->addMinutes(3));

                        ActivityLog::create([
                            'user_id'    => Auth::id(),
                            'event_type' => 'ai_simulation_requested',
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'details'    => "AI insight generated for simulated purchase: {$item}",
                        ]);

                        return;
                    }

                    Log::warning('What-If Simulator: discarded incomplete AI answer', ['finish_reason' => $finish]);
                }
            }
        } catch (\Exception $e) {
            Log::error('What-If Simulator Exception: ' . $e->getMessage());
        }

        // ---------- Offline fallback (no wifi, no key, API error, or bad answer) ----------
        $this->isOfflineMode = true;

        $left      = number_format($this->newSafeToSpend, 2);
        $over      = number_format($this->newOverToday, 2);
        $before    = number_format($this->tomorrowBefore, 2);
        $after     = number_format($this->tomorrowAfter, 2);
        $remaining = number_format($this->newRemaining, 2);
        $nextDays  = $this->daysAfterToday . ' ' . Str::plural('day', $this->daysAfterToday);

        if ($this->isDeficit) {
            $this->aiInsight = "You can't afford this yet — it's ₱" . number_format(abs($this->newRemaining), 2) . " more than what you have left this week.";
        } elseif ($this->isCriticalZero) {
            $this->aiInsight = "This uses up all your remaining budget. You'll have ₱0.00 until your next reset.";
        } elseif ($isLastDay) {
            $this->aiInsight = $this->riskLevel === 'low'
                ? "It's your last day and this fits within today's safe amount. The ₱{$remaining} left will roll over to next week."
                : "It's your last day. This is ₱{$over} over today's safe amount, so less will roll over to next week.";
        } elseif ($this->riskLevel === 'low') {
            $this->aiInsight = "Good to go! This fits within today's safe amount, and you'd still have ₱{$left} left for today.";
        } elseif ($this->riskLevel === 'medium') {
            $this->aiInsight = "This is ₱{$over} over today's safe amount. You can still afford it, but you'll have about ₱{$after} a day for the next {$nextDays} instead of ₱{$before}.";
        } else {
            $this->aiInsight = "This would cut your daily amount for the next {$nextDays} from ₱{$before} to ₱{$after}. Consider waiting or a cheaper option.";
        }

        Cache::put($cacheKey, ['text' => $this->aiInsight, 'offline' => true], now()->addMinutes(3));
    }

    public function render()
    {
        return view('livewire.student.what-if-simulator')->layout('layouts.student');
    }
}