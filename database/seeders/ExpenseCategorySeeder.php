<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Food & Dining',
                'description' => 'Daily meals, snacks, cafeteria expenses, and groceries.',
            ],
            [
                'name' => 'Transportation',
                'description' => 'Jeepney, tricycle, bus fares, gas, or ride-sharing.',
            ],
            [
                'name' => 'Academics & Supplies',
                'description' => 'Books, printing, photocopying, tuition fees, and school projects.',
            ],
            [
                'name' => 'Utilities & Internet',
                'description' => 'Mobile load, data registration, internet café fees, or dorm bills.',
            ],
            [
                'name' => 'Entertainment & Leisure',
                'description' => 'Movies, gaming, hanging out with friends, and hobbies.',
            ],
            [
                'name' => 'Personal Care & Health',
                'description' => 'Hygiene products, medicines, gym, or clothing items.',
            ],
        ];

        foreach ($categories as $category) {
            DB::table('expense_categories')->updateOrInsert(
                ['name' => $category['name']], // Prevents duplicate errors if run twice
                [
                    'description' => $category['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
