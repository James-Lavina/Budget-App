<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ExpenseCategory;
use App\Models\IntegrationSetting;
use App\Models\RiskSetting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\BuildsBudgetData;
use Tests\TestCase;

class ModelHelpersTest extends TestCase
{
    use RefreshDatabase, BuildsBudgetData;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-07 12:00:00'); // Wednesday
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // AppSetting
    // ---------------------------------------------------------------

    public function test_app_settings_are_created_with_defaults_and_cached()
    {
        $settings = AppSetting::current();

        $this->assertSame('BudgetWise', $settings->application_name);
        $this->assertSame('#4f39fa', $settings->primary_color);
        $this->assertTrue($settings->email_notifications_enabled);
        $this->assertFalse($settings->maintenance_mode_enabled);

        DB::table('app_settings')->update(['application_name' => 'Changed']);
        $this->assertSame('BudgetWise', AppSetting::current()->application_name); // served from cache

        AppSetting::flush();
        $this->assertSame('Changed', AppSetting::current()->application_name);
    }

    public function test_brand_color_helpers()
    {
        $settings = AppSetting::current();

        $this->assertSame('79, 57, 250', $settings->primaryColorRgb());
        $this->assertSame('#412fcd', $settings->primaryColorDark());

        $settings->primary_color = '#fff';
        $this->assertSame('255, 255, 255', $settings->primaryColorRgb());

        $settings->primary_color = null;
        $this->assertSame('79, 57, 250', $settings->primaryColorRgb());
    }

    // ---------------------------------------------------------------
    // ExpenseCategory
    // ---------------------------------------------------------------

    public function test_keywords_are_normalised()
    {
        $this->assertSame(
            'chicken,milk tea,burger',
            ExpenseCategory::normalizeKeywords("Chicken, milk tea ,chicken\nBurger")
        );
        $this->assertSame('a,b,c', ExpenseCategory::normalizeKeywords('a;b;;c'));
        $this->assertSame('', ExpenseCategory::normalizeKeywords(null));
    }

    public function test_blank_keywords_are_stored_as_null_and_listed_as_empty()
    {
        $category = $this->makeCategory('Blank', ['keywords' => ' , ']);

        $this->assertNull($category->fresh()->keywords);
        $this->assertSame([], $category->keywordList());

        $filled = $this->makeCategory('Filled', ['keywords' => 'one, two']);
        $this->assertSame(['one', 'two'], $filled->keywordList());
    }

    public function test_name_tokens_skip_stopwords_and_short_words()
    {
        $this->assertSame(['food', 'dining'], $this->makeCategory('Food & Dining')->nameTokens());
        $this->assertSame(['academics', 'supplies'], $this->makeCategory('Academics & Supplies')->nameTokens());
        $this->assertSame([], $this->makeCategory('Other Misc')->nameTokens());
        $this->assertSame(['shows'], $this->makeCategory('TV Shows')->nameTokens());
    }

    public function test_color_classes_map_to_chart_hex_values()
    {
        $this->assertSame('#059669', ExpenseCategory::colorToHex('bg-emerald-50 text-emerald-700'));
        $this->assertSame('#94a3b8', ExpenseCategory::colorToHex('bg-unknown-50 text-unknown-700'));
        $this->assertSame('#94a3b8', ExpenseCategory::colorToHex(null));
    }

    public function test_selectable_scope_and_fallback_lookup()
    {
        $food     = $this->makeCategory('Food & Dining');
        $disabled = $this->makeCategory('Disabled', ['status' => 'disabled']);
        $savings  = $this->makeCategory('Savings');

        $this->assertEquals([$food->id], ExpenseCategory::selectable()->pluck('id')->all());
        $this->assertNull(ExpenseCategory::fallback());

        $other = $this->makeCategory('Other', ['is_fallback' => true]);
        $this->assertSame($other->id, ExpenseCategory::fallback()->id);

        $other->update(['status' => 'disabled']);
        $this->assertNull(ExpenseCategory::fallback());
    }

