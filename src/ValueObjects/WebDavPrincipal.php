<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\ValueObjects;

final readonly class WebDavPrincipal
{
    public function __construct(
        public string $id,
        public string $displayName,
    ) {
    }

    public function getPrincipalUri(): string
    {
        return 'principals/'.$this->id;
    }
}