<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Server;

use Illuminate\Contracts\Config\Repository;
use N3XT0R\LaravelWebdavServer\DTO\Storage\StorageNodeContextDto;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Server\Configuration\SabreServerConfigurator;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Auth\AllowAllPathAuthorization;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Logging\RecordingLogger;
use N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server\CustomSabrePlugin;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use N3XT0R\LaravelWebdavServer\WebdavServerServiceProvider;
use Sabre\DAV\Server;

final class SabreServerConfiguratorTest extends TestCase
{
    private function makeSabreServer(): Server
    {
        $context = new StorageNodeContextDto(
            disk: 'local',
            filesystem: app('filesystem')->disk('local'),
            principal: new WebDavPrincipalValueObject('42', 'Alice'),
            authorization: new AllowAllPathAuthorization,
        );

        return new Server(new StorageRootCollection('42', 'webdav/42', $context));
    }

    public function test_it_sets_base_uri_using_config_and_space_key(): void
    {
        $this->app->make(Repository::class)->set('webdav-server.base_uri', '/webdav/');

        $server = $this->makeSabreServer();
        (new SabreServerConfigurator(
            new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'),
        ))->configure($server, 'default');

        $this->assertSame('/webdav/default/', $server->getBaseUri());
    }

    public function test_it_trims_slashes_from_base_uri_and_space_key(): void
    {
        $this->app->make(Repository::class)->set('webdav-server.base_uri', '/dav/');

        $server = $this->makeSabreServer();
        (new SabreServerConfigurator(
            new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'),
        ))->configure($server, '/team-a/');

        $this->assertSame('/dav/team-a/', $server->getBaseUri());
    }

    public function test_it_combines_different_space_keys_into_the_base_uri(): void
    {
        $this->app->make(Repository::class)->set('webdav-server.base_uri', '/files/');

        $server = $this->makeSabreServer();
        (new SabreServerConfigurator(
            new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'),
        ))->configure($server, 'archive');

        $this->assertSame('/files/archive/', $server->getBaseUri());
    }

    public function test_it_attaches_the_package_logger_to_sabre_when_logging_is_enabled(): void
    {
        $server = $this->makeSabreServer();
        $logger = new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug');

        (new SabreServerConfigurator($logger))->configure($server, 'default');

        $property = new \ReflectionProperty($server, 'logger');

        $this->assertSame($logger, $property->getValue($server));
    }

    public function test_it_skips_sabre_logger_registration_when_logging_is_disabled(): void
    {
        $server = $this->makeSabreServer();

        (new SabreServerConfigurator(
            new WebDavLoggingService(null, null, 'debug'),
        ))->configure($server, 'default');

        $property = new \ReflectionProperty($server, 'logger');

        $this->assertNull($property->getValue($server));
    }

    public function test_it_registers_user_defined_tagged_sabre_plugins_after_package_defaults(): void
    {
        $plugin = new CustomSabrePlugin;

        $this->app->instance(CustomSabrePlugin::class, $plugin);
        $this->app->tag([CustomSabrePlugin::class], WebdavServerServiceProvider::sabrePluginTag());

        $server = $this->makeSabreServer();

        (new SabreServerConfigurator(
            new WebDavLoggingService(new RecordingLogger, 'stderr', 'debug'),
        ))->configure($server, 'default');

        $plugins = $server->getPlugins();

        $this->assertArrayHasKey('custom-sabre-plugin', $plugins);
        $this->assertSame($plugin, $plugins['custom-sabre-plugin']);
        $this->assertTrue($plugin->initialized);
    }
}
