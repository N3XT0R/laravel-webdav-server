<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\ValueObjects;

use Illuminate\Contracts\Auth\Authenticatable;

final readonly class WebDavPrincipalValueObject
{
    /**
     * Create the authenticated WebDAV principal value object.
     *
     * @param string $id Stable principal identifier used for routing, storage resolution, and principal URIs.
     * @param string $displayName Human-readable principal name for WebDAV clients and policies.
     * @param \Illuminate\Contracts\Auth\Authenticatable|null $user Linked Laravel user for Gate / policy integration, if available.
     */
    public function __construct(
        public string $id,
        public string $displayName,
        public ?Authenticatable $user = null,
    ) {}

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
