<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WeeklyBudget;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\SavingsGoal;
use App\Models\RiskLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

class SeedTestWeek extends Command
{
    /**
     * php artisan test:seed-week you@example.com
     * php artisan test:seed-week you@example.com --days=3 --allowance=1500
     * php artisan test:seed-week you@example.com --days=7 --fresh
     * php artisan test:seed-week you@example.com --savings=100
     * php artisan test:seed-week you@example.com --savings=100 --savings-goal="Emergency Fund"
     */
    protected $signature = 'test:seed-week
                            {email? : Email of the student user to seed (defaults to first student)}
                            {--allowance=1500 : Total weekly allowance to set on the new budget cycle}
                            {--days=5 : How many days (starting Monday) to seed with expenses, 1-7}
                            {--fresh : Clear risk logs/notifications for this user first. Never deletes expenses or categories.}
                            {--savings=0 : PHP amount to contribute to a real savings goal during the seeded window}
                            {--savings-goal= : Name of the savings goal to fund. Uses/creates one if omitted.}';

    protected $description = 'Seeds N days (starting Monday, default 5) of expenses on top of a fresh budget cycle. '
        . 'Never touches existing expenses or categories. Optionally also creates a real savings-goal '
        . 'contribution (linked via savings_goal_id, same as GoalsManager::addFunds does) so you can test '
        . 'that savings correctly reduce remaining_allowance and count toward goal progress.';

