<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory, HasStore;

    protected $fillable = [
        'user_id',
        'customer_id',
        'customer_name', // Tetap diisi
        'order_date',
        'receipt_number',
        'total_amount',
        'amount_paid',
        'change_due',
        'payment_method_id',
        'payment_status',
        'discount_amount',
        'discount_type',
        'tax_amount',
        'points_earned',
        'points_redeemed',
        'points_discount',
        'status',
        'cashier_name', // Tetap diisi
        'store_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    // Relasi
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}