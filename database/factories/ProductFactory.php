<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productNames = [
            'Beras Premium',
            'Minyak Goreng',
            'Gula Pasir',
            'Telur Ayam',
            'Tepung Terigu',
            'Kecap Manis',
            'Mie Instan Goreng',
            'Roti Tawar',
            'Keripik Singkong',
            'Kerupuk Udang',
            'Cokelat Batang',
            'Air Mineral',
            'Teh Botol',
            'Kopi Sachet',
            'Susu UHT',
            'Sabun Mandi',
            'Sampo',
            'Pasta Gigi',
            'Sabun Cuci Piring',
            'Detergen Bubuk',
            'Pembersih Lantai',
            'Buku Tulis',
            'Pulpen',
            'Pensil',
            'Kertas HVS',
            'Popok Bayi',
            'Susu Formula',
            'Setrika Listrik',
            'Kipas Angin',
            'Snack Ringan',
        ];

        $hasCategory = $this->faker->boolean(70);
        $category = null;
        if ($hasCategory) {
            $category = Category::inRandomOrder()->first();
            if (!$category) {
                $category = Category::factory()->create();
            }
        }

        $hasStock = $this->faker->boolean(80);
        $price = $this->faker->numberBetween(2000, 100000);

        return [
            'category_id' => $category ? $category->id : null,
            'name' => $this->faker->unique()->randomElement($productNames),
            'sku' => $this->faker->unique()->bothify('SKU-####'),
            'description' => 'Deskripsi singkat untuk ' . $this->faker->randomElement($productNames),
            'price' => $price,
            'stock' => $hasStock ? $this->faker->numberBetween(0, 200) : null,
            'unit' => $this->faker->randomElement(['pcs', 'kg', 'liter', 'bungkus', 'botol']),
            'image_url' => 'https://picsum.photos/seed/' . $this->faker->unique()->numberBetween(1, 9999) . '/640/480',
            'is_active' => $this->faker->boolean(90),
            'discount' => $this->faker->randomElement([0, 0, 0, 5, 10, 15]),
        ];
    }
}