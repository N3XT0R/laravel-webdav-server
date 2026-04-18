<?php

namespace Database\Seeders;

use Database\Factories\UserFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = 'password';

        $factory = UserFactory::new();
        if (app()->runningInConsole()) {
            $factory->withPassword('password');
        }

        $user = $factory->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
