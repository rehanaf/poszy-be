<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Makanan',
            'Minuman',
            'Snack',
            'Sembako',
            'Bumbu Dapur',
            'Perawatan Diri',
            'Kebersihan',
            'Alat Tulis',
            'Kebutuhan Bayi',
            'Elektronik',
        ];
        $name = $this->faker->unique()->randomElement($categories);

        return [
            'name' => $name,
            'description' => 'Kategori ' . $name . ' untuk kebutuhan toko',
        ];
    }
}