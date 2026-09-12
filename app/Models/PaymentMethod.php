<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory, HasStore;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'store_id',
    ];

    // Relasi
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}