<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiskSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('risk_settings', function (Blueprint $table) {
            $table->id();

            $table->boolean('overspending_enabled')->default(true);
            $table->unsignedTinyInteger('overspending_threshold')->default(80); // % of weekly allowance

            $table->boolean('daily_safe_to_spend_enabled')->default(true);
            $table->unsignedTinyInteger('daily_safe_to_spend_threshold')->default(20); // % of today's quota

            $table->boolean('rapid_spending_enabled')->default(true);
            $table->unsignedTinyInteger('rapid_spending_count')->default(3); // # of large txns to trigger

            $table->boolean('no_expense_logs_enabled')->default(false);
            $table->unsignedTinyInteger('no_expense_logs_days')->default(7);

            $table->boolean('low_remaining_budget_enabled')->default(true);
            $table->unsignedTinyInteger('low_remaining_budget_threshold')->default(15); // % of allowance

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('risk_settings');
    }
}