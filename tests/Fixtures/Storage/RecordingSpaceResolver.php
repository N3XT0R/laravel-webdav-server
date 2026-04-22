<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Storage;

use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpace;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipal;

final class RecordingSpaceResolver implements SpaceResolverInterface
{
    /** @var list<array{principal:WebDavPrincipal,spaceKey:string}> */
    public array $calls = [];

    public function __construct(
        private readonly WebDavStorageSpace $space,
    ) {}

    public function resolve(WebDavPrincipal $principal, string $spaceKey): WebDavStorageSpace
    {
        $this->calls[] = [
            'principal' => $principal,
            'spaceKey' => $spaceKey,
        ];

        return $this->space;
    }
}
