<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Supplier;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents; // Uncomment if needed
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Untuk Hash password

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Hapus semua data yang ada sebelum seeding (opsional, untuk clean slate)
        // Order::truncate();
        // OrderItem::truncate();
        // Purchase::truncate();
        // PurchaseItem::truncate();
        // Shift::truncate();
        // Expense::truncate();
        // Product::truncate();
        // Category::truncate();
        // Customer::truncate();
        // PaymentMethod::truncate();
        // Supplier::truncate();
        // User::truncate(); // Hati-hati dengan ini jika sudah ada user admin yang penting

        // Membuat User: Admin, Cashier, User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'profile_image_url' => 'https://i.pravatar.cc/150?img=1',
        ]);

        User::create([
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'password' => Hash::make('password'),
            'role' => 'cashier',
            'profile_image_url' => 'https://i.pravatar.cc/150?img=2',
        ]);

        User::create([
            'name' => 'General User',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'profile_image_url' => 'https://i.pravatar.cc/150?img=3',
        ]);

        // Membuat 5 Kategori
        Category::factory()->count(5)->create();

        // Membuat 50 Produk
        Product::factory()->count(50)->create();

        // Membuat 20 Pelanggan
        Customer::factory()->count(20)->create();

        // Membuat 5 Metode Pembayaran (jika belum ada)
        // Kita hanya membuat yang umum jika tabel kosong
        if (PaymentMethod::count() === 0) {
            PaymentMethod::create(['name' => 'Cash', 'description' => 'Tunai', 'is_active' => true]);
            PaymentMethod::create(['name' => 'Credit Card', 'description' => 'Kartu Kredit', 'is_active' => true]);
            PaymentMethod::create(['name' => 'Debit Card', 'description' => 'Kartu Debit', 'is_active' => true]);
            PaymentMethod::create(['name' => 'QRIS', 'description' => 'Pembayaran QR Code', 'is_active' => true]);
            PaymentMethod::create(['name' => 'Bank Transfer', 'description' => 'Transfer Bank', 'is_active' => true]);
        }


        // Membuat 10 Pemasok
        Supplier::factory()->count(10)->create();

        // Anda bisa menambahkan seeder untuk Order, Purchase, Shift, Expense nanti jika diperlukan
        // data transaksi yang lebih kompleks, mungkin dengan relasi langsung.
        // Contoh: Order::factory()->count(10)->create();
    }
}