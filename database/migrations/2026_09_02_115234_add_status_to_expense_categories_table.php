<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToExpenseCategoriesTable extends Migration
{
    public function up()
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->enum('status', ['enabled', 'disabled'])->default('enabled')->after('color');
        });
    }

    public function down()
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}