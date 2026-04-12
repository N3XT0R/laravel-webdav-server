<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use RuntimeException;
use Sabre\DAV\Server;

final readonly class WebDavServerFactory
{
    public function __construct(
        private CredentialValidatorInterface $validator,
        private SpaceResolverInterface $spaceResolver,
        private FilesystemManager $filesystem,
    ) {}

    public function make(Request $request): Server
    {
        [$username, $password] = $this->extractBasicCredentials($request);

        $principal = $this->validator->validate($username, $password);

        if ($principal === null) {
            throw new RuntimeException('Invalid WebDAV credentials.');
        }

        $space = $this->spaceResolver->resolve($principal);

        $root = new StorageRootCollection(
            name: $principal->id,
            disk: $space->disk,
            rootPath: $space->rootPath,
            filesystem: $this->filesystem,
        );

        $server = new Server($root);
        $server->setBaseUri((string) config('webdav.base_uri', '/webdav/'));

        return $server;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractBasicCredentials(Request $request): array
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        if (! is_string($username) || ! is_string($password)) {
            throw new RuntimeException('Missing Basic Auth credentials.');
        }

        return [$username, $password];
    }
}