    public function test_saving_a_category_flushes_the_keyword_map_cache()
    {
        $first = $this->makeCategory('Food & Dining', ['keywords' => 'burger']);
        $this->assertCount(1, ExpenseCategory::keywordMap());

        $this->makeCategory('Transportation', ['keywords' => 'jeepney']);
        $this->assertCount(2, ExpenseCategory::keywordMap());

        $first->delete();
        $this->assertCount(1, ExpenseCategory::keywordMap());
    }

    // ---------------------------------------------------------------
    // Expense
    // ---------------------------------------------------------------

    public function test_expense_dates_are_human_friendly()
    {
        $user = $this->makeStudent();
        $cat  = $this->makeCategory();

        $cases = [
            '2026-10-07 09:05:00' => 'Today, 9:05 AM',
            '2026-10-06 18:30:00' => 'Yesterday, 6:30 PM',
            '2026-10-03 10:30:00' => 'Sat, 10:30 AM',
            '2026-09-20 10:30:00' => 'Sep 20, 2026 • 10:30 AM',
        ];

        foreach ($cases as $date => $expected) {
            $this->assertSame($expected, $this->makeExpense($user, $cat, 10.00, $date)->formatted_date, $date);
        }
    }

    public function test_expense_icon_and_color_come_from_the_category()
    {
        $user = $this->makeStudent();
        $cat  = $this->makeCategory('Transportation', ['icon' => 'truck', 'color' => 'bg-blue-50 text-blue-700']);

        $expense = $this->makeExpense($user, $cat, 10.00);

        $this->assertSame('truck', $expense->icon_type);
        $this->assertSame('bg-blue-50 text-blue-700', $expense->icon_bg_color);
    }

    // ---------------------------------------------------------------
    // User
    // ---------------------------------------------------------------

    public function test_wants_email_requires_both_the_student_and_the_admin_switch()
    {
        $on  = $this->makeStudent(['email_notifications_enabled' => true]);
        $off = $this->makeStudent(['email_notifications_enabled' => false]);

        $this->assertTrue($on->wantsEmail());
        $this->assertFalse($off->wantsEmail());

        AppSetting::current()->update(['email_notifications_enabled' => false]);
        AppSetting::flush();

        $this->assertFalse($on->wantsEmail());
    }

    public function test_super_admin_check()
    {
        $this->assertTrue($this->makeAdmin('super_admin')->isSuperAdmin());
        $this->assertFalse($this->makeAdmin('admin')->isSuperAdmin());
        $this->assertFalse($this->makeStudent()->isSuperAdmin());
    }

    public function test_latest_weekly_budget_is_the_newest_row()
    {
        $user = $this->makeStudent();
        $old  = $this->makeBudget($user, 100.00);
        DB::table('weekly_budgets')->where('id', $old->id)->update(['created_at' => Carbon::now()->subWeek()]);
        $new = $this->makeBudget($user, 900.00, ['cycle_start_date' => '2026-10-05']);

        $this->assertSame($new->id, $user->fresh()->latestWeeklyBudget->id);
    }

    // ---------------------------------------------------------------
    // Settings tables
    // ---------------------------------------------------------------

    public function test_the_groq_api_key_is_encrypted_at_rest()
    {
        IntegrationSetting::create(['groq_api_key' => 'gsk_secret_value']);
        IntegrationSetting::flush();

        $raw = DB::table('integration_settings')->value('groq_api_key');

        $this->assertNotSame('gsk_secret_value', $raw);
        $this->assertStringNotContainsString('gsk_secret_value', $raw);
        $this->assertSame('gsk_secret_value', IntegrationSetting::current()->groq_api_key);
    }

    public function test_integration_settings_are_created_with_defaults()
    {
        $settings = IntegrationSetting::current();

        $this->assertSame('qwen/qwen3.6-27b', $settings->groq_vision_model);
        $this->assertSame('openai/gpt-oss-120b', $settings->groq_text_model);
        $this->assertEquals(0.5, $settings->groq_temperature);
        $this->assertSame(400, $settings->groq_max_tokens);
        $this->assertNull($settings->groq_api_key);
    }

    public function test_risk_settings_defaults_are_loaded_and_the_cache_refreshes_on_save()
    {
        $this->assertEquals(80, RiskSetting::current()->overspending_threshold);

        RiskSetting::current()->update(['overspending_threshold' => 60]);

        $this->assertEquals(60, RiskSetting::current()->overspending_threshold);
    }
}