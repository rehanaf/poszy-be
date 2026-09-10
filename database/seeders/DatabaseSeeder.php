<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = '*_H@sbunallah123321!';

        // ===================== USERS =====================
        User::create([
            'name' => 'Andi Prasetyo',
            'email' => 'admin@poszy.test',
            'password' => Hash::make($password),
            'role' => 'owner',
            'profile_image_url' => 'https://i.pravatar.cc/150?img=12',
        ]);

        User::create([
            'name' => 'Rina Fitriani',
            'email' => 'cashier@poszy.test',
            'password' => Hash::make($password),
            'role' => 'kasir',
            'profile_image_url' => 'https://i.pravatar.cc/150?img=47',
        ]);

        User::create([
            'name' => 'Deni Saputra',
            'email' => 'user@poszy.test',
            'password' => Hash::make($password),
            'role' => 'manager',
            'profile_image_url' => 'https://i.pravatar.cc/150?img=33',
        ]);

        // ===================== CATEGORIES =====================
        $categories = [
            ['name' => 'Makanan', 'description' => 'Makanan pokok, lauk pauk, dan makanan jadi sehari-hari'],
            ['name' => 'Minuman', 'description' => 'Aneka minuman segar, kemasan, dan bubuk'],
            ['name' => 'Snack', 'description' => 'Camilan ringan, keripik, dan makanan ringan lainnya'],
            ['name' => 'Sembako', 'description' => 'Sembilan bahan pokok untuk kebutuhan dapur'],
            ['name' => 'Bumbu Dapur', 'description' => 'Bumbu masak, kecap, saos, dan rempah-rempah'],
            ['name' => 'Perawatan Diri', 'description' => 'Sabun, sampo, pasta gigi, dan produk perawatan pribadi'],
            ['name' => 'Kebersihan', 'description' => 'Produk pembersih rumah tangga dan laundry'],
            ['name' => 'Alat Tulis', 'description' => 'Perlengkapan sekolah dan kantor'],
            ['name' => 'Kebutuhan Bayi', 'description' => 'Popok, susu formula, dan perlengkapan bayi'],
            ['name' => 'Elektronik', 'description' => 'Peralatan listrik dan elektronik rumah tangga'],
        ];

        foreach ($categories as $data) {
            Category::create($data);
        }

        $cat = function ($name) {
            return Category::where('name', $name)->first()->id;
        };

        // ===================== PRODUCTS =====================
        $products = [
            ['Beras Premium 5 Kg', 'Sembako', 65000, 120, 'kantong', 'Beras premium kualitas super dengan butiran pulen, cocok untuk konsumsi harian.'],
            ['Minyak Goreng 1 L', 'Sembako', 18000, 200, 'botol', 'Minyak goreng kemasan botol 1 liter, jernih dan tahan panas.'],
            ['Gula Pasir 1 Kg', 'Sembako', 17000, 150, 'kg', 'Gula pasir putih bersih kemasan 1 kg, manis alami.'],
            ['Telur Ayam 1 Kg', 'Sembako', 28000, 80, 'kg', 'Telur ayam negeri segar, kualitas pilihan.'],
            ['Tepung Terigu 1 Kg', 'Sembako', 12000, 90, 'kg', 'Tepung terigu protein sedang, cocok untuk gorengan dan kue.'],
            ['Kecap Manis Botol', 'Bumbu Dapur', 15000, 100, 'botol', 'Kecap manis dengan rasa gurih dan aroma khas nusantara.'],
            ['Sambal Terasi Botol', 'Bumbu Dapur', 20000, 70, 'botol', 'Sambal terasi siap saji, pedas gurih untuk teman makan.'],
            ['Saos Sambal Botol', 'Bumbu Dapur', 12000, 75, 'botol', 'Saos sambal cocok untuk cocolan gorengan dan makanan.'],
            ['Mie Instan Goreng', 'Makanan', 3500, 500, 'bungkus', 'Mie instan goreng dengan bumbu lengkap, praktis dan lezat.'],
            ['Mie Instan Ayam Bawang', 'Makanan', 3000, 500, 'bungkus', 'Mie instan kuah rasa ayam bawang yang gurih.'],
            ['Roti Tawar', 'Makanan', 18000, 40, 'bungkus', 'Roti tawar lembut, cocok untuk sarapan atau bekal.'],
            ['Nasi Uduk Kemasan', 'Makanan', 12500, 35, 'bungkus', 'Nasi uduk lengkap dengan lauk, siap santap.'],
            ['Keripik Singkong', 'Snack', 15000, 60, 'bungkus', 'Keripik singkong balado renyah dan pedas menggoda.'],
            ['Kerupuk Udang', 'Snack', 12000, 60, 'bungkus', 'Kerupuk udang gurih khas, renyah saat digoreng.'],
            ['Cokelat Batang', 'Snack', 25000, 55, 'batang', 'Cokelat batang premium dengan rasa manis dan legit.'],
            ['Permen Jeli', 'Snack', 5000, 80, 'bungkus', 'Permen jeli aneka rasa dengan tekstur kenyal.'],
            ['Air Mineral 600 ml', 'Minuman', 4000, 300, 'botol', 'Air mineral murni kemasan botol 600 ml.'],
            ['Teh Botol Kotak', 'Minuman', 5000, 200, 'kotak', 'Teh manis kemasan kotak, segar dan siap minum.'],
            ['Kopi Sachet', 'Minuman', 2000, 250, 'sachet', 'Kopi bubuk sachet dengan rasa robusta yang pekat.'],
            ['Susu UHT 1 L', 'Minuman', 12000, 60, 'kotak', 'Susu UHT full cream, kaya kalsium dan protein.'],
            ['Sabun Mandi Batang', 'Perawatan Diri', 25000, 45, 'batang', 'Sabun mandi dengan aroma segar dan busa lembut.'],
            ['Sampo Botol 170 ml', 'Perawatan Diri', 45000, 40, 'botol', 'Sampo dengan perawatan rambut berkilau dan anti ketombe.'],
            ['Pasta Gigi 190 g', 'Perawatan Diri', 20000, 50, 'tube', 'Pasta gigi dengan perlindungan ganda untuk gigi dan gusi.'],
            ['Sabun Cuci Piring 750 ml', 'Kebersihan', 15000, 65, 'botol', 'Sabun cuci piring ampuh menghilangkan lemak.'],
            ['Detergen Bubuk 1 Kg', 'Kebersihan', 28000, 70, 'kg', 'Detergen bubuk dengan wangi tahan lama.'],
            ['Pembersih Lantai 800 ml', 'Kebersihan', 20000, 60, 'botol', 'Pembersih lantai dengan wangi segar dan anti bakteri.'],
            ['Buku Tulis 38 Lembar', 'Alat Tulis', 5000, 150, 'pcs', 'Buku tulis isi 38 lembar dengan kertas tebal.'],
            ['Pulpen Standard', 'Alat Tulis', 3000, 200, 'pcs', 'Pulpen tinta hitam dengan alur halus.'],
            ['Pensil 2B', 'Alat Tulis', 2500, 200, 'pcs', 'Pensil 2B berkualitas, mudah diraut.'],
            ['Kertas HVS A4 70 g', 'Alat Tulis', 55000, 30, 'rim', 'Kertas HVS A4 70 gram satu rim, untuk cetak dan fotokopi.'],
            ['Lak Ban / Selotip', 'Alat Tulis', 7000, 90, 'pcs', 'Selotip bening untuk merekatkan dokumen dan kado.'],
            ['Popok Bayi Ukuran M', 'Kebutuhan Bayi', 65000, 30, 'pack', 'Popok bayi ukuran M, lembut dan anti bocor.'],
            ['Susu Formula Bayi 400 g', 'Kebutuhan Bayi', 85000, 25, 'kaleng', 'Susu formula untuk bayi dengan nutrisi lengkap.'],
            ['Setrika Listrik', 'Elektronik', 175000, 12, 'unit', 'Setrika listrik dengan permukaan anti lengket.'],
            ['Kipas Angin Portable', 'Elektronik', 250000, 10, 'unit', 'Kipas angin portable, hemat listrik dan mudah dipindah.'],
        ];

        foreach ($products as $i => [$name, $categoryName, $price, $stock, $unit, $description]) {
            Product::create([
                'category_id' => $cat($categoryName),
                'name' => $name,
                'sku' => 'PRD-' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'unit' => $unit,
                'image_url' => 'https://picsum.photos/seed/' . ($i + 1) . '/640/480',
                'is_active' => true,
                'discount' => 0,
            ]);
        }

        // ===================== CUSTOMERS =====================
        $customers = [
            ['Budi Santoso', '081234567801', 'Jl. Merdeka No. 12, Jakarta Pusat'],
            ['Siti Rahayu', '081234567802', 'Jl. Melati No. 3, Bandung'],
            ['Andi Wijaya', '081234567803', 'Jl. Kenanga No. 8, Surabaya'],
            ['Dewi Lestari', '081234567804', 'Jl. Anggrek No. 21, Semarang'],
            ['Rudi Hartono', '081234567805', 'Jl. Mawar No. 5, Yogyakarta'],
            ['Intan Permatasari', '081234567806', 'Jl. Flamboyan No. 14, Denpasar'],
            ['Hendra Gunawan', '081234567807', 'Jl. Cempaka No. 9, Medan'],
            ['Maya Anggraini', '081234567808', 'Jl. Dahlia No. 7, Makassar'],
            ['Agus Salim', '081234567809', 'Jl. Kamboja No. 2, Palembang'],
            ['Ratna Sari', '081234567810', 'Jl. Melur No. 16, Malang'],
            ['Fajar Nugroho', '081234567811', 'Jl. Sakura No. 4, Bekasi'],
            ['Wulan Sari', '081234567812', 'Jl. Bougenville No. 11, Depok'],
            ['Bambang Setiawan', '081234567813', 'Jl. Teratai No. 6, Bogor'],
            ['Trisna Dewi', '081234567814', 'Jl. Seruni No. 18, Tangerang'],
            ['Januar Pratama', '081234567815', 'Jl. Lavender No. 10, Solo'],
        ];

        foreach ($customers as $i => [$name, $phone, $address]) {
            $email = strtolower(str_replace(' ', '.', $name)) . '@gmail.com';
            Customer::create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'profile_image_url' => 'https://i.pravatar.cc/150?img=' . (20 + $i),
            ]);
        }

        // ===================== PAYMENT METHODS =====================
        $paymentMethods = [
            ['name' => 'Tunai', 'description' => 'Pembayaran langsung menggunakan uang tunai'],
            ['name' => 'Kartu Kredit', 'description' => 'Pembayaran menggunakan kartu kredit'],
            ['name' => 'Kartu Debit', 'description' => 'Pembayaran menggunakan kartu debit'],
            ['name' => 'QRIS', 'description' => 'Pembayaran melalui scan kode QR'],
            ['name' => 'Transfer Bank', 'description' => 'Pembayaran melalui transfer bank'],
        ];

        foreach ($paymentMethods as $data) {
            PaymentMethod::create($data + ['is_active' => true]);
        }

        // ===================== SUPPLIERS =====================
        $suppliers = [
            ['PT Sumber Makmur Tani', 'Budi Hartanto', '081112223331', 'Jl. Raya Cikarang No. 45, Bekasi'],
            ['CV Berkah Abadi', 'Sri Wahyuni', '081112223332', 'Jl. Gatot Subroto No. 12, Jakarta'],
            ['Toko Grosir Jaya Pangan', 'Ahmad Fauzi', '081112223333', 'Jl. Asia Afrika No. 8, Bandung'],
            ['PT Segar Sejahtera', 'Dwi Lestari', '081112223334', 'Jl. Pandanaran No. 22, Semarang'],
            ['UD Karya Mandiri', 'Rudi Hartono', '081112223335', 'Jl. Malioboro No. 5, Yogyakarta'],
            ['PT Cipta Rasa Nusantara', 'Eko Prasetyo', '081112223336', 'Jl. Raya Darmo No. 33, Surabaya'],
            ['CV Berkah Bersih', 'Nur Aisyah', '081112223337', 'Jl. Sudirman No. 17, Makassar'],
            ['PT Terang Teknologi', 'Joko Susilo', '081112223338', 'Jl. Pemuda No. 9, Medan'],
        ];

        foreach ($suppliers as $i => [$name, $contactPerson, $phone, $address]) {
            $email = strtolower(str_replace([' ', '.'], '', $name)) . '@supplier.id';
            Supplier::create([
                'name' => $name,
                'contact_person' => $contactPerson,
                'phone' => $phone,
                'email' => $email,
                'address' => $address,
            ]);
        }
    }
}