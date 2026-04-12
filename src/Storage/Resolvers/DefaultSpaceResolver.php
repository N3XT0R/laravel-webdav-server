<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Storage\Resolvers;

use Illuminate\Contracts\Config\Repository as Config;
use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final readonly class DefaultSpaceResolver implements SpaceResolverInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function resolve(WebDavPrincipal $principal): WebDavStorageSpace
    {
        $disk = (string)$this->config->get('webdav.storage.disk', 'local');
        $prefix = (string)$this->config->get('webdav.storage.prefix', 'webdav');

        return new WebDavStorageSpace(
            disk: $disk,
            rootPath: $prefix.'/'.$principal->id,
        );
    }
}