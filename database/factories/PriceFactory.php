<?php

namespace Database\Factories;

use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Price>
 */
class PriceFactory extends Factory
{
    protected $model = Price::class;

    public function definition(): array
    {
        return [
            'value' => $this->faker->randomElement([50, 75, 100, 150, 200, 250, 350, 500, 750, 1000, 1200]),
            'currency_code' => 'USD',
            'currency_symbol' => '$',
        ];
    }
}
