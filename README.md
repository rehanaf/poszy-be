# POSZY Backend

REST API Laravel untuk **POSZY - Point Of Sale** (management produk, kategori, pelanggan, pengguna, pembayaran, dan transaksi).

## Teknologi

- [Laravel](https://laravel.com) 12
- PHP 8.2+
- SQLite (bisa diganti MySQL/Postgres via `.env`)
- [Sanctum](https://laravel.com/docs/sanctum) (API token)
- Sanctum (API token)

## Cara Menjalankan

```bash
git clone https://github.com/rehanaf/poszy-be.git
cd poszy-be

composer install
cp .env.example .env
php artisan key:generate
```

Konfigurasi database (default SQLite):

```bash
touch database/database.sqlite
```

Jalankan migrasi + seeder:

```bash
php artisan migrate:fresh --seed
```

Jalankan server:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Akun Default (Seeded)

| Role     | Email              | Password   |
| -------- | ------------------ | ---------- |
| Admin    | admin@example.com  | `password` |
| Kasir    | cashier@example.com| `password` |
| User     | user@example.com   | `password` |

## Endpoint Utama

| Metode | Endpoint                     | Keterangan                |
| ------ | ---------------------------- | ------------------------- |
| POST   | `/api/login`                 | Login, dapat token        |
| GET    | `/api/products`              | Daftar produk             |
| GET    | `/api/products/all`          | Semua produk (POS)        |
| POST   | `/api/products`              | Buat produk               |
| GET    | `/api/categories`            | Daftar kategori           |
| GET    | `/api/customers`             | Daftar pelanggan          |
| GET    | `/api/orders`                | Daftar transaksi          |
| POST   | `/api/orders`                | Buat transaksi            |
| GET    | `/api/orders/{id}/pdf`       | Unduh struk PDF           |
| GET    | `/api/orders/{id}/print`     | Cetak struk (HTML)        |
| GET    | `/api/users`                 | Daftar pengguna (admin)   |

> Semua endpoint selain `/login` membutuhkan header `Authorization: Bearer <token>`.

## CORS

`config/cors.php` sudah diatur `allowed_origins => ['*']` sehingga bisa dipanggil dari FE mana pun (Vercel, Capacitor, dll).

## Struktur

```
app/Http/Controllers/  → controller API
app/Models/            → model Eloquent
database/factories/    → factory & seeder
resources/views/       → blade (receipt/struk)
routes/web.php         → route middleware auth
routes/api.php         → endpoint API
```