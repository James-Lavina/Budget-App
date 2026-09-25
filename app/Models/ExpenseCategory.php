<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ExpenseCategory extends Model
{
    use HasFactory;

    public const KEYWORD_MAP_CACHE_KEY = 'category_keyword_map';

    // Mirrors App\Http\Livewire\Admin\ExpenseCategories::PALETTE — the hex
    // equivalents of each Tailwind 500-shade swatch the admin picks from.
    public const COLOR_HEX_MAP = [
        'bg-amber-50 text-amber-700'     => '#d97706',
        'bg-orange-50 text-orange-700'   => '#ea580c',
        'bg-rose-50 text-rose-700'       => '#e11d48',
        'bg-pink-50 text-pink-700'       => '#db2777',
        'bg-fuchsia-50 text-fuchsia-700' => '#c026d3',
        'bg-purple-50 text-purple-700'   => '#9333ea',
        'bg-indigo-50 text-indigo-700'   => '#4f46e5',
        'bg-blue-50 text-blue-700'       => '#2563eb',
        'bg-cyan-50 text-cyan-700'       => '#0891b2',
        'bg-emerald-50 text-emerald-700' => '#059669',
        'bg-lime-50 text-lime-700'       => '#65a30d',
        'bg-slate-100 text-slate-700'    => '#64748b',
    ];

    // Words in a category name that say nothing about what the category contains.
    private const NAME_STOPWORDS = ['and', 'the', 'for', 'with', 'other', 'others', 'misc', 'general', 'miscellaneous'];

    /**
     * Resolve a stored Tailwind color-class string (e.g. from the admin
     * panel) into a hex value Chart.js can render directly.
     */
    public static function colorToHex(?string $colorClass): string
    {
        return self::COLOR_HEX_MAP[$colorClass] ?? '#94a3b8';
    }

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
        'status',
        'keywords',
        'is_fallback',
    ];

    protected $casts = [
        'is_fallback' => 'boolean',
    ];

    protected static function booted()
    {
        $flush = function () {
            Cache::forget(self::KEYWORD_MAP_CACHE_KEY);
        };

        static::saved($flush);
        static::deleted($flush);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }

    /**
     * Categories a student can actually pick: enabled and not the Savings category
     * (Savings is only ever applied automatically through a goal contribution).
     */
    public function scopeSelectable($query)
    {
        return $query->where('status', 'enabled')
            ->whereRaw('LOWER(name) NOT LIKE ?', ['%savings%']);
    }

    /**
     * The catch-all ("Other") category, or null if the admin hasn't designated one
     * or it is currently disabled.
     */
    public static function fallback(): ?self
    {
        return static::selectable()->where('is_fallback', true)->first();
    }

    /**
     * "Chicken, milk tea ,chicken\nBurger" -> "chicken,milk tea,burger"
     */
    public static function normalizeKeywords(?string $value): string
    {
        return collect(preg_split('/[,\n;]+/', (string) $value))
            ->map(fn ($k) => Str::lower(trim(preg_replace('/\s+/', ' ', $k))))
            ->filter()
            ->unique()
            ->values()
            ->implode(',');
    }

    public function setKeywordsAttribute($value)
    {
        $normalized = self::normalizeKeywords($value);

        $this->attributes['keywords'] = $normalized === '' ? null : $normalized;
    }

    public function keywordList(): array
    {
        return $this->keywords
            ? array_values(array_filter(explode(',', $this->keywords)))
            : [];
    }

    /**
     * Significant words from the category name. A brand-new category named
     * "Groceries" works as a detector immediately, before the admin adds keywords.
     */
    public function nameTokens(): array
    {
        return collect(preg_split('/[^a-z0-9]+/', Str::lower($this->name)))
            ->filter(fn ($t) => mb_strlen($t) >= 4 && !in_array($t, self::NAME_STOPWORDS, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Detection dictionary for every selectable, non-fallback category. Cached and
     * flushed automatically on any category save or delete.
     *
     * @return array<int, array{id:int, keywords:array, name_tokens:array}>
     */
    public static function keywordMap(): array
    {
        return Cache::rememberForever(self::KEYWORD_MAP_CACHE_KEY, function () {
            return static::selectable()
                ->where('is_fallback', false)
                ->orderBy('name')
                ->get()
                ->map(fn ($c) => [
                    'id'          => $c->id,
                    'keywords'    => $c->keywordList(),
                    'name_tokens' => $c->nameTokens(),
                ])
                ->all();
        });
    }
}