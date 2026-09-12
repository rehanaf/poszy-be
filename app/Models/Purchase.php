<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory, HasStore;

    protected $fillable = [
        'supplier_id',
        'user_id',
        'purchase_date',
        'total_amount',
        'status',
        'notes',
        'store_id',
    ];

    protected $casts = [
        'purchase_date' => 'datetime',
    ];

    // Relasi
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}