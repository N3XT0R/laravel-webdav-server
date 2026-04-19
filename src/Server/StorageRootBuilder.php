<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final readonly class StorageRootBuilder implements StorageRootBuilderInterface
{
    public function __construct(
        private PathAuthorizationInterface $authorization,
        private FilesystemManager $filesystem,
    ) {}

    public function build(WebDavPrincipal $principal, WebDavStorageSpace $space): StorageRootCollection
    {
        return new StorageRootCollection(
            name: $principal->id,
            rootPath: $space->rootPath,
            context: new StorageNodeContextDto(
                disk: $space->disk,
                filesystem: $this->filesystem,
                principal: $principal,
                authorization: $this->authorization,
            ),
        );
    }
}

