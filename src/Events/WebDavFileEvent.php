<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Events;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

abstract readonly class WebDavFileEvent extends WebDavNodeEvent
{
    public function __construct(
        string $disk,
        string $path,
        WebDavPrincipalValueObject $principal,
        public int $bytes,
    ) {
        parent::__construct($disk, $path, $principal);
    }
}
