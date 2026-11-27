<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Web Development',
            'Mobile App Development',
            'UI/UX Design',
            'Graphic Design',
            'Content Writing',
            'Digital Marketing',
            'Video & Animation',
            'Data & AI',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
