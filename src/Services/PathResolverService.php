<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Services;

use Illuminate\Contracts\Config\Repository;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\PathResolverInterface;
use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidSpaceConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Storage\SpaceNotConfiguredException;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class PathResolverService implements PathResolverInterface
{
    /**
     * @param  Repository  $config  Package configuration repository used to read storage space definitions.
     */
    public function __construct(
        private Repository $config,
    ) {}

    /**
     * Resolve the user-scoped filesystem root path for the given principal and space key.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal whose ID is appended to the resolved path.
     * @param  string  $spaceKey  Logical storage space key.
     * @return string User-scoped filesystem root path, e.g. `webdav/42`.
     *
     * @throws SpaceNotConfiguredException When the requested space key is missing from configuration.
     * @throws InvalidSpaceConfigurationException When the configured space is incomplete or invalid.
     */
    public function resolvePath(WebDavPrincipalValueObject $principal, string $spaceKey): string
    {
        $spaceConfig = $this->readSpaceConfig($spaceKey);

        $root = $spaceConfig['root'] ?? null;
        $prefix = $spaceConfig['prefix'] ?? null;

        if (! is_string($root) || trim($root) === '') {
            throw new InvalidSpaceConfigurationException(sprintf(
                'WebDAV storage space "%s" is missing a valid "root" configuration.',
                $spaceKey,
            ));
        }

        $parts = [trim($root, '/')];

        if (is_string($prefix) && trim($prefix) !== '' && trim($prefix) !== '/') {
            $parts[] = trim($prefix, '/');
        }

        $parts[] = (string) $principal->id;

        return implode('/', $parts);
    }

    /**
     * Resolve the public WebDAV mount URL for the given space key.
     *
     * @param  string  $spaceKey  Logical storage space key.
     * @return string WebDAV mount URL, e.g. `https://app.test/webdav/default`.
     */
    public function resolveUrl(string $spaceKey): string
    {
        $base = rtrim((string) $this->config->get('app.url', ''), '/');
        $prefix = trim((string) $this->config->get('webdav-server.route_prefix', 'webdav'), '/');

        return $base.'/'.$prefix.'/'.trim($spaceKey, '/');
    }

    /**
     * @throws SpaceNotConfiguredException
     * @throws InvalidSpaceConfigurationException
     */
    private function readSpaceConfig(string $spaceKey): array
    {
        $spaces = $this->config->get('webdav-server.storage.spaces', []);

        if (! is_array($spaces) || ! array_key_exists($spaceKey, $spaces)) {
            throw new SpaceNotConfiguredException(sprintf(
                'WebDAV storage space "%s" is not configured.',
                $spaceKey,
            ));
        }

        $spaceConfig = $spaces[$spaceKey];

        if (! is_array($spaceConfig)) {
            throw new InvalidSpaceConfigurationException(sprintf(
                'WebDAV storage space "%s" has an invalid configuration.',
                $spaceKey,
            ));
        }

        return $spaceConfig;
    }
}
