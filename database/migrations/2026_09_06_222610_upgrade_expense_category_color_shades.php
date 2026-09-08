<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpgradeExpenseCategoryColorShades extends Migration
{
    private array $map = [
        'bg-amber-50 text-amber-600'     => 'bg-amber-50 text-amber-700',
        'bg-blue-50 text-blue-600'       => 'bg-blue-50 text-blue-700',
        'bg-emerald-50 text-emerald-600' => 'bg-emerald-50 text-emerald-700',
        'bg-cyan-50 text-cyan-600'       => 'bg-cyan-50 text-cyan-700',
        'bg-purple-50 text-purple-600'   => 'bg-purple-50 text-purple-700',
        'bg-pink-50 text-pink-600'       => 'bg-pink-50 text-pink-700',
        'bg-indigo-50 text-indigo-600'   => 'bg-indigo-50 text-indigo-700',
        'bg-rose-50 text-rose-600'       => 'bg-rose-50 text-rose-700',
        'bg-orange-50 text-orange-600'   => 'bg-orange-50 text-orange-700',
        'bg-slate-100 text-slate-600'    => 'bg-slate-100 text-slate-700',
    ];

    public function up()
    {
        foreach ($this->map as $old => $new) {
            DB::table('expense_categories')->where('color', $old)->update(['color' => $new]);
        }
    }

    public function down()
    {
        foreach ($this->map as $old => $new) {
            DB::table('expense_categories')->where('color', $new)->update(['color' => $old]);
        }
    }
}