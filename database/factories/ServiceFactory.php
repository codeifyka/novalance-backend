<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Price;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    private static array $samples = [
        ['title' => 'Full-Stack Laravel & Vue.js Web Application', 'description' => 'I will build a fast, secure, and scalable web application using Laravel and Vue.js with clean REST APIs and responsive Tailwind UI.'],
        ['title' => 'Modern SaaS UI/UX Design in Figma', 'description' => 'Complete user-centered interface design in Figma including wireframes, high-fidelity screens, and a reusable component library.'],
        ['title' => 'Cross-Platform Flutter Mobile App', 'description' => 'Native-quality iOS and Android app built with Flutter, featuring smooth animations, state management, and API integration.'],
        ['title' => 'REST API & Stripe Payment Integration', 'description' => 'Secure backend API development with authentication, webhook handling, and Stripe payment/escrow integration.'],
        ['title' => 'Brand Identity & Minimalist Logo Package', 'description' => 'Custom logo design, color palette, typography guidelines, and social media brand kit tailored to your business.'],
        ['title' => 'React & Next.js Landing Page Development', 'description' => 'Pixel-perfect, SEO-friendly landing page built with Next.js and Tailwind CSS optimized for speed and conversions.'],
        ['title' => 'Technical SEO Audit & Content Strategy', 'description' => 'Comprehensive site audit, keyword research, and actionable on-page SEO roadmap to boost organic search traffic.'],
        ['title' => 'Python Data Pipeline & Analytics Dashboard', 'description' => 'Automated ETL scripts and interactive data visualizations to turn raw business data into clear insights.'],
    ];

    public function definition(): array
    {
        $sample = $this->faker->randomElement(self::$samples);

        return [
            'title' => $sample['title'],
            'description' => $sample['description'],
            'category_id' => Category::inRandomOrder()->value('id') ?? Category::factory(),
            'price_id' => Price::factory(),
            'delivery_days' => $this->faker->randomElement([3, 5, 7, 10, 14]),
            'rate' => 0,
        ];
    }
}
