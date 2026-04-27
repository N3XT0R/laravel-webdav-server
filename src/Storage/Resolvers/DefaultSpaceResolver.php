<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Storage\Resolvers;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Config\Repository as Config;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\PathResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidSpaceConfigurationException;
use N3XT0R\LaravelWebdavServer\Exception\Storage\SpaceNotConfiguredException;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class DefaultSpaceResolver implements SpaceResolverInterface
{
    /**
     * Create the default config-driven space resolver.
     *
     * @param  Repository  $config  Package configuration repository used to resolve storage spaces.
     * @param  WebDavLoggingService  $logger  Package logger used to trace resolved storage targets.
     * @param  PathResolverInterface  $pathResolver  Resolves the user-scoped filesystem root path for the space.
     */
    public function __construct(
        private Config $config,
        private WebDavLoggingService $logger,
        private PathResolverInterface $pathResolver,
    ) {}

    /**
     * Resolve the concrete storage disk and user-scoped root path for the given principal and logical space key.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal whose ID is appended to the resolved root path.
     * @param  string  $spaceKey  Logical storage space key resolved from the request URL.
     * @return WebDavStorageSpaceValueObject Concrete disk plus user-scoped root path for the request.
     *
     * @throws SpaceNotConfiguredException When the requested space key is missing from configuration.
     * @throws InvalidSpaceConfigurationException When the configured space is incomplete or invalid.
     */
    public function resolve(WebDavPrincipalValueObject $principal, string $spaceKey): WebDavStorageSpaceValueObject
    {
        $spaces = $this->config->get('webdav-server.storage.spaces', []);

        if (! is_array($spaces) || ! array_key_exists($spaceKey, $spaces)) {
            throw new SpaceNotConfiguredException(sprintf(
                'WebDAV storage space "%s" is not configured.',
                $spaceKey,
            ));
        }

        $space = $spaces[$spaceKey];

        if (! is_array($space)) {
            throw new InvalidSpaceConfigurationException(sprintf(
                'WebDAV storage space "%s" has an invalid configuration.',
                $spaceKey,
            ));
        }

        $disk = $space['disk'] ?? null;

        if (! is_string($disk) || trim($disk) === '') {
            throw new InvalidSpaceConfigurationException(sprintf(
                'WebDAV storage space "%s" is missing a valid "disk" configuration.',
                $spaceKey,
            ));
        }

        $space = new WebDavStorageSpaceValueObject(
            disk: trim($disk),
            rootPath: $this->pathResolver->resolvePath($principal, $spaceKey),
        );

        $this->logger->debug('Resolved WebDAV storage space.', [
            'webdav' => [
                'principal_id' => $principal->id,
                'space_key' => $spaceKey,
                'disk' => $space->disk,
                'root_path' => $space->rootPath,
            ],
        ]);

        return $space;
    }
}
