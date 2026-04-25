<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Commands;

use Illuminate\Console\Command;

final class LaravelWebdavServerCommand extends Command
{
    protected $signature = 'laravel-webdav-server';

    protected $description = 'Show the available Laravel WebDAV Server artisan commands.';

    /**
     * Show the package-specific artisan entry points for WebDAV account management.
     *
     * @return int Symfony-compatible command exit code.
     */
    public function handle(): int
    {
        $this->components->info('Laravel WebDAV Server artisan commands');
        $this->newLine();
        $this->line('  php artisan laravel-webdav-server:account:create {username} {password}');
        $this->line('  php artisan laravel-webdav-server:account:list');
        $this->line('  php artisan laravel-webdav-server:account:show {username}');
        $this->line('  php artisan laravel-webdav-server:account:update {username} [--new-username=...] [--password=...]');
        $this->newLine();
        $this->line('Documentation: https://laravel-webdav-server.readthedocs.io/en/latest/');

        return self::SUCCESS;
    }
}
