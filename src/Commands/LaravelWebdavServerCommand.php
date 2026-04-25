<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;

final class LaravelWebdavServerCommand extends Command
{
    public $signature = 'laravel-webdav-server';

    public $description = 'My command';

    /**
     * Executes the package test command and reports the result in the console.
     *
     * @return int Symfony-compatible command exit code.
     */
    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
