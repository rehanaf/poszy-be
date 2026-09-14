<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Penting untuk Sanctum

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'profile_image_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    /**
     * Toko-toko yang dapat diakses user (via pivot store_user, berisi role per toko).
     */
    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_user')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    /**
     * Role user pada sebuah toko (null jika tidak punya akses).
     */
    public function roleInStore(?int $storeId): ?string
    {
        if ($storeId === null) {
            return null;
        }

        return $this->stores()->where('store_user.store_id', $storeId)->value('store_user.role');
    }

    /**
     * Cek apakah user punya akses ke toko tertentu.
     */
    public function hasStoreAccess(?int $storeId): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if ($storeId === null) {
            return false;
        }

        return $this->stores()->where('store_user.store_id', $storeId)->exists();
    }

    // Relasi
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}