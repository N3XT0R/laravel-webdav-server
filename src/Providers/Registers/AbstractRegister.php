<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;

abstract readonly class AbstractRegister
{
    public function __construct(
        protected readonly Container $app,
    ) {}

    /**
     * @return array<class-string, class-string>
     */
    abstract protected function bindings(): array;

    public function register(): void
    {
        foreach ($this->bindings() as $abstract => $concrete) {
            $this->app->bindIf($abstract, $concrete);
        }
    }
}
