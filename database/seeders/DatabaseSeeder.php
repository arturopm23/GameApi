<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        Role::create(['name' => 'player']);
        Role::create(['name' => 'admin']);

        // Create one admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin'); // Assign the admin role

        // Create multiple regular users
        User::factory()->count(5)->create()->each(function ($user) {
            $user->assignRole('player'); // Assign the player role
        });

        // Get all users to create games
        $users = User::all();

        // Create multiple games for each user
        foreach ($users as $user) {
            Game::factory()->count(3)->create([
                'user_id' => $user->id,
                'dice1' => $dice1 = rand(1, 6),
                'dice2' => $dice2 = rand(1, 6),
                'win' => ($dice1 + $dice2 == 7),
            ]);
        }
    }
}
