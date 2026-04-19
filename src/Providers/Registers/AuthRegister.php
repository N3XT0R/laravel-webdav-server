<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;
use N3XT0R\LaravelWebdavServer\Auth\Authorization\GatePathAuthorization;
use N3XT0R\LaravelWebdavServer\Auth\Validators\DatabaseCredentialValidator;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;

final readonly class AuthRegister
{
    public function __construct(
        private Container $app,
    ) {}

    public function register(): void
    {
        $this->app->bindIf(
            CredentialValidatorInterface::class,
            DatabaseCredentialValidator::class,
        );

        $this->app->bindIf(
            PathAuthorizationInterface::class,
            GatePathAuthorization::class,
        );
    }
}

