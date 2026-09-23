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
        'plan',
        'plan_expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'plan_expires_at' => 'datetime',
    ];

    /**
     * Sajikan URL logo via rute /api/logo (ber-CORS) agar bisa dimuat
     * dan digambar ke canvas lintas origin untuk berbagi struk.
     */
    public function getLogoUrlAttribute($value)
    {
        if (! $value) {
            return null;
        }
        // URL lama masih /storage/... -> ubah ke rute CORS
        if (str_contains($value, '/storage/')) {
            $name = \Illuminate\Support\Str::afterLast($value, '/');
            $name = \Illuminate\Support\Str::before($name, '?');
            $url = preg_replace('#^https?://#', 'https://', (string) config('app.url'));
            return rtrim($url, '/') . '/api/logo/' . rawurlencode($name);
        }
        return $value;
    }

    /**
     * Cek apakah toko pada paket gratis.
     */
    public function isFree(): bool
    {
        return ($this->plan ?? 'free') === 'free';
    }

    /**
     * Cek apakah toko pada paket berbayar/pro.
     */
    public function isPro(): bool
    {
        return ($this->plan ?? 'free') === 'pro';
    }

    /**
     * Batas maksimal pengguna toko berdasarkan tier plan.
     */
    public function maxUsers(): int
    {
        return $this->isPro() ? 999 : 2; // Paket Free: maks 2 user (1 Owner + 1 Kasir)
    }

    /**
     * Apakah kuota user untuk toko ini sudah habis.
     */
    public function hasReachedUserLimit(): bool
    {
        return $this->users()->count() >= $this->maxUsers();
    }

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