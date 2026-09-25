<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySuggester
{
    private const OWN_MIN_USES        = 2;
    private const CROWD_MIN_USES      = 3;
    private const CROWD_MIN_STUDENTS  = 2;
    private const CROWD_MIN_AGREEMENT = 0.70;
    private const CROWD_CACHE_SECONDS = 600;

    /**
     * Returns the most likely category for an item name, or null when there is
     * no confident answer. Never returns the fallback ("Other") category, a
     * disabled category, or Savings.
     *
     * Priority: student's own history -> crowd history -> admin keywords / category names.
     */
    public function suggest($user, string $itemName): ?ExpenseCategory
    {
        $term = Str::lower(trim(preg_replace('/\s+/', ' ', $itemName)));

        if (mb_strlen($term) < 3) {
            return null;
        }

        // 1. The student's own habit for this exact item.
        $own = Expense::where('user_id', $user->id)
            ->whereNull('savings_goal_id')
            ->whereRaw('LOWER(item_name) = ?', [$term])
            ->select('expense_category_id', DB::raw('COUNT(*) as uses'))
            ->groupBy('expense_category_id')
            ->orderByDesc('uses')
            ->first();

        if ($own && $own->uses >= self::OWN_MIN_USES) {
            $category = ExpenseCategory::selectable()->find($own->expense_category_id);

            if ($category) {
                // A consistent habit of filing this item under "Other" is respected — no nudge.
                return $category->is_fallback ? null : $category;
            }
        }

        // 2. What other students consistently do with this exact item.
        $crowdId = $this->crowdCategoryId($term);

        if ($crowdId) {
            $category = ExpenseCategory::selectable()->where('is_fallback', false)->find($crowdId);

            if ($category) {
                return $category;
            }
        }

        // 3. Admin keywords + category-name words.
        return $this->fromKeywords($term);
    }

    private function crowdCategoryId(string $term): ?int
    {
        // Wrapped in an array so a "no result" is cached too (Cache::remember skips null).
        $cached = Cache::remember('category_crowd:' . md5($term), self::CROWD_CACHE_SECONDS, function () use ($term) {
            $rows = Expense::whereNull('savings_goal_id')
                ->whereRaw('LOWER(item_name) = ?', [$term])
                ->select(
                    'expense_category_id',
                    DB::raw('COUNT(*) as uses'),
                    DB::raw('COUNT(DISTINCT user_id) as students')
                )
                ->groupBy('expense_category_id')
                ->orderByDesc('uses')
                ->get();

            $top   = $rows->first();
            $total = (int) $rows->sum('uses');

            if (
                !$top
                || $total === 0
                || $top->uses < self::CROWD_MIN_USES
                || $top->students < self::CROWD_MIN_STUDENTS
                || ($top->uses / $total) < self::CROWD_MIN_AGREEMENT
            ) {
                return ['id' => null];
            }

            return ['id' => (int) $top->expense_category_id];
        });

        return $cached['id'] ?? null;
    }

    private function fromKeywords(string $term): ?ExpenseCategory
    {
        $scores = [];

        foreach (ExpenseCategory::keywordMap() as $entry) {
            $score = 0;

            foreach ($entry['keywords'] as $keyword) {
                if ($this->containsWord($term, $keyword)) {
                    // Multi-word keywords ("milk tea") outweigh single words.
                    $score += substr_count($keyword, ' ') + 1;
                }
            }

            foreach ($entry['name_tokens'] as $token) {
                if ($this->containsWord($term, $token)) {
                    $score += 1;
                }
            }

            if ($score > 0) {
                $scores[$entry['id']] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        arsort($scores);
        $ids = array_keys($scores);

        // Two categories tied at the top = ambiguous. Let the student decide.
        if (isset($ids[1]) && $scores[$ids[0]] === $scores[$ids[1]]) {
            return null;
        }

        return ExpenseCategory::selectable()->where('is_fallback', false)->find($ids[0]);
    }

    // Whole-word match; tolerates a plural suffix ("books", "fares").
    private function containsWord(string $haystack, string $needle): bool
    {
        $pattern = '/(?<![a-z0-9])' . preg_quote($needle, '/') . '(?:s|es)?(?![a-z0-9])/u';

        return (bool) preg_match($pattern, $haystack);
    }
}