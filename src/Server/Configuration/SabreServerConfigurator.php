<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Server\Configuration;

use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerConfiguratorInterface;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Server\Configuration\Plugins\CompatibilityLoggingPlugin;
use N3XT0R\LaravelWebdavServer\Server\Configuration\Plugins\MissingPathPropFindPlugin;
use Sabre\DAV\Server;

final readonly class SabreServerConfigurator implements ServerConfiguratorInterface
{
    /**
     * @param  WebDavLoggingService  $logger  Package logger used for SabreDAV runtime logs and package-level debug tracing.
     */
    public function __construct(
        private WebDavLoggingService $logger,
    ) {}

    /**
     * Configure the SabreDAV runtime for the resolved logical storage space.
     *
     * @param  Server  $server  Prepared SabreDAV server instance.
     * @param  string  $spaceKey  Logical storage space key currently being served.
     */
    public function configure(Server $server, string $spaceKey): void
    {
        $baseUri = trim((string) config('webdav-server.base_uri', '/webdav/'), '/');
        $space = trim($spaceKey, '/');

        $server->setBaseUri('/'.$baseUri.'/'.$space.'/');
        $server->addPlugin(new MissingPathPropFindPlugin($this->logger));
        $server->addPlugin(new CompatibilityLoggingPlugin($this->logger));

        $sabreLogger = $this->logger->sabreLogger();

        if ($sabreLogger !== null) {
            $server->setLogger($sabreLogger);
        }

        $this->logger->debug('Configured SabreDAV runtime.', [
            'webdav' => [
                'space_key' => $spaceKey,
                'base_uri' => $server->getBaseUri(),
                'logging_enabled' => $this->logger->isEnabled(),
                'logging_driver' => $this->logger->driver(),
                'logging_level' => $this->logger->minimumLevel(),
            ],
        ]);
    }
}
