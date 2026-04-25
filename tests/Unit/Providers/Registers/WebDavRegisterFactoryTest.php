<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Providers\Registers;

use Illuminate\Container\Container;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\CredentialValidatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Auth\PathAuthorizationInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Repositories\AccountRepositoryInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\PrincipalAuthenticatorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Providers\Registers\WebDavRegisterFactory;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;
use PHPUnit\Framework\TestCase;

final class WebDavRegisterFactoryTest extends TestCase
{
    public function test_it_registers_all_package_bindings(): void
    {
        $container = new Container;
        $factory = new WebDavRegisterFactory($container);

        $factory->registerAll();

        $this->assertTrue($container->bound(AccountRepositoryInterface::class));
        $this->assertTrue($container->bound(CredentialValidatorInterface::class));
        $this->assertTrue($container->bound(PathAuthorizationInterface::class));
        $this->assertTrue($container->bound(SpaceResolverInterface::class));
        $this->assertTrue($container->bound(StorageRootBuilderInterface::class));
        $this->assertTrue($container->bound(RequestCredentialsExtractorInterface::class));
        $this->assertTrue($container->bound(PrincipalAuthenticatorInterface::class));
        $this->assertTrue($container->bound(SpaceKeyResolverInterface::class));
        $this->assertTrue($container->bound(RequestContextResolverInterface::class));
        $this->assertTrue($container->bound(ServerConfiguratorInterface::class));
        $this->assertTrue($container->bound(ServerRunnerInterface::class));
        $this->assertTrue($container->bound(WebDavServerFactory::class));
    }
}
