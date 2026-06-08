<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRiskLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('risk_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('anomaly_type'); // e.g., 'rapid_overspending', 'early_week_depletion'
            $table->enum('severity_tier', ['low', 'medium', 'high']);
            $table->text('description'); // Detailed message explaining the behavioral prompt trigger
            $table->boolean('resolved')->default(false); // Flags if student adjusted pacing
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('risk_logs');
    }
}
