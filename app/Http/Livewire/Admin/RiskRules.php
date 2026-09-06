<?php

namespace App\Http\Livewire\Admin;

use App\Models\RiskSetting;
use Livewire\Component;
use App\Models\ActivityLog;

class RiskRules extends Component
{
    public $overspending_enabled;
    public $overspending_threshold;

    public $daily_safe_to_spend_enabled;
    public $daily_safe_to_spend_threshold;

    public $rapid_spending_enabled;
    public $rapid_spending_count;

    public $no_expense_logs_enabled;
    public $no_expense_logs_days;

    public $low_remaining_budget_enabled;
    public $low_remaining_budget_threshold;

    protected $rules = [
        'overspending_threshold'         => 'required|integer|min:1|max:100',
        'daily_safe_to_spend_threshold'  => 'required|integer|min:1|max:100',
        'rapid_spending_count'           => 'required|integer|min:2|max:20',
        'no_expense_logs_days'           => 'required|integer|min:1|max:60',
        'low_remaining_budget_threshold' => 'required|integer|min:1|max:100',
    ];

    public function mount()
    {
        $s = RiskSetting::current();
        $this->overspending_enabled          = $s->overspending_enabled;
        $this->overspending_threshold        = $s->overspending_threshold;
        $this->daily_safe_to_spend_enabled   = $s->daily_safe_to_spend_enabled;
        $this->daily_safe_to_spend_threshold = $s->daily_safe_to_spend_threshold;
        $this->rapid_spending_enabled        = $s->rapid_spending_enabled;
        $this->rapid_spending_count          = $s->rapid_spending_count;
        $this->no_expense_logs_enabled       = $s->no_expense_logs_enabled;
        $this->no_expense_logs_days          = $s->no_expense_logs_days;
        $this->low_remaining_budget_enabled  = $s->low_remaining_budget_enabled;
        $this->low_remaining_budget_threshold = $s->low_remaining_budget_threshold;
    }

    public function save()
    {
        $this->validate();

        $s = RiskSetting::current();

        $fieldLabels = [
            'overspending_enabled'           => 'Overspending Threshold enabled',
            'overspending_threshold'         => 'Overspending Threshold %',
            'daily_safe_to_spend_enabled'    => 'Daily Safe-to-Spend enabled',
            'daily_safe_to_spend_threshold'  => 'Daily Safe-to-Spend %',
            'rapid_spending_enabled'         => 'Rapid Spending Detection enabled',
            'rapid_spending_count'           => 'Rapid Spending count',
            'no_expense_logs_enabled'        => 'No Expense Logs enabled',
            'no_expense_logs_days'           => 'No Expense Logs days',
            'low_remaining_budget_enabled'   => 'Low Remaining Budget enabled',
            'low_remaining_budget_threshold' => 'Low Remaining Budget %',
        ];

        $newValues = [
            'overspending_enabled'           => $this->overspending_enabled,
            'overspending_threshold'         => $this->overspending_threshold,
            'daily_safe_to_spend_enabled'    => $this->daily_safe_to_spend_enabled,
            'daily_safe_to_spend_threshold'  => $this->daily_safe_to_spend_threshold,
            'rapid_spending_enabled'         => $this->rapid_spending_enabled,
            'rapid_spending_count'           => $this->rapid_spending_count,
            'no_expense_logs_enabled'        => $this->no_expense_logs_enabled,
            'no_expense_logs_days'           => $this->no_expense_logs_days,
            'low_remaining_budget_enabled'   => $this->low_remaining_budget_enabled,
            'low_remaining_budget_threshold' => $this->low_remaining_budget_threshold,
        ];

        $changes = [];
        foreach ($newValues as $key => $newVal) {
            $oldVal = $s->{$key};
            // Booleans stored/cast may compare loosely across form <-> DB types; normalize before diffing.
            $oldNormalized = is_bool($oldVal) ? ($oldVal ? '1' : '0') : (string) $oldVal;
            $newNormalized = is_bool($newVal) ? ($newVal ? '1' : '0') : (string) $newVal;

            if ($oldNormalized !== $newNormalized) {
                if (str_ends_with($key, '_enabled')) {
                    $changes[] = "{$fieldLabels[$key]}: " . ($oldVal ? 'On' : 'Off') . ' → ' . ($newVal ? 'On' : 'Off');
                } else {
                    $changes[] = "{$fieldLabels[$key]}: {$oldVal} → {$newVal}";
                }
            }
        }

        $s->update($newValues);

        ActivityLog::create([
            'user_id'    => auth()->id(),
            'event_type' => 'risk_rules_updated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'details'    => !empty($changes)
                ? 'Updated risk detection rules: ' . implode(', ', $changes)
                : 'Saved risk detection rules — no field changes detected',
        ]);

        session()->flash('success', 'Risk detection rules updated.');
    }

    public function render()
    {
        return view('livewire.admin.risk-rules')->layout('layouts.admin');
    }
}