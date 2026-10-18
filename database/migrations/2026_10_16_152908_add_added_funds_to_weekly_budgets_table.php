<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddedFundsToWeeklyBudgetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('weekly_budgets', function (Blueprint $table) {
            $table->decimal('added_funds', 10, 2)->default(0)->after('remaining_allowance');
        });
    }

    public function down()
    {
        Schema::table('weekly_budgets', function (Blueprint $table) {
            $table->dropColumn('added_funds');
        });
    }
}
