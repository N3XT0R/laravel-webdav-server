<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\DTO\Server\WebDavRequestContextDto;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Server;

final class WebDavServerFactoryTest extends TestCase
{
    public function test_it_builds_and_configures_server_from_resolved_request_context(): void
    {
        $request = Request::create('/webdav', 'PROPFIND');
        $principal = new WebDavPrincipal('42', 'Alice');
        $space = new WebDavStorageSpace('local', 'webdav/42');
        $context = new WebDavRequestContextDto($principal, 'team-a', $space);

        $requestContextResolver = $this->createMock(RequestContextResolverInterface::class);
        $requestContextResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($request)
            ->willReturn($context);

        $nodeContext = new StorageNodeContextDto(
            disk: $space->disk,
            filesystem: $this->createStub(Filesystem::class),
            principal: $principal,
            authorization: $this->createStub(PathAuthorizationInterface::class),
        );

        $root = new StorageRootCollection(
            name: $principal->id,
            rootPath: $space->rootPath,
            context: $nodeContext,
        );

        $storageRootBuilder = $this->createMock(StorageRootBuilderInterface::class);
        $storageRootBuilder
            ->expects($this->once())
            ->method('build')
            ->with($principal, $space)
            ->willReturn($root);

        $serverConfigurator = $this->createMock(ServerConfiguratorInterface::class);
        $serverConfigurator
            ->expects($this->once())
            ->method('configure')
            ->with($this->isInstanceOf(Server::class), 'team-a');

        $factory = new WebDavServerFactory(
            requestContextResolver: $requestContextResolver,
            storageRootBuilder: $storageRootBuilder,
            serverConfigurator: $serverConfigurator,
        );

        $server = $factory->make($request);

        $this->assertInstanceOf(Server::class, $server);
    }
}