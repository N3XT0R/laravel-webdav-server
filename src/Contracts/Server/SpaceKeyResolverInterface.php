<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;

interface SpaceKeyResolverInterface
{
    public function resolve(Request $request): string;
}

