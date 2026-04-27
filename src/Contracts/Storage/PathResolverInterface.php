<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Storage;

use N3XT0R\LaravelWebdavServer\Contracts\Auth\WebDavPrincipalInterface;
use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidSpaceConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Storage\SpaceNotConfiguredException;

interface PathResolverInterface
{
    /**
     * Resolve the user-scoped filesystem root path for the given principal and space key.
     *
     * @param  WebDavPrincipalInterface  $principal  Principal whose ID is appended to the resolved path.
     *                                               Accepts any `AccountInterface` or `WebDavPrincipalValueObject`.
     * @param  string  $spaceKey  Logical storage space key.
     * @return string User-scoped filesystem root path, e.g. `webdav/42`.
     *
     * @throws SpaceNotConfiguredException When the requested space key is missing from configuration.
     * @throws InvalidSpaceConfigurationException When the configured space is incomplete or invalid.
     */
    public function resolvePath(WebDavPrincipalInterface $principal, string $spaceKey): string;

    /**
     * Resolve the public WebDAV mount URL for the given space key.
     *
     * @param  string  $spaceKey  Logical storage space key.
     * @return string WebDAV mount URL, e.g. `https://app.test/webdav/default`.
     */
    public function resolveUrl(string $spaceKey): string;
}
