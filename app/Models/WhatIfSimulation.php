<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatIfSimulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'item_title',
        'amount',
        'simulated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
