<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class StorageNodeContextDto
{
    /**
     * Create the immutable context shared by SabreDAV storage nodes.
     *
     * @param  string  $disk  Laravel filesystem disk currently serving the request.
     * @param  Filesystem  $filesystem  Resolved filesystem instance for the target disk.
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal for the current request.
     * @param  PathAuthorizationInterface  $authorization  Authorization adapter invoked before filesystem operations.
     */
    public function __construct(
        public string $disk,
        public Filesystem $filesystem,
        public WebDavPrincipalValueObject $principal,
        public PathAuthorizationInterface $authorization,
    ) {}
}
