<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Storage;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final readonly class StorageNodeContextDto
{
    public function __construct(
        public string $disk,
        public FilesystemManager $filesystem,
        public WebDavPrincipal $principal,
        public PathAuthorizationInterface $authorization,
    ) {}
}
