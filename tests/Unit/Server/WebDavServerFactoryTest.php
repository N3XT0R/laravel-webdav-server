<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\DTO\Server\RequestContextDto;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\AllowAllPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\FixedRequestContextResolver;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\RecordingServerConfigurator;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\RecordingStorageRootBuilder;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Sabre\DAV\Server;

final class WebDavServerFactoryTest extends TestCase
{
    public function test_it_builds_and_configures_server_from_resolved_request_context(): void
    {
        $request = Request::create('/webdav', 'PROPFIND');
        $principal = new WebDavPrincipalValueObject('42', 'Alice');
        $space = new WebDavStorageSpaceValueObject('local', 'webdav/42');
        $context = new RequestContextDto($principal, 'team-a', $space);

        $root = new StorageRootCollection(
            name: $principal->id,
            rootPath: $space->rootPath,
            context: new StorageNodeContextDto(
                disk: $space->disk,
                filesystem: app('filesystem')->disk('local'),
                principal: $principal,
                authorization: new AllowAllPathAuthorization,
            ),
        );

        $requestContextResolver = new FixedRequestContextResolver($context);
        $storageRootBuilder = new RecordingStorageRootBuilder($root);
        $serverConfigurator = new RecordingServerConfigurator;

        $factory = new WebDavServerFactory(
            requestContextResolver: $requestContextResolver,
            storageRootBuilder: $storageRootBuilder,
            serverConfigurator: $serverConfigurator,
        );

        $server = $factory->make($request);

        $this->assertInstanceOf(Server::class, $server);
        $this->assertCount(1, $requestContextResolver->requests);
        $this->assertSame([['principal' => $principal, 'space' => $space]], $storageRootBuilder->calls);
        $this->assertCount(1, $serverConfigurator->calls);
        $this->assertSame('team-a', $serverConfigurator->calls[0]['spaceKey']);
    }
}
