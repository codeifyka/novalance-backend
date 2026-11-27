<?php

namespace Database\Seeders;

use App\Models\JobPost;
use App\Models\User;
use Illuminate\Database\Seeder;

class JobPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = User::where('account_type', 'client')->get();

        foreach ($clients as $client) {
            JobPost::factory()->count(2)->create([
                'user_id' => $client->id,
            ]);
        }
    }
}
