<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\DTO\Auth;

final readonly class PathResourceDto
{
    /**
     * Create a path-based authorization resource for Gate / policy checks.
     *
     * @param string $disk Laravel filesystem disk the operation targets.
     * @param string $path Resolved path on the target disk.
     */
    public function __construct(
        public string $disk,
        public string $path,
    ) {}
}
