<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSavingsGoalIdToExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Adds the column cleanly right after your category column
            $table->foreignId('savings_goal_id')
                ->nullable()
                ->after('expense_category_id')
                ->constrained()
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Drops the foreign key constraint first, then drops the column safely
            $table->dropForeign(['savings_goal_id']);
            $table->dropColumn('savings_goal_id');
        });
    }
}