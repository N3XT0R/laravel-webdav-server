<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Integration\Providers;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Gate;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;
use N3XT0R\LaravelWebdavServer\Policies\PathPolicy;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;

final class WebdavServerServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        VerifyCsrfToken::flushState();

        parent::tearDown();
    }

    public function test_package_booted_registers_csrf_exclusions_from_base_uri_when_route_prefix_is_empty(): void
    {
        VerifyCsrfToken::flushState();
        config()->set('webdav-server.route_prefix', '');
        config()->set('webdav-server.base_uri', '/dav/');

        (new WebdavServerServiceProvider($this->app))->packageBooted();

        $csrfMiddleware = new VerifyCsrfToken($this->app, $this->app['encrypter']);

        $this->assertSame(['dav', 'dav/*'], $csrfMiddleware->getExcludedPaths());
        $this->assertInstanceOf(PathPolicy::class, Gate::getPolicyFor(PathResourceDto::class));
    }

    public function test_package_booted_skips_csrf_registration_when_no_route_prefix_or_base_uri_is_available(): void
    {
        VerifyCsrfToken::flushState();
        config()->set('webdav-server.route_prefix', '');
        config()->set('webdav-server.base_uri', '');

        (new WebdavServerServiceProvider($this->app))->packageBooted();

        $csrfMiddleware = new VerifyCsrfToken($this->app, $this->app['encrypter']);

        $this->assertSame([], $csrfMiddleware->getExcludedPaths());
    }
}
