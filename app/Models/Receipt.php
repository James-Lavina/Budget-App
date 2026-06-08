<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'expense_id',
        'image_path',
        'raw_ocr_text',
        'status',
    ];

    // Inverse: This image upload belongs to one user account
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Inverse One-to-One: This receipt links back to its validated expense row
    public function expense()
    {
        return $this->belongsTo(Expense::class);
    }
}
