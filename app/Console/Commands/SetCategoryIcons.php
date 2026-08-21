<?php

namespace App\Console\Commands;

use App\Models\ExpenseCategory;
use Illuminate\Console\Command;

class SetCategoryIcons extends Command
{
    protected $signature = 'categories:set-icons';

    protected $description = 'One-time: sets icon/color on your existing expense categories by matching current names. '
        . 'Safe to re-run - only updates categories whose name matches one of the entries below.';

    public function handle()
    {
        $map = [
            'Food & Dining'           => ['icon' => 'food',          'color' => 'bg-amber-50 text-amber-600'],
            'Transportation'          => ['icon' => 'transport',     'color' => 'bg-blue-50 text-blue-600'],
            'Academics & Supplies'    => ['icon' => 'school',        'color' => 'bg-emerald-50 text-emerald-600'],
            'Utilities & Internet'    => ['icon' => 'utility',       'color' => 'bg-cyan-50 text-cyan-600'],
            'Entertainment & Leisure' => ['icon' => 'entertainment', 'color' => 'bg-purple-50 text-purple-600'],
            'Personal Care & Health'  => ['icon' => 'shopping',      'color' => 'bg-pink-50 text-pink-600'],
            'Savings'                 => ['icon' => 'savings',       'color' => 'bg-indigo-50 text-indigo-600'],
        ];

        foreach ($map as $name => $values) {
            $updated = ExpenseCategory::where('name', $name)->update($values);

            if ($updated) {
                $this->info("Updated \"{$name}\" -> icon: {$values['icon']}, color: {$values['color']}");
            } else {
                $this->warn("No category found named \"{$name}\" - skipped.");
            }
        }

        $unmatched = ExpenseCategory::whereNotIn('name', array_keys($map))->get();
        if ($unmatched->isNotEmpty()) {
            $this->newLine();
            $this->warn('These categories were not in the map and are still on the default icon:');
            foreach ($unmatched as $cat) {
                $this->line("  - {$cat->name}");
            }
        }

        return 0;
    }
}