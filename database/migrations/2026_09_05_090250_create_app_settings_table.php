<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppSettingsTable extends Migration
{
    
    public function up()
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('application_name')->default('BudgetWise');
            $table->string('primary_color', 7)->default('#4f39fa');
            $table->string('logo_path')->nullable();
            $table->boolean('email_notifications_enabled')->default(true);
            $table->boolean('maintenance_mode_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_settings');
    }
}
