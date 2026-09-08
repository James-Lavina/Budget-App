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