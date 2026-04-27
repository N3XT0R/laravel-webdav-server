<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

use Illuminate\Contracts\Auth\Authenticatable;

interface AccountInterface extends WebDavPrincipalInterface
{
    /**
     * Return the principal identifier that should back the WebDAV principal URI.
     *
     * @return string Principal identifier used for authenticated WebDAV requests.
     */
    public function getPrincipalId(): string;

    /**
     * Return the human-readable display name for the authenticated account.
     *
     * @return string Display name exposed to WebDAV consumers.
     */
    public function getDisplayName(): string;

    /**
     * Return the hashed password used for credential verification.
     *
     * @return string Stored password hash for the WebDAV account.
     */
    public function getPasswordHash(): string;

    /**
     * Return the linked Laravel user if the WebDAV account is associated with one.
     *
     * @return Authenticatable|null Linked Laravel user or null when the account is standalone.
     */
    public function getUser(): ?Authenticatable;
}
