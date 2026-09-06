<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class RemapExpenseCategoryIconNames extends Migration
{
    private array $map = [
        'transport'     => 'truck',
        'school'        => 'academic-cap',
        'utility'       => 'lightning-bolt',
        'entertainment' => 'sparkles',
        'shopping'      => 'shopping-bag',
        'savings'       => 'cash',
        'personal'      => 'user',
        'bills'         => 'credit-card',
        'default'       => 'document-text',
        // 'food' is intentionally excluded — it stays 'food' since it's
        // handled as a manual inline SVG (no matching Heroicon exists).
    ];

    public function up()
    {
        foreach ($this->map as $old => $new) {
            DB::table('expense_categories')->where('icon', $old)->update(['icon' => $new]);
        }
    }

    public function down()
    {
        foreach ($this->map as $old => $new) {
            DB::table('expense_categories')->where('icon', $new)->update(['icon' => $old]);
        }
    }
}