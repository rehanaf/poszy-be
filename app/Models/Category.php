<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory, HasStore;

    protected $fillable = [
        'name',
        'description',
        'store_id',
    ];

    // Relasi
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}