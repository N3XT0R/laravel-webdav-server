<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;

final readonly class WebDavRegisterFactory
{
    /**
     * @param  Container  $app  Laravel service container used to instantiate the individual register classes.
     */
    public function __construct(
        private Container $app,
    ) {}

    /**
     * Instantiates every package register class and applies its bindings to the container.
     */
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
            LoggingRegister::class,
            StorageRegister::class,
            ServerRegister::class,
            ServerFactoryRegister::class,
        ];
    }
}
