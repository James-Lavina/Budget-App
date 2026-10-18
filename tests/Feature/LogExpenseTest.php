<?php

namespace Tests\Feature;

use App\Http\Livewire\Student\LogExpense;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class LogExpenseTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Carbon::setTestNow('2026-10-07 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function signIn(float $remaining = 1000.00): array
    {
        $user      = $this->makeStudent();
        $budget    = $this->makeBudget($user, $remaining);
        $food      = $this->makeCategory('Food & Dining', ['keywords' => 'chicken,burger,milk tea']);
        $transport = $this->makeCategory('Transportation', ['keywords' => 'jeepney']);
        $this->actingAs($user);

        return [$user, $budget, $food, $transport];
    }

    // ---------------------------------------------------------------
    // Category detection
    // ---------------------------------------------------------------

    public function test_typing_an_item_auto_selects_the_matching_category()
    {
        [, , $food] = $this->signIn();

        Livewire::test(LogExpense::class)
            ->set('item_name', 'Chicken burger')
            ->assertSet('expense_category_id', $food->id)
            ->assertSet('categoryAutoPicked', true);
    }

    public function test_an_auto_picked_category_is_cleared_when_the_item_no_longer_matches()
    {
        $this->signIn();

        Livewire::test(LogExpense::class)
            ->set('item_name', 'Chicken burger')
            ->set('item_name', 'something else entirely')
            ->assertSet('expense_category_id', null)
            ->assertSet('categoryAutoPicked', false);
    }

    public function test_a_manual_choice_is_not_overwritten_but_a_nudge_is_shown()
    {
        [, , $food, $transport] = $this->signIn();

        Livewire::test(LogExpense::class)
            ->set('expense_category_id', $transport->id)
            ->set('item_name', 'Chicken burger')
            ->assertSet('expense_category_id', $transport->id)
            ->assertSet('suggestedCategoryId', $food->id)
            ->assertSet('suggestedCategoryName', 'Food & Dining');
    }

    public function test_saving_is_blocked_until_the_mismatch_is_dismissed()
    {
        [$user, , , $transport] = $this->signIn();

        Livewire::test(LogExpense::class)
            ->set('expense_category_id', $transport->id)
            ->set('item_name', 'Chicken burger')
            ->set('amount', '50')
            ->call('storeExpense')
            ->assertHasErrors(['item_name']);

        $this->assertSame(0, Expense::where('user_id', $user->id)->count());

        Livewire::test(LogExpense::class)
            ->set('expense_category_id', $transport->id)
            ->set('item_name', 'Chicken burger')
            ->set('amount', '50')
            ->call('dismissSuggestion')
            ->call('storeExpense')
            ->assertHasNoErrors()
            ->assertRedirect(route('student.dashboard'));

        $this->assertSame(1, Expense::where('user_id', $user->id)->count());
    }

    public function test_accepting_the_suggestion_switches_the_category()
    {
        [, , $food, $transport] = $this->signIn();

        Livewire::test(LogExpense::class)
            ->set('expense_category_id', $transport->id)
            ->set('item_name', 'Chicken burger')
            ->call('acceptSuggestedCategory')
            ->assertSet('expense_category_id', $food->id)
            ->assertSet('suggestedCategoryId', null);
    }

    public function test_the_catch_all_category_never_blocks_saving()
    {
        [$user] = $this->signIn();
        $other  = $this->makeCategory('Other', ['is_fallback' => true]);

        Livewire::test(LogExpense::class)
            ->set('expense_category_id', $other->id)
            ->set('item_name', 'Chicken burger')
            ->set('amount', '50')
            ->call('storeExpense')
            ->assertHasNoErrors();

        $this->assertSame(1, Expense::where('user_id', $user->id)->count());
    }

    // ---------------------------------------------------------------
    // Save & add another / session log
    // ---------------------------------------------------------------

    public function test_save_and_add_another_keeps_the_form_open_and_logs_the_entry()
    {
        [$user, $budget] = $this->signIn();

        $component = Livewire::test(LogExpense::class)
            ->set('item_name', 'Chicken burger')
            ->set('amount', '50')
            ->call('storeAndAddAnother')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('item_name', null)
            ->assertSet('amount', null)
            ->assertSet('expense_category_id', null);

        $log = $component->get('sessionLog');
        $this->assertCount(1, $log);
        $this->assertSame('Chicken burger', $log[0]['item_name']);
        $this->assertSame('Food & Dining', $log[0]['category_name']);
        $this->assertEquals(950.00, $budget->fresh()->remaining_allowance);
    }

    public function test_removing_an_entry_from_the_session_log_refunds_it()
    {
        [$user, $budget] = $this->signIn();

        $component = Livewire::test(LogExpense::class)
            ->set('item_name', 'Chicken burger')
            ->set('amount', '50')
            ->call('storeAndAddAnother');

        $id = $component->get('sessionLog')[0]['id'];
        $component->call('removeFromSessionLog', $id);

        $this->assertSame([], $component->get('sessionLog'));
        $this->assertDatabaseMissing('expenses', ['id' => $id]);
        $this->assertEquals(1000.00, $budget->fresh()->remaining_allowance);
    }

    // ---------------------------------------------------------------
    // Quick add
    // ---------------------------------------------------------------

    public function test_quick_add_lists_frequent_items_and_fills_the_form()
    {
        [$user, , $food] = $this->signIn();
        $past = $this->makeExpense($user, $food, 80.00, '2026-10-06 12:00:00', ['item_name' => 'Chicken lunch']);

        Livewire::test(LogExpense::class)
            ->assertSee('Chicken lunch')
            ->call('useFrequent', $past->id)
            ->assertSet('item_name', 'Chicken lunch')
            ->assertSet('amount', 80)
            ->assertSet('expense_category_id', $food->id);
    }

    public function test_repeating_an_expense_logs_it_again_today()
    {
        [$user, $budget, $food] = $this->signIn();
        $past = $this->makeExpense($user, $food, 80.00, '2026-10-06 12:00:00', ['item_name' => 'Chicken lunch']);

        $component = Livewire::test(LogExpense::class)->call('repeatExpense', $past->id);

        $this->assertSame(2, Expense::where('user_id', $user->id)->count());
        $this->assertCount(1, $component->get('sessionLog'));
        $this->assertEquals(920.00, $budget->fresh()->remaining_allowance);
        $this->assertSame(
            '2026-10-07',
            Expense::where('user_id', $user->id)->latest('id')->first()->transaction_date->format('Y-m-d')
        );
    }

    public function test_quick_add_ignores_other_students_expenses()
    {
        [, , $food] = $this->signIn();
        $theirs = $this->makeExpense($this->makeStudent(), $food, 80.00, '2026-10-06 12:00:00', ['item_name' => 'Secret meal']);

        Livewire::test(LogExpense::class)
            ->call('useFrequent', $theirs->id)
            ->assertSet('item_name', null);
    }

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    public function test_required_fields()
    {
        $this->signIn();

        Livewire::test(LogExpense::class)
            ->call('storeExpense')
            ->assertHasErrors(['item_name', 'amount', 'expense_category_id']);
    }

    public function test_dates_before_the_cycle_or_in_the_future_are_rejected()
    {
        [, , $food] = $this->signIn();

        $base = Livewire::test(LogExpense::class)
            ->set('item_name', 'Lunch')
            ->set('amount', '50')
            ->set('expense_category_id', $food->id);

        $base->set('transaction_date', '2026-10-01')->call('storeExpense')->assertHasErrors(['transaction_date']);
        $base->set('transaction_date', '2026-10-09')->call('storeExpense')->assertHasErrors(['transaction_date']);
        $base->set('transaction_date', '2026-10-05')->call('storeExpense')->assertHasNoErrors();
    }

    public function test_disabled_categories_cannot_be_used()
    {
        $this->signIn();
        $hidden = $this->makeCategory('Hidden', ['status' => 'disabled']);

        Livewire::test(LogExpense::class)
            ->set('item_name', 'Lunch')
            ->set('amount', '50')
            ->set('expense_category_id', $hidden->id)
            ->call('storeExpense')
            ->assertHasErrors(['expense_category_id']);
    }

    // ---------------------------------------------------------------
    // Side effects
    // ---------------------------------------------------------------

    public function test_a_large_purchase_raises_an_alert()
    {
        [$user, , $food] = $this->signIn();

        Livewire::test(LogExpense::class)
            ->set('item_name', 'Textbook')
            ->set('amount', '400')
            ->set('expense_category_id', $food->id)
            ->call('storeExpense')
            ->assertHasNoErrors();

        $alerts = DatabaseNotification::where('notifiable_id', $user->id)->get()
            ->filter(fn ($n) => ($n->data['anomaly_type'] ?? null) === 'large_transaction');

        $this->assertCount(1, $alerts);
    }

    public function test_logging_an_expense_resolves_the_no_expense_logs_alert()
    {
        [$user, , $food] = $this->signIn();
        $alert = DatabaseNotification::create([
            'id'              => (string) Str::uuid(),
            'type'            => 'App\Notifications\NoExpenseLogsWarning',
            'notifiable_type' => User::class,
            'notifiable_id'   => $user->id,
            'data'            => ['anomaly_type' => 'no_expense_logs', 'description' => 'x', 'resolved' => false],
        ]);

        Livewire::test(LogExpense::class)
            ->set('item_name', 'Lunch')
            ->set('amount', '50')
            ->set('expense_category_id', $food->id)
            ->call('storeExpense');

        $this->assertTrue((bool) $alert->fresh()->data['resolved']);
    }

    // ---------------------------------------------------------------
    // Arriving from the simulator
    // ---------------------------------------------------------------

    public function test_the_page_is_prefilled_from_the_simulator_query_string()
    {
        $this->signIn();

        $this->get('/expenses/create?item=Milk%20Tea&amount=50')
            ->assertOk()
            ->assertSee('Milk Tea');
    }

    public function test_prefilled_item_names_have_html_stripped()
    {
        $this->signIn();

        $this->get('/expenses/create?item=' . urlencode('<script>alert(1)</script>Pizza'))
            ->assertOk()
            ->assertSee('Pizza')
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}