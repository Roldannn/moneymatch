<?php

namespace Database\Factories;

use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'country' => $this->faker->country(),
            'currency' => $this->faker->currencyCode(),
            'equivalence' => $this->faker->randomFloat(4, 0.0001, 100) ?: 1.0,
        ];
    }
}

