<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIntegrationSettingsTable extends Migration
{
    public function up()
    {
        Schema::create('integration_settings', function (Blueprint $table) {
            $table->id();
            $table->text('groq_api_key')->nullable(); // text, not string — encrypted values are longer than 255 chars
            $table->string('groq_vision_model')->default('qwen/qwen3.6-27b');
            $table->string('groq_text_model')->default('openai/gpt-oss-120b');
            $table->decimal('groq_temperature', 3, 2)->default(0.50);
            $table->unsignedInteger('groq_max_tokens')->default(400);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('integration_settings');
    }
}