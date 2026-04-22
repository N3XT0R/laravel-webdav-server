<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use N3XT0R\LaravelWebdavServer\Contracts\Server\StorageRootBuilderInterface;
use N3XT0R\LaravelWebdavServer\Nodes\StorageRootCollection;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class RecordingStorageRootBuilder implements StorageRootBuilderInterface
{
    /** @var list<array{principal:WebDavPrincipal,space:WebDavStorageSpace}> */
    public array $calls = [];

    public function __construct(
        private readonly StorageRootCollection $root,
    ) {}

    public function build(WebDavPrincipal $principal, WebDavStorageSpace $space): StorageRootCollection
    {
        $this->calls[] = [
            'principal' => $principal,
            'space' => $space,
        ];

        return $this->root;
    }
}
