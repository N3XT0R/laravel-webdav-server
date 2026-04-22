<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\SpaceKeyResolverInterface;

final class FixedSpaceKeyResolver implements SpaceKeyResolverInterface
{
    /** @var list<Request> */
    public array $requests = [];

    public function __construct(
        private readonly string $spaceKey,
    ) {}

    public function resolve(Request $request): string
    {
        $this->requests[] = $request;

        return $this->spaceKey;
    }
}
