<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// app/Models/ExpenseCategory.php

class ExpenseCategory extends Model
{
    use HasFactory;

    // Mirrors App\Http\Livewire\Admin\ExpenseCategories::PALETTE — the hex
    // equivalents of each Tailwind 500-shade swatch the admin picks from.
    public const COLOR_HEX_MAP = [
        'bg-amber-50 text-amber-600'     => '#f59e0b',
        'bg-blue-50 text-blue-600'       => '#3b82f6',
        'bg-emerald-50 text-emerald-600' => '#10b981',
        'bg-cyan-50 text-cyan-600'       => '#06b6d4',
        'bg-purple-50 text-purple-600'   => '#a855f7',
        'bg-pink-50 text-pink-600'       => '#ec4899',
        'bg-indigo-50 text-indigo-600'   => '#6366f1',
        'bg-rose-50 text-rose-600'       => '#f43f5e',
        'bg-orange-50 text-orange-600'   => '#f97316',
        'bg-slate-100 text-slate-600'    => '#94a3b8',
    ];

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
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }
}