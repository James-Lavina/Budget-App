<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'default_allowance',  
        'default_reset_day',  
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // One-to-Many: A user can track multiple weekly budget cycles over time
    public function weeklyBudgets()
    {
        return $this->hasMany(WeeklyBudget::class);
    }

    // One-to-Many: A user can log many individual expenses
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    // One-to-Many: A user can upload multiple receipt images
    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    // One-to-Many: A user can set multiple financial goals
    public function savingsGoals()
    {
        return $this->hasMany(SavingsGoal::class);
    }

    // One-to-Many: A user can create many scenarios in the simulator
    public function whatIfSimulations()
    {
        return $this->hasMany(WhatIfSimulation::class);
    }

    // One-to-Many: A user can trigger multiple automated behavioral alerts
    public function riskLogs()
    {
        return $this->hasMany(RiskLog::class);
    }

    // One-to-Many: A user generates security and administrative audit footprint records
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
