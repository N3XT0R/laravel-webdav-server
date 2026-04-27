<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

interface WebDavPrincipalInterface
{
    /**
     * Return the principal identifier used for storage path resolution and WebDAV principal URIs.
     *
     * @return string Stable principal identifier.
     */
    public function getPrincipalId(): string;
}
