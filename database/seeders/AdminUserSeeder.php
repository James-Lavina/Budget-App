<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'], // Prevents duplicate errors if run multiple times
            [
                'name' => 'admin',
                'password' => Hash::make('123123123'), // Change this to a secure password
                'role' => 'admin',
            ]
        );
    }
}
