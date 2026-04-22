<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Storage\Data;

final readonly class WebDavStorageSpaceValueObject
{
    public function __construct(
        public string $disk,
        public string $rootPath,
    ) {}
}
