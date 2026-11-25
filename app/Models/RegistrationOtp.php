<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationOtp extends Model
{
    protected $fillable = [
        'email',
        'name',
        'school',
        'password',
        'code_hash',
        'attempts',
        'last_sent_at',
        'expires_at',
    ];

    protected $hidden = ['password', 'code_hash'];

    protected $casts = [
        'last_sent_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];
}