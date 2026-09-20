<?php

namespace App\Http\Livewire\Student;

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

    // Overview cards (always visible)
    public $hasBudget = true;
    public $weeklyAllowance = 0.00;
    public $remainingBudget = 0.00;
    public $daysRemaining = 1;
    public $spentToday = 0.00;
    public $currentDailyQuota = 0.00;   // same formula as Dashboard: (remaining + spentToday) / daysRemaining
    public $currentSafeToSpend = 0.00;  // quota - spentToday ("left today")
    public $purchaseCeiling = 50000.00;

    // Result state
    public $hasSimulated = false;
    public $simulatedItem = '';
    public $simulatedAmount = 0.00;
    public $newRemaining = 0.00;
    public $newDailyQuota = 0.00;
    public $newSafeToSpend = 0.00;
    public $dailyQuotaDrop = 0.00;
    public $percentBefore = 0;
    public $percentAfter = 0;
    public $riskLevel = 'low'; // low | medium | high
    public $savingsImpact = 'No active goal';
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
     * Loads the overview cards. Uses BudgetCycleService and the same quota
     * formula as Student\Dashboard::computeBehavioralMetrics(), so the numbers
     * here always match the dashboard (including the fast-forward test flow).
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

        $periodEnded = $cycle['today']->gte($cycle['nextResetDate']);
        $this->daysRemaining = $periodEnded ? 0 : (int) $cycle['daysRemaining'];

        $this->spentToday = (float) Expense::where('user_id', Auth::id())
            ->whereDate('transaction_date', $cycle['spentTodayDate'])
            ->whereNull('savings_goal_id')
            ->whereDoesntHave('category', function ($q) {
                $q->where('name', 'LIKE', '%Savings%');
            })
            ->sum('amount');

        if ($this->daysRemaining > 0) {
            $this->currentDailyQuota  = ($this->remainingBudget + $this->spentToday) / $this->daysRemaining;
            $this->currentSafeToSpend = max(0, $this->currentDailyQuota - $this->spentToday);
        } else {
            $this->currentDailyQuota  = 0.00;
            $this->currentSafeToSpend = 0.00;
        }
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

        if ($this->isDeficit || $this->daysRemaining === 0) {
            $this->newDailyQuota  = 0.00;
            $this->newSafeToSpend = 0.00;
        } else {
            $this->newDailyQuota  = ($this->newRemaining + $this->spentToday) / $this->daysRemaining;
            $this->newSafeToSpend = max(0, $this->newDailyQuota - $this->spentToday);
        }

        $this->dailyQuotaDrop = max(0, $this->currentDailyQuota - $this->newDailyQuota);

        $this->percentBefore = (int) max(0, min(100, round(($this->remainingBudget / $this->weeklyAllowance) * 100)));
        $this->percentAfter  = (int) max(0, min(100, round((max(0, $this->newRemaining) / $this->weeklyAllowance) * 100)));

        $this->riskLevel     = $this->resolveRiskLevel();
        $this->savingsImpact = $this->resolveSavingsImpact($cost);

        $this->hasSimulated = true;

        $this->generateSimulationInsight($cost);
    }

    /**
     * high   -> overdraws the budget, uses every peso left, or leaves under 10% of the week's pool
     * medium -> daily quota drops by 50%+ OR under 25% of the week's pool remains
     * low    -> everything else
     */
    private function resolveRiskLevel(): string
    {
        if ($this->isDeficit || $this->isCriticalZero || $this->percentAfter < 10) {
            return 'high';
        }

        // On the final day the quota is just "everything left", so a quota drop is meaningless.
        if ($this->daysRemaining === 1) {
            return $this->percentAfter < 25 ? 'medium' : 'low';
        }

        $dropRatio = $this->currentDailyQuota > 0
            ? $this->dailyQuotaDrop / $this->currentDailyQuota
            : 0;

        if ($dropRatio >= 0.5 || $this->percentAfter < 25) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Heuristic: the bigger the purchase relative to what's left, the slower
     * the student's top active goal will grow.
     */
    private function resolveSavingsImpact(float $cost): string
    {
        $goal = SavingsGoal::where('user_id', Auth::id())
            ->where('status', 'active')
            ->orderByDesc('current_saved')
            ->first();

        if (!$goal) {
            return 'No active goal';
        }

        if ($this->isDeficit) {
            return 'Goal at risk';
        }

        $ratio = $cost / max($this->remainingBudget, 0.01);

        if ($ratio <= 0.25) {
            return 'Barely affected';
        }
        if ($ratio <= 0.60) {
            return 'Slightly slower';
        }
        return 'Much slower';
    }

    public function resetSimulation()
    {
        $this->reset([
            'itemName', 'purchaseAmount', 'scenarioType', 'hasSimulated',
            'simulatedItem', 'simulatedAmount', 'newRemaining', 'newDailyQuota',
            'newSafeToSpend', 'dailyQuotaDrop', 'percentBefore', 'percentAfter',
            'riskLevel', 'savingsImpact', 'isDeficit', 'isCriticalZero',
            'isOfflineMode', 'aiInsight',
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
        $isLastDay = $this->daysRemaining === 1;

        $roundedCost = (int) (round($cost / 5) * 5);
        $cacheKey = sprintf(
            'simulator_ai:v3:%d:%s:%d:%d:%s',
            Auth::id(),
            Str::slug($item),
            $roundedCost,
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
            $apiKey = $settings->groq_api_key ?: (env('GROQ_API_KEY') ?? config('services.groq.key'));

            if (!empty($apiKey)) {
                $prompt = "Analyze this college student's spending simulation:\n" .
                    "- Item: {$item}\n" .
                    "- Cost: ₱" . number_format($cost, 2) . "\n" .
                    "- Days left in the week (including today): {$this->daysRemaining}\n" .
                    "- Remaining budget after purchase: ₱" . number_format($this->newRemaining, 2) . "\n" .
                    "- Daily budget after purchase (per day, for each remaining day): ₱" . number_format($this->newDailyQuota, 2) . "\n" .
                    "- Already spent today: ₱" . number_format($this->spentToday, 2) . "\n" .
                    "- Risk level: {$this->riskLevel}\n" .
                    ($isLastDay
                        ? "- IMPORTANT: today is the LAST day of the week. Any unspent money rolls over to next week, so it is not lost.\n"
                        : "") . "\n" .
                    "Give concise advice in ONE complete sentence (max 25 words). Refer to the DAILY BUDGET as the per-day figure. " .
                    "If risk is low, simply say they can afford it. Only suggest waiting or a cheaper option when risk is high " .
                    "or the daily budget is under ₱100. Plain words, use '₱'.";

                $payload = [
                    'model'                 => $settings->groq_text_model,
                    'messages'              => [
                        ['role' => 'system', 'content' => 'You are an encouraging and practical student budgeting assistant.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature'           => $settings->groq_temperature,
                    // Reasoning models spend tokens "thinking" before the answer; leave room for both.
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

                    // Reject truncated or unfinished answers instead of showing half a sentence.
                    if ($text !== '' && $finish === 'stop' && preg_match('/[.!?]["”)]?$/u', $text)) {
                        $this->aiInsight = $text;
                        Cache::put($cacheKey, ['text' => $text, 'offline' => false], now()->addMinutes(3));
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

        $daily     = number_format($this->newDailyQuota, 2);
        $remaining = number_format($this->newRemaining, 2);

        if ($this->isDeficit) {
            $this->aiInsight = "You can't afford this yet — it's ₱" . number_format(abs($this->newRemaining), 2) . " more than what you have left this week.";
        } elseif ($this->isCriticalZero) {
            $this->aiInsight = "This uses up all your remaining budget. You'll have ₱0.00 until your next reset.";
        } elseif ($isLastDay) {
            $this->aiInsight = $this->riskLevel === 'high'
                ? "It's your last day and this leaves only ₱{$remaining}. That carries over to next week, so consider a cheaper option."
                : "It's your last day. You can afford this, and the ₱{$remaining} left will roll over to next week.";
        } elseif ($this->riskLevel === 'high') {
            $this->aiInsight = "You can afford this, but your daily budget would drop to ₱{$daily} for {$this->daysRemaining} days. Consider waiting or a cheaper option.";
        } elseif ($this->riskLevel === 'medium') {
            $this->aiInsight = "You can afford this, but expect a tighter ₱{$daily}/day for the rest of the week.";
        } else {
            $this->aiInsight = "You can comfortably afford this. Your daily budget stays around ₱{$daily} for the rest of the week.";
        }

        Cache::put($cacheKey, ['text' => $this->aiInsight, 'offline' => true], now()->addMinutes(3));
    }

    public function render()
    {
        return view('livewire.student.what-if-simulator')->layout('layouts.student');
    }
}