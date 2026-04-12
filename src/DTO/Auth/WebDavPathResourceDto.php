<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Auth;

readonly class WebDavPathResourceDto
{
    public function __construct(
        public string $disk,
        public string $path,
    ) {}
}
