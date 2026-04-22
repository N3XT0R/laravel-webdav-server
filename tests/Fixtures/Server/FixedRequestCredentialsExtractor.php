<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\Contracts\Server\RequestCredentialsExtractorInterface;

final class FixedRequestCredentialsExtractor implements RequestCredentialsExtractorInterface
{
    /** @var list<Request> */
    public array $requests = [];

    /**
     * @param array{0:string,1:string} $credentials
     */
    public function __construct(
        private readonly array $credentials,
    ) {}

    public function extract(Request $request): array
    {
        $this->requests[] = $request;

        return $this->credentials;
    }
}
