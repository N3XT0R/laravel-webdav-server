<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Storage;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class StorageRootBuilder implements StorageRootBuilderInterface
{
    /**
     * Create the default storage-root builder for filesystem-backed WebDAV trees.
     *
     * @param  PathAuthorizationInterface  $authorization  Authorization adapter injected into every created node.
     * @param  Factory  $filesystem  Laravel filesystem manager used to resolve the configured disk instance.
     */
    public function __construct(
        private PathAuthorizationInterface $authorization,
        private FilesystemManager $filesystem,
    ) {}

    /**
     * Build the SabreDAV root collection for the resolved principal and storage space.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal whose ID becomes the root collection name.
     * @param  WebDavStorageSpaceValueObject  $space  Resolved storage target containing the disk and user-scoped root path.
     * @return StorageRootCollection Root collection representing the principal's WebDAV entry point.
     */
    public function build(WebDavPrincipalValueObject $principal, WebDavStorageSpaceValueObject $space): StorageRootCollection
    {
        return new StorageRootCollection(
            name: $principal->id,
            rootPath: $space->rootPath,
            context: new StorageNodeContextDto(
                disk: $space->disk,
                filesystem: $this->filesystem->disk($space->disk),
                principal: $principal,
                authorization: $this->authorization,
            ),
        );
    }
}
