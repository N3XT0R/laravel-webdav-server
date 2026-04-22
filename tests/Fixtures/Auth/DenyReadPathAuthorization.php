<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\Forbidden;

final class DenyReadPathAuthorization extends AllowAllPathAuthorization
{
    public function authorizeRead(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        parent::authorizeRead($principal, $disk, $path);

        throw new Forbidden('Access denied.');
    }
}
