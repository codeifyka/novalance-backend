<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use App\Models\Category;
use App\Models\Price;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'description' => fake()->text(255),
            'category_id' => Category::all()->random()->id,
            'price_id' => Price::factory(),
            'rate' => random_int(1,5),
        ];
    }
}
