<?php

namespace Database\Factories;

use App\Models\JobPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobPost>
 */
class JobPostFactory extends Factory
{
    protected $model = JobPost::class;

    private static array $samples = [
        ['title' => 'Build a Custom CRM Dashboard in Vue 3 and Laravel', 'description' => 'We need an experienced full-stack developer to build a clean CRM dashboard with client pipelines, role permissions, and reporting exports.'],
        ['title' => 'Redesign Mobile App UI/UX for Food Delivery Startup', 'description' => 'Looking for a UI/UX designer to refresh our iOS and Android ordering flow in Figma, including a clickable prototype and design tokens.'],
        ['title' => 'Integrate Stripe Payments & Webhooks into SaaS Backend', 'description' => 'Seeking a backend engineer to implement Stripe Checkout, subscription webhooks, and idempotent invoice handling in Laravel.'],
        ['title' => 'Develop High-Converting Landing Page in Tailwind CSS', 'description' => 'Need a responsive, fast-loading marketing page with smooth animations, pricing comparison table, and contact form integration.'],
        ['title' => 'Build Flutter Companion App for Smart Home Devices', 'description' => 'Looking for a mobile developer to create a cross-platform Flutter app that connects to our REST and WebSocket APIs.'],
        ['title' => 'Technical SEO Optimization & Product Copywriting', 'description' => 'We want to improve organic rankings for our B2B SaaS product through technical fixes, schema markup, and 5 pillar articles.'],
    ];

    public function definition(): array
    {
        $sample = $this->faker->randomElement(self::$samples);

        return [
            'title' => $sample['title'],
            'description' => $sample['description'],
            'size' => $this->faker->randomElement(['small', 'medium', 'large']),
            'experience_level' => $this->faker->randomElement(['beginner', 'intermediate', 'expert']),
            'expected_delivery_time' => $this->faker->randomElement([5, 7, 10, 14, 21, 30]),
            'budjet' => $this->faker->randomElement([300, 500, 800, 1200, 1500, 2500, 3500]),
            'illustrative_files' => '',
        ];
    }
}
