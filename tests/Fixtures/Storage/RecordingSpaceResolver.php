<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Storage;

use N3XT0R\LaravelWebdavServer\Contracts\Storage\SpaceResolverInterface;
use N3XT0R\LaravelWebdavServer\Storage\Data\WebDavStorageSpaceValueObject;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;

final class RecordingSpaceResolver implements SpaceResolverInterface
{
    /** @var list<array{principal:WebDavPrincipalValueObject,spaceKey:string}> */
    public array $calls = [];

    public function __construct(
        private readonly WebDavStorageSpaceValueObject $space,
    ) {}

    public function resolve(WebDavPrincipalValueObject $principal, string $spaceKey): WebDavStorageSpaceValueObject
    {
        $this->calls[] = [
            'principal' => $principal,
            'spaceKey' => $spaceKey,
        ];

        return $this->space;
    }
}
