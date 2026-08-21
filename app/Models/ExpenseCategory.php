<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color',
    ];

    // One-to-Many: A category can belong to many different logged expenses
    public function expenses()
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }
}