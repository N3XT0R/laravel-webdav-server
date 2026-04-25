<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Auth\Authorization;

use Illuminate\Contracts\Auth\Access\Gate;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Exception\Forbidden;

final readonly class GatePathAuthorization implements PathAuthorizationInterface
{
    /**
     * Create the default Gate-backed authorization adapter.
     *
     * @param  Gate  $gate  Laravel Gate instance used to evaluate path policies for the linked user.
     * @param  WebDavLoggingService  $logger  Package logger used to trace path-authorization checks.
     */
    public function __construct(
        private Gate $gate,
        private WebDavLoggingService $logger,
    ) {}

    /**
     * Authorize read access for the given disk path through Laravel Gate.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting read access.
     * @param  string  $disk  Laravel filesystem disk that will be read.
     * @param  string  $path  Resolved path on the target disk.
     *
     * @throws Forbidden When Gate denies read access for the linked user and path resource.
     */
    public function authorizeRead(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'read', $disk, $path);
    }

    /**
     * Authorize write access for an existing file path through Laravel Gate.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting write access.
     * @param  string  $disk  Laravel filesystem disk that will be written.
     * @param  string  $path  Resolved path on the target disk.
     *
     * @throws Forbidden When Gate denies write access for the linked user and path resource.
     */
    public function authorizeWrite(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'write', $disk, $path);
    }

    /**
     * Authorize deletion of the given disk path through Laravel Gate.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting delete access.
     * @param  string  $disk  Laravel filesystem disk from which content will be deleted.
     * @param  string  $path  Resolved path on the target disk.
     *
     * @throws Forbidden When Gate denies delete access for the linked user and path resource.
     */
    public function authorizeDelete(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'delete', $disk, $path);
    }

    /**
     * Authorize directory creation at the given disk path through Laravel Gate.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting directory creation.
     * @param  string  $disk  Laravel filesystem disk where the directory will be created.
     * @param  string  $path  Resolved directory path on the target disk.
     *
     * @throws Forbidden When Gate denies directory creation for the linked user and path resource.
     */
    public function authorizeCreateDirectory(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'createDirectory', $disk, $path);
    }

    /**
     * Authorize file creation at the given disk path through Laravel Gate.
     *
     * @param  WebDavPrincipalValueObject  $principal  Authenticated principal requesting file creation.
     * @param  string  $disk  Laravel filesystem disk where the file will be created.
     * @param  string  $path  Resolved file path on the target disk.
     *
     * @throws Forbidden When Gate denies file creation for the linked user and path resource.
     */
    public function authorizeCreateFile(WebDavPrincipalValueObject $principal, string $disk, string $path): void
    {
        $this->authorize($principal, 'createFile', $disk, $path);
    }

    private function authorize(WebDavPrincipalValueObject $principal, string $ability, string $disk, string $path): void
    {
        $resource = new PathResourceDto(
            disk: $disk,
            path: $path,
        );

        $this->logger->debug('Authorizing WebDAV path through Laravel Gate.', [
            'auth' => [
                'ability' => $ability,
                'principal_id' => $principal->id,
                'user_id' => $principal->user?->getAuthIdentifier(),
            ],
            'webdav' => [
                'disk' => $disk,
                'path' => $path,
            ],
        ]);

        $response = $this->gate->forUser($principal->user)->inspect($ability, $resource);

        if (! $response->allowed()) {
            $this->logger->info('WebDAV path authorization was denied.', [
                'auth' => [
                    'ability' => $ability,
                    'principal_id' => $principal->id,
                    'user_id' => $principal->user?->getAuthIdentifier(),
                ],
                'webdav' => [
                    'disk' => $disk,
                    'path' => $path,
                ],
            ]);

            throw new Forbidden($response->message() ?: 'Access denied.');
        }
    }
}
