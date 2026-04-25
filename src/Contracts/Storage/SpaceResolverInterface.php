<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Storage;

use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidSpaceConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Storage\SpaceNotConfiguredException;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

interface SpaceResolverInterface
{
    /**
     * Resolve the concrete storage space for a principal and logical space key.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal used for user-scoped path resolution.
     * @param  string  $spaceKey  Logical space key resolved from the request URL.
     * @return WebDavStorageSpaceValueObject Concrete disk and root path for the request.
     *
     * @throws SpaceNotConfiguredException When the requested space key does not exist.
     * @throws InvalidSpaceConfigurationException When the configured space is incomplete or invalid.
     */
    public function resolve(WebDavPrincipalValueObject $principal, string $spaceKey): WebDavStorageSpaceValueObject;
}
