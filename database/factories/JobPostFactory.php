<?php

namespace Database\Factories;

use App\Models\JobPost;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobPost>
 */
class JobPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = JobPost::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(6, true),
            'description' => $this->faker->paragraph(10),
            'size' => $this->faker->randomElement(['small', 'medium', 'large']),
            'experience_level' => $this->faker->randomElement(['beginner', 'intermediate', 'expert']),
            'expected_delivery_time' => $this->faker->numberBetween(1, 30),
            'budjet' => $this->faker->numberBetween(500, 5000),
            'illustrative_files' => $this->faker->imageUrl(),
        ];
    }
}
