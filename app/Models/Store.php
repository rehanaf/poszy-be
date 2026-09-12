<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name',
        'tagline',
        'address',
        'phone',
        'nip',
        'logo_url',
        'footer',
        'default_receipt_size',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * ID toko default (toko pertama). Dipakai untuk data yang dibuat
     * tanpa konteks user (seeder, register publik, superadmin).
     */
    public static function defaultId(): ?int
    {
        return static::query()->orderBy('id')->value('id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'store_id');
    }
}