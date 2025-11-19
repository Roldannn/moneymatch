<?php

namespace Database\Factories;

use App\Models\CurrencyEquivalence;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyEquivalenceFactory extends Factory
{
    protected $model = CurrencyEquivalence::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'currency_id' => Currency::factory(),
            'year' => $this->faker->numberBetween(2004, 2025),
            'month' => $this->faker->numberBetween(1, 12),
            'equivalence' => $this->faker->randomFloat(6, 0.0001, 10000),
        ];
    }
}

