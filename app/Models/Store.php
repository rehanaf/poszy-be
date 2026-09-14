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
     * tanpa konteks user (seeder, superadmin).
     */
    public static function defaultId(): ?int
    {
        return static::query()->orderBy('id')->value('id');
    }

    /**
     * Pemilik toko.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * User yang dapat mengelola toko ini (via pivot store_user, berisi role).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'store_user')
            ->withPivot(['role'])
            ->withTimestamps();
    }
}