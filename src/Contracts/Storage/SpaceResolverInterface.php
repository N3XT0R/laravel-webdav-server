<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Storage;

use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

interface SpaceResolverInterface
{
    public function resolve(WebDavPrincipal $principal): WebDavStorageSpace;
}
