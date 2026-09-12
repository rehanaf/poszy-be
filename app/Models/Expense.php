<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory, HasStore;

    protected $fillable = [
        'user_id',
        'description',
        'amount',
        'expense_date',
        'category',
        'notes',
        'store_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
    ];

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}