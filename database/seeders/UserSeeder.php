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
        // Ensure strictly ONE admin user exists
        if (!User::where('account_type', 'admin')->exists()) {
            User::factory()->create([
                'username' => 'admin',
                'first_name' => 'Platform',
                'last_name' => 'Admin',
                'email' => 'admin@novalance.com',
                'password' => 'password',
                'account_type' => 'admin',
            ]);
        }

        // Primary demo freelancer
        User::firstOrCreate(
            ['email' => 'abdelfetah@gmail.com'],
            User::factory()->raw([
                'username' => 'abdelfetah',
                'first_name' => 'Abdelfetah',
                'last_name' => 'Dev',
                'email' => 'abdelfetah@gmail.com',
                'password' => 'password',
                'account_type' => 'freelancer',
            ])
        );

        // Primary demo client
        User::firstOrCreate(
            ['email' => 'client@novalance.com'],
            User::factory()->raw([
                'username' => 'novaclient',
                'first_name' => 'Sara',
                'last_name' => 'Client',
                'email' => 'client@novalance.com',
                'password' => 'password',
                'account_type' => 'client',
            ])
        );

        // Additional freelancers & clients (no extra admins)
        User::factory()->count(5)->create(['account_type' => 'freelancer']);
        User::factory()->count(5)->create(['account_type' => 'client']);
    }
}
