<?php

namespace App\Support;

/**
 * Konteks store untuk request saat ini.
 * Dipakai oleh global scope HasStore agar tidak memicu recursion
 * (menghindari auth('sanctum')->user() di dalam scope model User).
 */
class CurrentStore
{
    private static ?int $storeId = null;

    /**
     * Set store aktif untuk request ini. null = tanpa filter
     * (superadmin / CLI / seeder).
     */
    public static function set(?int $storeId): void
    {
        self::$storeId = $storeId;
    }

    public static function current(): ?int
    {
        return self::$storeId;
    }

    public static function reset(): void
    {
        self::$storeId = null;
    }
}