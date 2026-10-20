<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * AppSetting, RiskSetting and IntegrationSetting need no seeder: each one
     * creates its single row with defaults the first time it is read.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            ExpenseCategorySeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}