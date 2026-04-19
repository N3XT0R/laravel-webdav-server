<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Server;

use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final readonly class WebDavRequestContextDto
{
    public function __construct(
        public WebDavPrincipal $principal,
        public string $spaceKey,
        public WebDavStorageSpace $space,
    ) {}
}
