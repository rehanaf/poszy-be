<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $methods = [
            'Tunai' => 'Pembayaran langsung menggunakan uang tunai',
            'Kartu Kredit' => 'Pembayaran menggunakan kartu kredit',
            'Kartu Debit' => 'Pembayaran menggunakan kartu debit',
            'QRIS' => 'Pembayaran melalui scan kode QR',
            'Transfer Bank' => 'Pembayaran melalui transfer bank',
        ];

        $name = $this->faker->unique()->randomElement(array_keys($methods));

        return [
            'name' => $name,
            'description' => $methods[$name],
            'is_active' => true,
        ];
    }
}