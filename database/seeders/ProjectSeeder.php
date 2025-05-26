<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $freelancers = User::where('account_type', 'freelancer')->get();

        foreach ($freelancers as $freelancer) {
            Project::factory()->count(5)->create([
                'user_id' => $freelancer->id,
            ]);
        }
    }
}
