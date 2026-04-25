<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Exception\Storage\InvalidDefaultSpaceConfigurationException;
use N3XT0R\LaravelWebdavServer\Server\Request\Routing\RequestSpaceKeyResolver;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;

final class RequestSpaceKeyResolverTest extends TestCase
{
    public function test_it_uses_route_space_parameter_when_present(): void
    {
        $resolver = new RequestSpaceKeyResolver;
        $request = Request::create('/webdav', 'PROPFIND');

        $request->setRouteResolver(static fn () => new class
        {
            public function parameter(string $key, mixed $default = null): mixed
            {
                return $key === 'space' ? 'team-a' : $default;
            }
        });

        $this->assertSame('team-a', $resolver->resolve($request));
    }

    public function test_it_falls_back_to_configured_default_space(): void
    {
        config()->set('webdav-server.storage.default_space', 'default');

        $resolver = new RequestSpaceKeyResolver;
        $request = Request::create('/webdav', 'PROPFIND');

        $this->assertSame('default', $resolver->resolve($request));
    }

    public function test_it_throws_when_default_space_configuration_is_invalid(): void
    {
        $this->expectException(InvalidDefaultSpaceConfigurationException::class);

        config()->set('webdav-server.storage.default_space', '');

        $resolver = new RequestSpaceKeyResolver;
        $request = Request::create('/webdav', 'PROPFIND');

        $resolver->resolve($request);
    }
}
