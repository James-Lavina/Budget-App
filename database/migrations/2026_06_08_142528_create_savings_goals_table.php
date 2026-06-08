<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSavingsGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('target_name'); // e.g., "Exam Fees", "New Book"
            $table->decimal('target_amount', 10, 2);
            $table->decimal('current_saved', 10, 2)->default(0.00);
            $table->date('target_date')->nullable();
            $table->enum('status', ['active', 'achieved', 'abandoned'])->default('active');
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
        Schema::dropIfExists('savings_goals');
    }
}