    public function handle()
    {
        $email = $this->argument('email');

        $user = $email
            ? User::where('email', $email)->first()
            : User::where('role', 'student')->first();

        if (!$user) {
            $this->error('No matching user found. Pass an email: php artisan test:seed-week you@example.com');
            return 1;
        }

        $days = max(1, min(7, (int) $this->option('days')));
        $savingsAmount = (float) $this->option('savings');

        // Anchor to THIS week's Monday. If run on a Sat/Sun, push to next week
        // so the seeded window stays "in the future" relative to real today
        // (needed for the fast-forward evaluation logic in Dashboard/Forecast/Simulator).
        $today = Carbon::today();
        $cycleStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        if ($today->isWeekend()) {
            $cycleStart = $cycleStart->copy()->addWeek();
        }

        $totalAllowance = (float) $this->option('allowance');

        // --fresh only clears risk logs/notifications for a clean pace-warning test.
        // It NEVER deletes expenses, categories, or savings goals - your existing data is always preserved.
        if ($this->option('fresh')) {
            $this->info("Clearing risk logs/notifications for {$user->email} (expenses, categories, and goals untouched)...");
            RiskLog::where('user_id', $user->id)->delete();
            DatabaseNotification::where('notifiable_id', $user->id)
                ->where('notifiable_type', 'App\Models\User')
                ->delete();
        }

        // Use whatever categories already exist - never create new ones.
        $allCategories = ExpenseCategory::all();
        if ($allCategories->isEmpty()) {
            $this->error('No expense categories found in the database. Create at least one category first.');
            return 1;
        }

        // For NEWLY seeded regular expenses, exclude "Savings" - a savings-category
        // expense needs a real savings_goal_id to make sense, handled separately below.
        $seedableCategories = $allCategories->reject(fn ($c) => strtolower($c->name) === 'savings');
        if ($seedableCategories->isEmpty()) {
            $this->error('Only a "Savings" category exists - add at least one non-savings category to seed against.');
            return 1;
        }

        $budget = WeeklyBudget::create([
            'user_id'             => $user->id,
            'total_allowance'     => $totalAllowance,
            'remaining_allowance' => $totalAllowance,
            'reset_day'           => 'Monday',
            'cycle_start_date'    => $cycleStart,
        ]);

        $nextResetDate = $cycleStart->copy()->addWeek();
        $endDate       = $nextResetDate->copy()->subSecond();

        // Pre-existing expenses already in this cycle's date window (including any
        // savings transfers) count toward remaining_allowance too, since they represent
        // real money already moved out of the budget. Nothing here gets deleted or ignored.
        $preExistingSpent = (float) Expense::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$cycleStart, $endDate])
            ->sum('amount');

        if ($preExistingSpent > 0) {
            $this->info("Found PHP " . number_format($preExistingSpent, 2) . " in pre-existing expenses already inside this cycle window - included in the total, not deleted or ignored.");
        }

        $sampleItems = [
            ['Jollibee lunch', 80, 150],
            ['Grab ride', 50, 100],
            ['Coffee', 40, 90],
            ['Printing / photocopy', 15, 40],
            ['Load / mobile data', 30, 80],
            ['Snacks', 20, 60],
            ['School supplies', 60, 150],
            ['Jeepney fare', 15, 30],
            ['Movie / streaming', 60, 120],
        ];

        $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $newlySeeded = 0.0;

        for ($i = 0; $i < $days; $i++) {
            $date = $cycleStart->copy()->addDays($i);
            $count = rand(2, 3);

            for ($j = 0; $j < $count; $j++) {
                [$name, $min, $max] = $sampleItems[array_rand($sampleItems)];
                $amount = rand($min * 100, $max * 100) / 100;
                $category = $seedableCategories->random();

                Expense::create([
                    'user_id'             => $user->id,
                    'expense_category_id' => $category->id,
                    'merchant_name'       => null,
                    'item_name'           => $name,
                    'amount'              => $amount,
                    'transaction_date'    => $date->copy()->setTime(rand(7, 20), rand(0, 59)),
                    'tracking_type'       => 'manual',
                ]);

                $newlySeeded += $amount;
            }

            $this->info("{$dayNames[$i]} ({$date->format('Y-m-d')}): {$count} expenses seeded.");
        }

        // --- OPTION 3: real savings goal contribution ---
        $savingsSeeded = 0.0;
        if ($savingsAmount > 0) {
            $goalName = $this->option('savings-goal');

            if ($goalName) {
                $goal = SavingsGoal::where('user_id', $user->id)
                    ->whereRaw('LOWER(target_name) = ?', [strtolower($goalName)])
                    ->first();

                if (!$goal) {
                    $goal = SavingsGoal::create([
                        'user_id'       => $user->id,
                        'target_name'   => $goalName,
                        'target_amount' => max($savingsAmount * 2, 500),
                        'current_saved' => 0,
                        'status'        => 'active',
                    ]);
                    $this->info("Created new savings goal \"{$goalName}\" (target PHP " . number_format($goal->target_amount, 2) . ").");
                }
            } else {
                // No name given - use the user's existing active goal if they have one, else make a generic test goal.
                $goal = SavingsGoal::where('user_id', $user->id)->where('status', 'active')->latest()->first();

                if (!$goal) {
                    $goal = SavingsGoal::create([
                        'user_id'       => $user->id,
                        'target_name'   => 'Test Savings Goal',
                        'target_amount' => max($savingsAmount * 2, 500),
                        'current_saved' => 0,
                        'status'        => 'active',
                    ]);
                    $this->info("No active savings goal found - created \"Test Savings Goal\" (target PHP " . number_format($goal->target_amount, 2) . ").");
                } else {
                    $this->info("Using your existing active goal \"{$goal->target_name}\" for this contribution.");
                }
            }

            // Reuses the "Savings" category the same way GoalsManager::addFunds does -
            // this is replicating the app's own normal behavior, not fabricating a new
            // arbitrary test category.
            $savingsCategory = ExpenseCategory::firstOrCreate(
                ['name' => 'Savings'],
                ['description' => 'Capital intentionally set aside for milestone savings targets.']
            );

            $contributionDate = $cycleStart->copy()->addDays($days - 1)->setTime(rand(7, 20), rand(0, 59));

            Expense::create([
                'user_id'             => $user->id,
                'expense_category_id' => $savingsCategory->id,
                'savings_goal_id'     => $goal->id,
                'item_name'           => $goal->target_name,
                'merchant_name'       => 'Savings Goal',
                'amount'              => $savingsAmount,
                'transaction_date'    => $contributionDate,
                'tracking_type'       => 'manual',
            ]);

            $newSaved = min($goal->target_amount, $goal->current_saved + $savingsAmount);
            $goal->update([
                'current_saved' => $newSaved,
                'status'        => $newSaved >= $goal->target_amount ? 'achieved' : $goal->status,
            ]);

            $savingsSeeded = $savingsAmount;
            $this->info("Seeded PHP " . number_format($savingsAmount, 2) . " savings contribution to \"{$goal->target_name}\" on {$contributionDate->format('Y-m-d (l)')}. Goal now at PHP " . number_format($goal->fresh()->current_saved, 2) . " / PHP " . number_format($goal->target_amount, 2) . ".");
        }

        $totalSpent = $preExistingSpent + $newlySeeded + $savingsSeeded;

        $budget->update([
            'remaining_allowance' => max(0, $totalAllowance - $totalSpent),
        ]);

        $this->newLine();
        $this->info("Cycle start: {$cycleStart->format('Y-m-d (l)')}, seeded {$days} day(s).");
        $this->info("Total allowance: PHP " . number_format($totalAllowance, 2));
        $this->info("Pre-existing spent in window: PHP " . number_format($preExistingSpent, 2));
        $this->info("Newly seeded expenses: PHP " . number_format($newlySeeded, 2));
        if ($savingsSeeded > 0) {
            $this->info("Newly seeded savings contribution: PHP " . number_format($savingsSeeded, 2));
        }
        $this->info("Combined total spent: PHP " . number_format($totalSpent, 2));
        $this->info("Remaining allowance: PHP " . number_format($budget->remaining_allowance, 2));

        $remainingDaysAfterSeed = max(0, 7 - $days);
        if ($remainingDaysAfterSeed > 0) {
            $this->info("Last seeded day is day {$days} of 7 -> Dashboard/Forecast/Simulator should fast-forward to that day and show {$remainingDaysAfterSeed} day(s) left.");
        } else {
            $this->info("Seeded all 7 days -> this cycle is fully populated, 0 days should be left.");
        }

        return 0;
    }
}