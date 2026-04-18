<?php

namespace Database\Seeders;

use Database\Factories\UserFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccount;

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
        $webdavAccountFactory = WebDavAccount::factory()
            ->withUserName('testuser');
        if (app()->runningInConsole()) {
            $factory->withPassword($password);
            $webdavAccountFactory->withPassword($password);
        }

        $user = $factory->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $webdavAccountFactory
            ->withUserId($user->getKey())
            ->create([
                'display_name' => 'Test User',
            ]);
    }
}
