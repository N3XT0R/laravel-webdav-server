<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Contracts\Server;

use Illuminate\Http\Request;

interface RequestCredentialsExtractorInterface
{
    /**
     * @return array{0:string,1:string}
     */
    public function extract(Request $request): array;
}
