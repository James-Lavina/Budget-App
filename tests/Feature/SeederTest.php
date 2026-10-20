<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Requires database/seeders/ExpenseCategorySeeder.php, generated with
 * `php artisan categories:export-seeder`.
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    private function adminConfig(?string $email = 'owner@example.com', ?string $password = 'a-strong-pass-1'): void
    {
        config(['budgetwise.admin' => ['name' => 'Owner', 'email' => $email, 'password' => $password]]);
    }

    public function test_a_fresh_database_gets_categories_and_a_super_admin()
    {
        $this->adminConfig();

        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, ExpenseCategory::count());
        $this->assertLessThanOrEqual(1, ExpenseCategory::where('is_fallback', true)->count());
        $this->assertDatabaseHas('expense_categories', ['name' => 'Savings']);

        $admin = User::where('email', 'owner@example.com')->first();
        $this->assertSame('super_admin', $admin->role);
        $this->assertSame('active', $admin->status);
        $this->assertTrue(Hash::check('a-strong-pass-1', $admin->password));
    }

    public function test_the_seeded_admin_can_log_in_and_reach_the_admin_area()
    {
        $this->adminConfig();
        $this->seed(DatabaseSeeder::class);

        $this->actingAs(User::where('email', 'owner@example.com')->first())
            ->get('/admin/admin-accounts')
            ->assertOk();
    }

    public function test_seeded_categories_are_selectable_by_students()
    {
        $this->adminConfig();
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(0, ExpenseCategory::selectable()->count());
    }

    public function test_seeding_twice_creates_no_duplicates_and_keeps_admin_edits()
    {
        $this->adminConfig();
        $this->seed(DatabaseSeeder::class);
        $categories = ExpenseCategory::count();
        $users      = User::count();

        ExpenseCategory::where('name', 'Savings')->update(['description' => 'Edited by an admin']);
        User::where('email', 'owner@example.com')->update(['name' => 'Renamed Owner']);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($categories, ExpenseCategory::count());
        $this->assertSame($users, User::count());
        $this->assertSame('Edited by an admin', ExpenseCategory::where('name', 'Savings')->value('description'));
        $this->assertSame('Renamed Owner', User::where('email', 'owner@example.com')->value('name'));
    }

    public function test_no_admin_is_created_without_credentials()
    {
        $this->adminConfig(null, null);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::count());
        $this->assertGreaterThan(0, ExpenseCategory::count());
    }

    public function test_weak_or_invalid_admin_credentials_are_refused()
    {
        $this->adminConfig('owner@example.com', 'short');
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(0, User::count());

        $this->adminConfig('not-an-email', 'a-strong-pass-1');
        $this->seed(DatabaseSeeder::class);
        $this->assertSame(0, User::count());
    }
}