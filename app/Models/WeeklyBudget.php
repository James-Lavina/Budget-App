<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_allowance',
        'remaining_allowance',
        'reset_day',
        'cycle_start_date',
    ];

    // Inverse: This budget baseline belongs strictly to one student
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
