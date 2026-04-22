<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\ValueObjects;

use Illuminate\Contracts\Auth\Authenticatable;

final readonly class WebDavPrincipalValueObject
{
    public function __construct(
        public string $id,
        public string $displayName,
        public ?Authenticatable $user = null,
    ) {}

    public function getPrincipalUri(): string
    {
        return 'principals/'.$this->id;
    }
}
