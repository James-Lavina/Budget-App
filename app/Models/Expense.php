<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'expense_category_id',
        'merchant_name',
        'item_name',
        'amount',
        'transaction_date',
        'tracking_type',
    ];

    // Inverse: This transaction belongs to one student
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Inverse: This transaction maps to one specific category for Chart.js grouping
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    // One-to-One: An expense can optionally have exactly one attached receipt file profile
    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}
