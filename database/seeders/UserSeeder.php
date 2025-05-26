<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            "email" => "abdelfetah@gmail.com",
            "password" => "Abdelfat",
            "account_type" => "freelancer"
        ]);
        User::factory()->count(20)->create();
    }
}
