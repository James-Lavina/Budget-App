<?php

namespace App\Console\Commands;

use App\Console\Concerns\BlocksProduction;
use App\Models\User;
use App\Models\WeeklyBudget;
use App\Services\BudgetCycleService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TestBudgetRollover extends Command
{

    use BlocksProduction;

    /**
     * Usage examples:
     * php artisan test:trigger-rollover you@example.com
     * php artisan test:trigger-rollover you@example.com --unspent=250.50
     * php artisan test:trigger-rollover you@example.com --allowance=1500
     * php artisan test:trigger-rollover --unspent=500
     */
    protected $signature = 'test:trigger-rollover
                            {email? : Email of the student user to test (defaults to first student)}
                            {--unspent= : Optional PHP amount to force as unspent allowance before rollover}
                            {--allowance= : Optional new weekly allowance to enter (defaults to the last base allowance)}';

    protected $description = 'Forces a weekly budget rollover for a student by backdating their active cycle start date '
        . 'and calling BudgetCycleService::rollover(), the same call the New Cycle page makes. '
        . 'Tests unspent budget carry-over and the weekly review notification.';

    public function handle()
    {
        if ($this->blockedInProduction()) {
            return 1;
        }

        $email = $this->argument('email');

        $user = $email
            ? User::where('email', $email)->first()
            : User::where('role', 'student')->first();

        if (!$user) {
            $this->error('No matching user found. Pass an email: php artisan test:trigger-rollover you@example.com');
            return 1;
        }

        $budget = WeeklyBudget::where('user_id', $user->id)
            ->latest()
            ->first();

        if (!$budget) {
            $this->error("No active weekly budget found for user {$user->email}. Run test:seed-week first.");
            return 1;
        }

        // If --unspent was provided, override remaining_allowance to test specific carry-over numbers
        if ($this->option('unspent') !== null) {
            $forcedUnspent = max(0.00, (float) $this->option('unspent'));
            $budget->update(['remaining_allowance' => $forcedUnspent]);
            $this->info('Overrode remaining allowance to PHP ' . number_format($forcedUnspent, 2) . ' prior to reset.');
        }

        $unspentBeforeReset = (float) $budget->fresh()->remaining_allowance;

        $newAllowance = $this->option('allowance') !== null
            ? max(1.00, round((float) $this->option('allowance'), 2))
            : (float) $budget->total_allowance;

        // Backdate cycle_start_date to 8 days ago so needsRollover() evaluates to true.
        $budget->update([
            'cycle_start_date' => Carbon::today()->subDays(8),
        ]);

        $this->info("Backdated cycle_start_date to {$budget->fresh()->cycle_start_date->format('Y-m-d (l)')} to force cycle expiration.");

        $service = app(BudgetCycleService::class);

        if (!$service->needsRollover($budget->fresh(), $user)) {
            $this->error('needsRollover() returned false. Rollover was not run.');
            return 1;
        }

        $result = $service->rollover($budget->fresh(), $user, $newAllowance);

        if (!$result) {
            $this->error('rollover() returned null. The cycle was already rolled over.');
            return 1;
        }

        $budget->refresh();

        $expectedNewTotal = $newAllowance + $unspentBeforeReset;

        $this->newLine();
        $this->info("=== ROLLOVER SUMMARY FOR {$user->email} ===");
        $this->info('Ending Cycle Spent: PHP ' . number_format($result['amountSpent'], 2));
        $this->info('Ending Cycle Saved: PHP ' . number_format($result['amountSaved'], 2));
        $this->info('Rolled-Over Unspent: PHP ' . number_format($result['unspent'], 2));
        $this->info('New Cycle Allowance Entered: PHP ' . number_format($newAllowance, 2));
        $this->info('New Cycle Total Available: PHP ' . number_format($budget->remaining_allowance, 2));
        $this->info("New Cycle Start Date: {$budget->cycle_start_date->format('Y-m-d (l)')}");

        if (abs((float) $budget->remaining_allowance - $expectedNewTotal) < 0.01) {
            $this->info('✔ SUCCESS: Rollover carry-over calculation is mathematically exact!');
        } else {
            $this->warn('⚠ WARNING: Expected PHP ' . number_format($expectedNewTotal, 2) . ' but found PHP ' . number_format($budget->remaining_allowance, 2));
        }

        return 0;
    }
}