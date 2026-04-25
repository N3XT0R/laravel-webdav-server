<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Unit\Providers\Registers;

use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Providers\Registers\LoggingRegister;
use N3XT0R\LaravelWebdavServer\Tests\TestCase;

final class LoggingRegisterTest extends TestCase
{
    public function test_register_creates_a_disabled_logging_service_when_no_driver_is_configured(): void
    {
        config()->set('webdav-server.logging.driver', null);
        config()->set('webdav-server.logging.level', 'debug');

        $register = new LoggingRegister($this->app);
        $register->register();

        $logger = $this->app->make(WebDavLoggingService::class);

        $this->assertFalse($logger->isEnabled());
        $this->assertNull($logger->driver());
        $this->assertSame('debug', $logger->minimumLevel());
    }

    public function test_register_creates_an_enabled_logging_service_for_the_configured_driver(): void
    {
        config()->set('webdav-server.logging.driver', 'stack');
        config()->set('webdav-server.logging.level', 'info');

        $register = new LoggingRegister($this->app);
        $register->register();

        $logger = $this->app->make(WebDavLoggingService::class);

        $this->assertTrue($logger->isEnabled());
        $this->assertSame('stack', $logger->driver());
        $this->assertSame('info', $logger->minimumLevel());
        $this->assertSame($logger, $logger->sabreLogger());
    }

    public function test_bindings_returns_an_empty_list_because_the_register_uses_a_custom_scoped_binding(): void
    {
        $register = new LoggingRegister($this->app);
        $method = new \ReflectionMethod($register, 'bindings');

        $this->assertSame([], $method->invoke($register));
    }
}
