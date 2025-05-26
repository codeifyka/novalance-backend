<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JobPost;
use App\Models\User;

class JobPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = User::where('account_type', 'client')->get();

        foreach ($clients as $client) {
            JobPost::factory()->count(5)->create([
                'user_id' => $client->id,
            ]);
        }
    }
}
