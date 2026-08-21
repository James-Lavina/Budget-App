<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WeeklyBudget;
use App\Models\Expense;
use App\Models\SavingsGoal;
use App\Models\RiskLog;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class TestResetUser extends Command
{
    /**
     * php artisan test:reset-user you@example.com --expenses
     * php artisan test:reset-user you@example.com --expenses --budgets
     * php artisan test:reset-user you@example.com --all
     */
    protected $signature = 'test:reset-user
                            {email : Email of the user to reset}
                            {--expenses : Delete all expenses}
                            {--budgets : Delete all weekly budget cycles}
                            {--goals : Delete all savings goals}
                            {--risks : Delete all risk logs}
                            {--notifications : Delete all notifications}
                            {--all : Delete everything above}
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Selectively wipes test data for one user. Nothing is deleted unless you '
        . 'explicitly pass a flag for it - running with no flags does nothing.';

    public function handle()
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No user found with that email.');
            return 1;
        }

        $all = $this->option('all');
        $targets = [
            'expenses'      => $all || $this->option('expenses'),
            'budgets'       => $all || $this->option('budgets'),
            'goals'         => $all || $this->option('goals'),
            'risks'         => $all || $this->option('risks'),
            'notifications' => $all || $this->option('notifications'),
        ];

        $selected = array_keys(array_filter($targets));

        if (empty($selected)) {
            $this->warn('No flags passed - nothing to delete. Pass --expenses, --budgets, --goals, --risks, --notifications, or --all.');
            return 0;
        }

        $counts = [
            'expenses'      => Expense::where('user_id', $user->id)->count(),
            'budgets'       => WeeklyBudget::where('user_id', $user->id)->count(),
            'goals'         => SavingsGoal::where('user_id', $user->id)->count(),
            'risks'         => RiskLog::where('user_id', $user->id)->count(),
            'notifications' => DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', 'App\Models\User')->count(),
        ];

        $this->info("About to delete for {$user->email}:");
        foreach ($selected as $key) {
            $this->line("  - {$counts[$key]} {$key}");
        }

        if (!$this->option('force') && !$this->confirm('Proceed with deletion?')) {
            $this->info('Cancelled. Nothing was deleted.');
            return 0;
        }

        if ($targets['expenses']) {
            Expense::where('user_id', $user->id)->delete();
        }
        if ($targets['budgets']) {
            WeeklyBudget::where('user_id', $user->id)->delete();
        }
        if ($targets['goals']) {
            SavingsGoal::where('user_id', $user->id)->delete();
        }
        if ($targets['risks']) {
            RiskLog::where('user_id', $user->id)->delete();
        }
        if ($targets['notifications']) {
            DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', 'App\Models\User')->delete();
        }

        $this->newLine();
        $this->info('Done. Deleted: ' . implode(', ', $selected) . '.');

        return 0;
    }
}