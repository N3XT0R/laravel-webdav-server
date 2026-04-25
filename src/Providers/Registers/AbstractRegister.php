<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;

abstract readonly class AbstractRegister
{
    /**
     * @param  Container  $app  Laravel service container used to register package bindings.
     */
    public function __construct(
        protected readonly Container $app,
    ) {}

    /**
     * Returns the container bindings handled by this register.
     *
     * @return array<class-string, class-string>
     */
    abstract protected function bindings(): array;

    /**
     * Registers all configured bindings via `bindIf()` so consuming apps can override them.
     */
    public function register(): void
    {
        foreach ($this->bindings() as $abstract => $concrete) {
            $this->app->bindIf($abstract, $concrete);
        }
    }
}
