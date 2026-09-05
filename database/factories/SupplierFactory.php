<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Factory as FakerFactory;

class SupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = FakerFactory::create('id_ID');

        return [
            'name' => $faker->unique()->company(),
            'contact_person' => $faker->name(),
            'phone' => $faker->unique()->phoneNumber(),
            'email' => $faker->unique()->safeEmail(),
            'address' => $faker->address(),
        ];
    }
}