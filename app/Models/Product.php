<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasStore;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'description',
        'price',
        'stock', // Nullable
        'unit',
        'image_url',
        'is_active',
        'discount',
        'points_earn',
        'store_id',
    ];

    // Relasi
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function getImageUrlAttribute($value)
    {
        if ($value) {
            return url($value);
        }
        return null;
    }
}
