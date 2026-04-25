<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

interface StorageRootBuilderInterface
{
    /**
     * Build the SabreDAV root collection for the resolved principal and storage space.
     *
     * @param \N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject $principal Authenticated principal for the current request.
     * @param \N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject $space Resolved storage space containing target disk and user-scoped root path.
     *
     * @return \N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection Root SabreDAV collection used as the server tree root.
     */
    public function build(WebDavPrincipalValueObject $principal, WebDavStorageSpaceValueObject $space): StorageRootCollection;
}
