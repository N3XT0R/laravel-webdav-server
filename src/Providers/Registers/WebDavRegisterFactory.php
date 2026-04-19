<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;

final readonly class WebDavRegisterFactory
{
    public function __construct(
        private Container $app,
    ) {
    }

    public function registerAll(): void
    {
        new RepositoryRegister($this->app)->register();
        new AuthRegister($this->app)->register();
        new StorageRegister($this->app)->register();
        new ServerRegister($this->app)->register();
    }
}

