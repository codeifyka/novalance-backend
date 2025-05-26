<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ServiceTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $freelancers = User::where('account_type', 'freelancer')->get();
        foreach ($freelancers as $freelancer) {
            Service::factory()->count(5)->create([
                'user_id' => $freelancer->id,
            ]);
        }
    }
}
