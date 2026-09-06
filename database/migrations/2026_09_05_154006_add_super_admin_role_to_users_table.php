<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSuperAdminRoleToUsersTable extends Migration
{
    public function up()
    {
        // MySQL doesn't support altering an ENUM's values via Schema
        // builder directly — raw statement is the standard Laravel 8
        // workaround.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'super_admin') NOT NULL DEFAULT 'student'");
    }

    public function down()
    {
        // Reassign any super_admin rows back to admin before shrinking
        // the enum, or the down migration itself would fail.
        DB::table('users')->where('role', 'super_admin')->update(['role' => 'admin']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin') NOT NULL DEFAULT 'student'");
    }
}