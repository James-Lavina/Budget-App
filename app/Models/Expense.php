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
        'savings_goal_id',
        'merchant_name',
        'item_name',
        'amount',
        'transaction_date',
        'tracking_type',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount' => 'float',
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

    public function savingsGoal()
    {
        return $this->belongsTo(SavingsGoal::class);
    }

    // Dynamic icon type, pulled directly from the category row - no hardcoded name matching.
    public function getIconTypeAttribute()
    {
        return $this->category->icon ?? 'default';
    }

    // Dynamic icon background/text color, same source.
    public function getIconBgColorAttribute()
    {
        return $this->category->color ?? 'bg-slate-100 text-slate-600';
    }

    // Shared human-friendly date formatting, used by both Dashboard and All Expenses views.
    public function getFormattedDateAttribute()
    {
        $date = \Carbon\Carbon::parse($this->transaction_date);

        if ($date->isToday()) {
            return 'Today, ' . $date->format('g:i A');
        } elseif ($date->isYesterday()) {
            return 'Yesterday, ' . $date->format('g:i A');
        } elseif ($date->greaterThan(now()->subDays(7))) {
            return $date->format('D, g:i A');
        }

        return $date->format('M d, Y • g:i A');
    }
}