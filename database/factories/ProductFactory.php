<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category; // Import Category
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
        $hasCategory = $this->faker->boolean(70); // 70% chance to have a category
        $category = null;
        if ($hasCategory) {
            $category = Category::inRandomOrder()->first(); // Get a random existing category
            if (!$category) {
                // Fallback: create a category if none exist
                $category = Category::factory()->create();
            }
        }

        $hasStock = $this->faker->boolean(80); // 80% chance to have finite stock
        $price = $this->faker->randomFloat(2, 5000, 100000);

        return [
            'category_id' => $category ? $category->id : null,
            'name' => $this->faker->unique()->words(2, true) . ' ' . $this->faker->word(),
            'sku' => $this->faker->unique()->bothify('???-###'),
            'description' => $this->faker->paragraph(),
            'price' => $price,
            'stock' => $hasStock ? $this->faker->numberBetween(0, 200) : null, // Null for unlimited stock
            'unit' => $this->faker->randomElement(['pcs', 'kg', 'liter', 'pack']),
            'image_url' => $this->faker->imageUrl(640, 480, 'food', true, 'Faker'), // Dummy image URL
            'is_active' => $this->faker->boolean(90),
            'discount' => $this->faker->randomElement([0, 5, 10, 15]), // Random discount
        ];
    }
}