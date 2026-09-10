<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_code',
        'customer_type',
        'name',
        'email',
        'phone',
        'address',
        'profile_image_url',
        'points_balance',
    ];

    // Relasi
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}