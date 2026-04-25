<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Storage\Data;

final readonly class WebDavStorageSpaceValueObject
{
    /**
     * Create the resolved storage space used for the current WebDAV request.
     *
     * @param string $disk Laravel filesystem disk that should serve the request.
     * @param string $rootPath User-scoped root path on the target disk.
     */
    public function __construct(
        public string $disk,
        public string $rootPath,
    ) {}
}
