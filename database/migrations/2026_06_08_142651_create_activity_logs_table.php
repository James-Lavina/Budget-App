<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Nullable for tracking failed login attempts
            $table->string('event_type'); // e.g., 'auth_login', 'ocr_upload', 'budget_exceeded'
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('details')->nullable(); // Explanatory contextual metadata string
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
        Schema::dropIfExists('activity_logs');
    }
}
