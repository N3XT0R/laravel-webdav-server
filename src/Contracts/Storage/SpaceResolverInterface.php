<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Storage;

use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

interface SpaceResolverInterface
{
    public function resolve(WebDavPrincipalValueObject $principal, string $spaceKey): WebDavStorageSpaceValueObject;
}
