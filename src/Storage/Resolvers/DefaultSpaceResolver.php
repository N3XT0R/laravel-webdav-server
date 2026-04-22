<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Storage\Resolvers;

use Illuminate\Contracts\Config\Repository as Config;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use RuntimeException;

final readonly class DefaultSpaceResolver implements SpaceResolverInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function resolve(WebDavPrincipalValueObject $principal, string $spaceKey): WebDavStorageSpaceValueObject
    {
        $spaces = $this->config->get('webdav-server.storage.spaces', []);

        if (! is_array($spaces) || ! array_key_exists($spaceKey, $spaces)) {
            throw new RuntimeException(sprintf(
                'WebDAV storage space "%s" is not configured.',
                $spaceKey,
            ));
        }

        $space = $spaces[$spaceKey];

        if (! is_array($space)) {
            throw new RuntimeException(sprintf(
                'WebDAV storage space "%s" has an invalid configuration.',
                $spaceKey,
            ));
        }

        $disk = $space['disk'] ?? null;
        $root = $space['root'] ?? null;
        $prefix = $space['prefix'] ?? null;

        if (! is_string($disk) || trim($disk) === '') {
            throw new RuntimeException(sprintf(
                'WebDAV storage space "%s" is missing a valid "disk" configuration.',
                $spaceKey,
            ));
        }

        if (! is_string($root) || trim($root) === '') {
            throw new RuntimeException(sprintf(
                'WebDAV storage space "%s" is missing a valid "root" configuration.',
                $spaceKey,
            ));
        }

        $parts = [trim($root, '/')];

        if (is_string($prefix) && trim($prefix) !== '' && trim($prefix) !== '/') {
            $parts[] = trim($prefix, '/');
        }

        $parts[] = (string) $principal->id;

        return new WebDavStorageSpaceValueObject(
            disk: trim($disk),
            rootPath: implode('/', $parts),
        );
    }
}
