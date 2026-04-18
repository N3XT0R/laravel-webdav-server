<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Exception\Auth\InvalidCredentialsException;
use N3XT0R\LaravelWebdavServer\Exception\Auth\MissingCredentialsException;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use RuntimeException;
use Sabre\DAV\Server;

final readonly class WebDavServerFactory
{
    public function __construct(
        private CredentialValidatorInterface $validator,
        private SpaceResolverInterface $spaceResolver,
        private PathAuthorizationInterface $authorization,
        private FilesystemManager $filesystem,
    ) {
    }

    public function make(Request $request): Server
    {
        [$username, $password] = $this->extractBasicCredentials($request);

        $principal = $this->validator->validate($username, $password);

        if ($principal === null) {
            throw new InvalidCredentialsException(
                message: 'Invalid WebDAV credentials.',
                context: [
                    'request' => [
                        'method' => $request->getMethod(),
                        'uri' => $request->getRequestUri(),
                        'user_agent' => $request->userAgent(),
                    ],
                    'auth' => [
                        'username' => $username,
                    ],
                ],
            );
        }

        $spaceKey = $this->resolveSpaceKey($request);
        $space = $this->spaceResolver->resolve($principal, $spaceKey);

        $root = new StorageRootCollection(
            name: $principal->id,
            rootPath: $space->rootPath,
            context: new StorageNodeContextDto(
                disk: $space->disk,
                filesystem: $this->filesystem,
                principal: $principal,
                authorization: $this->authorization,
            ),
        );

        $server = new Server($root);
        $server->setBaseUri((string)config('webdav.base_uri', '/webdav/'));

        return $server;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractBasicCredentials(Request $request): array
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        if (is_string($username) && is_string($password)) {
            return [$username, $password];
        }

        $authorization = $request->headers->get('Authorization');

        if (!is_string($authorization) || !str_starts_with($authorization, 'Basic ')) {
            throw new MissingCredentialsException(
                message: 'Basic Auth credentials are required to access the WebDAV server.',
                context: [
                    'request' => [
                        'method' => $request->getMethod(),
                        'uri' => $request->getRequestUri(),
                        'user_agent' => $request->userAgent(),
                        'headers' => $request->headers->all(),
                    ],
                ],
            );
        }

        $encoded = substr($authorization, 6);
        $decoded = base64_decode($encoded, true);

        if (!is_string($decoded) || !str_contains($decoded, ':')) {
            throw new InvalidCredentialsException(
                message: 'Malformed Basic Auth header.',
                context: [
                    'request' => [
                        'method' => $request->getMethod(),
                        'uri' => $request->getRequestUri(),
                        'user_agent' => $request->userAgent(),
                    ],
                ],
            );
        }

        [$username, $password] = explode(':', $decoded, 2);

        if ($username === '' || $password === '') {
            throw new InvalidCredentialsException(
                message: 'Incomplete Basic Auth credentials.',
                context: [
                    'request' => [
                        'method' => $request->getMethod(),
                        'uri' => $request->getRequestUri(),
                        'user_agent' => $request->userAgent(),
                    ],
                ],
            );
        }

        return [$username, $password];
    }

    private function resolveSpaceKey(Request $request): string
    {
        $space = $request->route('space');

        if (is_string($space) && trim($space) !== '') {
            return trim($space);
        }

        $defaultSpace = config('webdav.storage.default_space', 'default');

        if (!is_string($defaultSpace) || trim($defaultSpace) === '') {
            throw new RuntimeException('Missing or invalid webdav.storage.default_space configuration.');
        }

        return trim($defaultSpace);
    }
}