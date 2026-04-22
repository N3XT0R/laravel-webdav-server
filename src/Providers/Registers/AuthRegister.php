<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use N3XT0R\LaravelWebdavServer\Auth\Authorization\GatePathAuthorization;
use N3XT0R\LaravelWebdavServer\Auth\Validators\DatabaseCredentialValidator;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;

final class AuthRegister extends AbstractRegister
{
    protected function bindings(): array
    {
        return [
            CredentialValidatorInterface::class => DatabaseCredentialValidator::class,
            PathAuthorizationInterface::class => GatePathAuthorization::class,
        ];
    }
}
