<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Providers\Registers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Log\LogManager;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use Psr\Log\LogLevel;

final readonly class LoggingRegister extends AbstractRegister
{
    protected function bindings(): array
    {
        return [];
    }

    public function register(): void
    {
        $this->app->scopedIf(WebDavLoggingService::class, function (Container $app): WebDavLoggingService {
            $config = $app->make(Repository::class);
            $driver = $config->get('webdav-server.logging.driver');
            $level = $config->get('webdav-server.logging.level', LogLevel::INFO);

            if (! is_string($driver) || trim($driver) === '') {
                return new WebDavLoggingService(
                    logger: null,
                    driver: null,
                    minimumLevel: is_string($level) ? $level : LogLevel::INFO,
                );
            }

            return new WebDavLoggingService(
                logger: $app->make(LogManager::class)->channel($driver),
                driver: trim($driver),
                minimumLevel: is_string($level) ? $level : LogLevel::INFO,
            );
        });
    }
}
