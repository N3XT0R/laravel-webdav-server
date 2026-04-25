<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Providers\Registers;

use Illuminate\Container\Container;
use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\DTO\Server\RequestContextDto;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Providers\Registers\ServerFactoryRegister;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use PHPUnit\Framework\TestCase;
use Sabre\DAV\Server;

final class ServerFactoryRegisterTest extends TestCase
{
    public function test_register_binds_a_scoped_webdav_server_factory(): void
    {
        $container = new Container;
        $container->instance(RequestContextResolverInterface::class, new class implements RequestContextResolverInterface
        {
            public function resolve(Request $request): RequestContextDto
            {
                return new RequestContextDto(
                    principal: new WebDavPrincipalValueObject('1', 'Alice'),
                    spaceKey: 'default',
                    space: new WebDavStorageSpaceValueObject('local', 'webdav/1'),
                );
            }
        });
        $container->instance(StorageRootBuilderInterface::class, new class implements StorageRootBuilderInterface
        {
            public function build(WebDavPrincipalValueObject $principal, WebDavStorageSpaceValueObject $space): StorageRootCollection
            {
                throw new \LogicException('Not used in this test.');
            }
        });
        $container->instance(ServerConfiguratorInterface::class, new class implements ServerConfiguratorInterface
        {
            public function configure(Server $server, string $spaceKey): void {}
        });

        $register = new ServerFactoryRegister($container);
        $register->register();

        $this->assertTrue($container->bound(WebDavServerFactory::class));
        $this->assertInstanceOf(WebDavServerFactory::class, $container->make(WebDavServerFactory::class));
    }

    public function test_bindings_returns_an_empty_list_because_the_register_uses_a_custom_scoped_binding(): void
    {
        $register = new ServerFactoryRegister(new Container);
        $method = new \ReflectionMethod($register, 'bindings');

        $this->assertSame([], $method->invoke($register));
    }
}
