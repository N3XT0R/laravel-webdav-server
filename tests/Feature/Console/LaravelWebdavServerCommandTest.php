<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Feature\Console;

use N3XT0R\LaravelWebdavServer\Tests\TestCase;

final class LaravelWebdavServerCommandTest extends TestCase
{
    public function test_command_outputs_success_message_and_returns_success_exit_code(): void
    {
        $this->artisan('laravel-webdav-server')
            ->expectsOutput('All done')
            ->assertExitCode(0);
    }
}
