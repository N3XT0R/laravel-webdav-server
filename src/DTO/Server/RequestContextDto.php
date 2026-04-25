<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Server;

use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final readonly class RequestContextDto
{
    /**
     * Create the immutable runtime context used while building the SabreDAV server.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal for the request.
     * @param  string  $spaceKey  Logical storage space key resolved from the URL or fallback config.
     * @param  WebDavStorageSpaceValueObject  $space  Concrete storage target resolved for the request.
     */
    public function __construct(
        public WebDavPrincipalValueObject $principal,
        public string $spaceKey,
        public WebDavStorageSpaceValueObject $space,
    ) {}
}
