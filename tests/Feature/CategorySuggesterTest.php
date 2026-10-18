<?php

namespace Tests\Feature;

use App\Services\CategorySuggester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class CategorySuggesterTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    private function suggest($user, string $item)
    {
        return app(CategorySuggester::class)->suggest($user, $item);
    }

    public function test_admin_keywords_select_the_category()
    {
        $transport = $this->makeCategory('Transportation', ['keywords' => 'jeep, jeepney, fare']);
        $this->makeCategory('Food & Dining', ['keywords' => 'lunch, burger']);

        $this->assertSame($transport->id, $this->suggest($this->makeStudent(), 'Jeepney fare')->id);
    }

    public function test_unknown_items_get_no_suggestion()
    {
        $this->makeCategory('Transportation', ['keywords' => 'jeepney']);

        $this->assertNull($this->suggest($this->makeStudent(), 'xyzzy plugh'));
    }

    public function test_very_short_input_is_ignored()
    {
        $this->makeCategory('Food & Dining', ['keywords' => 'ab']);

        $this->assertNull($this->suggest($this->makeStudent(), 'ab'));
    }

    public function test_a_new_category_works_from_its_name_before_any_keywords_exist()
    {
        $groceries = $this->makeCategory('Groceries');

        $this->assertSame($groceries->id, $this->suggest($this->makeStudent(), 'weekly groceries run')->id);
    }

    public function test_plural_forms_match()
    {
        $academics = $this->makeCategory('Academics & Supplies', ['keywords' => 'book']);

        $this->assertSame($academics->id, $this->suggest($this->makeStudent(), 'Books')->id);
    }

    public function test_keywords_only_match_whole_words()
    {
        $this->makeCategory('Food & Dining', ['keywords' => 'rice']);

        $this->assertNull($this->suggest($this->makeStudent(), 'price check'));
    }

    public function test_multi_word_keywords_outweigh_single_word_matches()
    {
        $food   = $this->makeCategory('Food Court', ['keywords' => 'milk tea']);
        $this->makeCategory('Drinks', ['keywords' => 'tea']);

        $this->assertSame($food->id, $this->suggest($this->makeStudent(), 'milk tea')->id);
    }

    public function test_a_tie_between_categories_returns_nothing()
    {
        $this->makeCategory('Drinks', ['keywords' => 'coffee']);
        $this->makeCategory('Cafe', ['keywords' => 'coffee']);

        $this->assertNull($this->suggest($this->makeStudent(), 'coffee'));
    }

    public function test_disabled_savings_and_fallback_categories_are_never_suggested()
    {
        $this->makeCategory('Hidden Fees', ['keywords' => 'tuition', 'status' => 'disabled']);
        $this->makeCategory('Savings', ['keywords' => 'goal']);
        $this->makeCategory('Other', ['keywords' => 'misc', 'is_fallback' => true]);
        $user = $this->makeStudent();

        $this->assertNull($this->suggest($user, 'tuition fee'));
        $this->assertNull($this->suggest($user, 'goal money'));
        $this->assertNull($this->suggest($user, 'misc stuff'));
    }

    public function test_the_students_own_habit_beats_the_keywords()
    {
        $mine = $this->makeCategory('Allowance Stuff');
        $this->makeCategory('Food & Dining', ['keywords' => 'lunch']);
        $user = $this->makeStudent();

        $this->assertNotSame($mine->id, $this->suggest($user, 'lunch special')->id);

        foreach ([1, 2] as $i) {
            $this->makeExpense($user, $mine, 80.00, '2026-10-0' . $i . ' 12:00:00', ['item_name' => 'Lunch Special']);
        }

        $this->assertSame($mine->id, $this->suggest($user, 'lunch special')->id);
    }

    public function test_a_habit_of_filing_an_item_under_other_is_respected_with_no_nudge()
    {
        $other = $this->makeCategory('Other', ['is_fallback' => true]);
        $this->makeCategory('Food & Dining', ['keywords' => 'lunch']);
        $user = $this->makeStudent();

        foreach ([1, 2] as $i) {
            $this->makeExpense($user, $other, 80.00, '2026-10-0' . $i . ' 12:00:00', ['item_name' => 'Lunch Special']);
        }

        $this->assertNull($this->suggest($user, 'lunch special'));
    }

    public function test_the_crowd_decides_when_enough_students_agree()
    {
        $cat = $this->makeCategory('Crowd Picked');
        $u1  = $this->makeStudent();
        $u2  = $this->makeStudent();
        $u3  = $this->makeStudent();

        $this->makeExpense($u1, $cat, 10.00, '2026-10-01 10:00:00', ['item_name' => 'blorp item']);
        $this->makeExpense($u1, $cat, 10.00, '2026-10-02 10:00:00', ['item_name' => 'blorp item']);
        $this->makeExpense($u2, $cat, 10.00, '2026-10-03 10:00:00', ['item_name' => 'blorp item']);

        $this->assertSame($cat->id, $this->suggest($u3, 'blorp item')->id);
    }

    public function test_one_student_alone_is_not_a_crowd()
    {
        $cat = $this->makeCategory('Crowd Picked');
        $u1  = $this->makeStudent();
        $u3  = $this->makeStudent();

        foreach ([1, 2, 3] as $i) {
            $this->makeExpense($u1, $cat, 10.00, '2026-10-0' . $i . ' 10:00:00', ['item_name' => 'blorp item']);
        }

        $this->assertNull($this->suggest($u3, 'blorp item'));
    }
}