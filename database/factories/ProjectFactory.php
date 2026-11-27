<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    private static array $samples = [
        ['title' => 'E-Commerce Checkout & Escrow Platform', 'description' => 'Designed and engineered a multi-vendor checkout experience with real-time order tracking and automated wallet payouts.'],
        ['title' => 'Real-Time SaaS Analytics Dashboard', 'description' => 'Built an interactive analytics portal with custom charts, role-based access control, and CSV/PDF reporting.'],
        ['title' => 'Fintech Mobile Wallet App', 'description' => 'Cross-platform mobile wallet featuring instant transfers, biometric login, and transaction history filtering.'],
        ['title' => 'Healthcare Appointment Booking Portal', 'description' => 'Patient and doctor scheduling web app with automated reminders, calendar sync, and secure messaging.'],
        ['title' => 'Brand Design System & Component Kit', 'description' => 'Created a unified Figma and Tailwind CSS design system with 60+ accessible UI components.'],
        ['title' => 'AI Support Chatbot & Knowledge Base', 'description' => 'Integrated semantic search and automated ticket routing to reduce customer support response times by 45%.'],
    ];

    public function definition(): array
    {
        $sample = $this->faker->randomElement(self::$samples);

        return [
            'title' => $sample['title'],
            'description' => $sample['description'],
        ];
    }
}
