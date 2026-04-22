<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use Illuminate\Contracts\Config\Repository;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Server\Configuration\SabreServerConfigurator;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\AllowAllPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;
use Sabre\DAV\Server;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;

final class SabreServerConfiguratorTest extends TestCase
{
    private function makeSabreServer(): Server
    {
        $context = new StorageNodeContextDto(
            disk: 'local',
            filesystem: app('filesystem')->disk('local'),
            principal: new WebDavPrincipal('42', 'Alice'),
            authorization: new AllowAllPathAuthorization(),
        );

        return new Server(new StorageRootCollection('42', 'webdav/42', $context));
    }

    public function test_it_sets_base_uri_using_config_and_space_key(): void
    {
        $this->app->make(Repository::class)->set('webdav-server.base_uri', '/webdav/');

        $server = $this->makeSabreServer();
        (new SabreServerConfigurator())->configure($server, 'default');

        $this->assertSame('/webdav/default/', $server->getBaseUri());
    }

    public function test_it_trims_slashes_from_base_uri_and_space_key(): void
    {
        $this->app->make(Repository::class)->set('webdav-server.base_uri', '/dav/');

        $server = $this->makeSabreServer();
        (new SabreServerConfigurator())->configure($server, '/team-a/');

        $this->assertSame('/dav/team-a/', $server->getBaseUri());
    }

    public function test_it_combines_different_space_keys_into_the_base_uri(): void
    {
        $this->app->make(Repository::class)->set('webdav-server.base_uri', '/files/');

        $server = $this->makeSabreServer();
        (new SabreServerConfigurator())->configure($server, 'archive');

        $this->assertSame('/files/archive/', $server->getBaseUri());
    }
}
