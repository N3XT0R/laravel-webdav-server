<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;
use N3XT0R\LaravelWebdavServer\DTO\Server\WebDavRequestContextDto;

interface RequestContextResolverInterface
{
    public function resolve(Request $request): WebDavRequestContextDto;
}

