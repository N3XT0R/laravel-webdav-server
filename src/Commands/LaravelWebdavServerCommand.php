<?php

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;

class LaravelWebdavServerCommand extends Command
{
    public $signature = 'laravel-webdav-server';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
