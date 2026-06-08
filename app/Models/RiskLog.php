<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'anomaly_type',
        'severity_tier',
        'description',
        'resolved',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
