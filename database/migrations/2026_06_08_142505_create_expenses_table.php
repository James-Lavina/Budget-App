<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_category_id')->constrained()->onDelete('restrict');
            $table->string('merchant_name')->nullable();
            $table->string('item_name');
            $table->decimal('amount', 10, 2);
            $table->dateTime('transaction_date');
            $table->enum('tracking_type', ['manual', 'ocr'])->default('manual');
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
        Schema::dropIfExists('expenses');
    }
}
