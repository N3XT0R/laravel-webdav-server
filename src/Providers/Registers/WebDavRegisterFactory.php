<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;

final readonly class WebDavRegisterFactory
{
    public function __construct(
        private Container $app,
    ) {}

    public function registerAll(): void
    {
        foreach ($this->registerClasses() as $registerClass) {
            new $registerClass($this->app)->register();
        }
    }

    /**
     * @return list<class-string<AbstractRegister>>
     */
    private function registerClasses(): array
    {
        return [
            RepositoryRegister::class,
            AuthRegister::class,
            StorageRegister::class,
            ServerRegister::class,
            ServerFactoryRegister::class,
        ];
    }
}
