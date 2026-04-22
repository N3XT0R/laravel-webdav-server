<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestContextResolverInterface;
use N3XT0R\LaravelWebdavServer\DTO\Server\WebDavRequestContextDto;

final class FixedRequestContextResolver implements RequestContextResolverInterface
{
    /** @var list<Request> */
    public array $requests = [];

    public function __construct(
        private readonly WebDavRequestContextDto $context,
    ) {}

    public function resolve(Request $request): WebDavRequestContextDto
    {
        $this->requests[] = $request;

        return $this->context;
    }
}
