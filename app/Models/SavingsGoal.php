<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsGoal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'target_name',
        'target_amount',
        'current_saved',
        'target_date',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
