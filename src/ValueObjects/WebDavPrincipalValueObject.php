<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\ValueObjects;

use Illuminate\Contracts\Auth\Authenticatable;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\WebDavPrincipalInterface;

final readonly class WebDavPrincipalValueObject implements WebDavPrincipalInterface
{
    /**
     * Create the authenticated WebDAV principal value object.
     *
     * @param  string  $id  Stable principal identifier used for routing, storage resolution, and principal URIs.
     * @param  string  $displayName  Human-readable principal name for WebDAV clients and policies.
     * @param  Authenticatable|null  $user  Linked Laravel user for Gate / policy integration, if available.
     */
    public function __construct(
        public string $id,
        public string $displayName,
        public ?Authenticatable $user = null,
    ) {}

    public function getPrincipalId(): string
    {
        return $this->id;
    }

    /**
     * Build the SabreDAV principal URI for the authenticated principal.
     *
     * @return string Principal URI in `principals/{id}` form.
     */
    public function getPrincipalUri(): string
    {
        return 'principals/'.$this->id;
    }
}
