<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

abstract readonly class WebDavNodeEvent
{
    use Dispatchable;

    public function __construct(
        public string $disk,
        public string $path,
        public WebDavPrincipalValueObject $principal,
    ) {}
}
