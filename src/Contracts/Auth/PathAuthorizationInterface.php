<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Auth;

use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\Forbidden;

interface PathAuthorizationInterface
{
    /**
     * Authorize read access for the given path before a filesystem read operation executes.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting access.
     * @param  string  $disk  Laravel filesystem disk that will be accessed.
     * @param  string  $path  Resolved path on the target disk.
     *
     * @throws Forbidden When the principal is not allowed to read the path.
     */
    public function authorizeRead(WebDavPrincipalValueObject $principal, string $disk, string $path): void;

    /**
     * Authorize write access for the given path before an existing file is overwritten.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting access.
     * @param  string  $disk  Laravel filesystem disk that will be accessed.
     * @param  string  $path  Resolved path on the target disk.
     *
     * @throws Forbidden When the principal is not allowed to modify the path.
     */
    public function authorizeWrite(WebDavPrincipalValueObject $principal, string $disk, string $path): void;

    /**
     * Authorize deletion of the given path before filesystem content is removed.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting access.
     * @param  string  $disk  Laravel filesystem disk that will be accessed.
     * @param  string  $path  Resolved path on the target disk.
     *
     * @throws Forbidden When the principal is not allowed to delete the path.
     */
    public function authorizeDelete(WebDavPrincipalValueObject $principal, string $disk, string $path): void;

    /**
     * Authorize creation of a directory at the given path.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting access.
     * @param  string  $disk  Laravel filesystem disk that will be accessed.
     * @param  string  $path  Resolved directory path on the target disk.
     *
     * @throws Forbidden When the principal is not allowed to create the directory.
     */
    public function authorizeCreateDirectory(WebDavPrincipalValueObject $principal, string $disk, string $path): void;

    /**
     * Authorize creation of a file at the given path.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting access.
     * @param  string  $disk  Laravel filesystem disk that will be accessed.
     * @param  string  $path  Resolved file path on the target disk.
     *
     * @throws Forbidden When the principal is not allowed to create the file.
     */
    public function authorizeCreateFile(WebDavPrincipalValueObject $principal, string $disk, string $path): void;
}
