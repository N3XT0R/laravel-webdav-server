<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Server;

use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class RequestContextDto
{
    public function __construct(
        public WebDavPrincipalValueObject $principal,
        public string $spaceKey,
        public WebDavStorageSpaceValueObject $space,
    ) {}
}
