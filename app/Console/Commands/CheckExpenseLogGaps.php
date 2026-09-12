<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\RiskSetting;
use App\Models\User;
use App\Notifications\NoExpenseLogsWarning;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class CheckExpenseLogGaps extends Command
{
    protected $signature = 'risk:check-log-gaps {--date= : Simulate "today" as this Y-m-d date, for testing}';
    protected $description = 'Daily check: alerts students who have not logged an expense for N consecutive days (per Risk Detection Rules).';

    public function handle()
    {
        $settings = RiskSetting::current();

        if (!$settings->no_expense_logs_enabled) {
            $this->info('No Expense Logs rule is disabled — skipping.');
            return 0;
        }

        if ($this->option('date')) {
            Carbon::setTestNow($this->option('date'));
            $this->warn('Simulating "today" as ' . Carbon::today()->format('Y-m-d') . ' for this run.');
        }

        $threshold = $settings->no_expense_logs_days;
        $today = Carbon::today();

        $students = User::where('role', 'student')->get();

        foreach ($students as $user) {
            $lastExpenseDate = Expense::where('user_id', $user->id)->max('transaction_date');

            $anchorDate = $lastExpenseDate
                ? Carbon::parse($lastExpenseDate)->startOfDay()
                : Carbon::parse($user->created_at)->startOfDay();

            $daysSinceLastLog = $anchorDate->diffInDays($today);

            if ($daysSinceLastLog < $threshold) {
                continue;
            }

            $alreadyAlerted = DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', 'App\Models\User')
                ->where('data->anomaly_type', 'no_expense_logs')
                ->where('data->resolved', false)
                ->exists();

            if (!$alreadyAlerted) {
                $user->notify(new NoExpenseLogsWarning($daysSinceLastLog));
                $this->info("Alerted {$user->email} — {$daysSinceLastLog} days since last log.");
            }
        }

        if ($this->option('date')) {
            Carbon::setTestNow(); // reset so you don't leave it faked for anything else in this process
        }

        return 0;
    }
}