<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests;

use Illuminate\Contracts\Filesystem\Factory as FilesystemManager;
use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Server\WebDavServerFactory;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\Tests\Unit\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Server;

final class WebDavServerFactoryTest extends TestCase
{
    public function test_it_uses_route_space_parameter_for_resolving_storage_space(): void
    {
        config()->set('webdav.base_uri', '/webdav/');

        $principal = new WebDavPrincipal('42', 'Alice');

        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('alice', 'secret')
            ->willReturn($principal);

        $spaceResolver = $this->createMock(SpaceResolverInterface::class);
        $spaceResolver->expects($this->once())
            ->method('resolve')
            ->with($principal, 'team-a')
            ->willReturn(new WebDavStorageSpace('local', 'webdav/42'));

        $authorization = $this->createMock(PathAuthorizationInterface::class);
        $filesystem = $this->createMock(FilesystemManager::class);

        $factory = new WebDavServerFactory($validator, $spaceResolver, $authorization, $filesystem);

        $request = $this->makeRequestWithRouteSpace('team-a');

        $server = $factory->make($request);

        $this->assertInstanceOf(Server::class, $server);
    }

    public function test_it_falls_back_to_default_space_when_route_space_is_missing(): void
    {
        config()->set('webdav.base_uri', '/webdav/');
        config()->set('webdav.storage.default_space', 'default');

        $principal = new WebDavPrincipal('42', 'Alice');

        $validator = $this->createMock(CredentialValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with('alice', 'secret')
            ->willReturn($principal);

        $spaceResolver = $this->createMock(SpaceResolverInterface::class);
        $spaceResolver->expects($this->once())
            ->method('resolve')
            ->with($principal, 'default')
            ->willReturn(new WebDavStorageSpace('local', 'webdav/42'));

        $authorization = $this->createMock(PathAuthorizationInterface::class);
        $filesystem = $this->createMock(FilesystemManager::class);

        $factory = new WebDavServerFactory($validator, $spaceResolver, $authorization, $filesystem);

        $request = $this->makeRequestWithRouteSpace(null);

        $server = $factory->make($request);

        $this->assertInstanceOf(Server::class, $server);
    }

    private function makeRequestWithRouteSpace(?string $space): Request
    {
        $request = Request::create(
            uri: '/webdav',
            method: 'PROPFIND',
            server: [
                'PHP_AUTH_USER' => 'alice',
                'PHP_AUTH_PW' => 'secret',
            ],
        );

        $request->setRouteResolver(static fn () => new class($space)
        {
            public function __construct(private readonly ?string $space) {}

            public function parameter(string $key, mixed $default = null): mixed
            {
                if ($key === 'space') {
                    return $this->space;
                }

                return $default;
            }
        });

        return $request;
    }
}
