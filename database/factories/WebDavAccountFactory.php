<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccount;

class WebDavAccountFactory extends Factory
{
    protected $model = WebDavAccount::class;

    public function definition(): array
    {
        return [
            'username' => $this->faker->userName(),
            'password' => bcrypt('password'),
            'enabled' => true,
            'user_id' => null,
            'display_name' => $this->faker->name(),
            'meta' => null,
        ];
    }

    public function withPassword(string $password): self
    {
        return $this->state(fn(array $attributes) => [
            'password' => bcrypt($password),
        ]);
    }

    public function withUserId(?int $userId): self
    {
        return $this->state(fn(array $attributes) => [
            'user_id' => $userId,
        ]);
    }

}