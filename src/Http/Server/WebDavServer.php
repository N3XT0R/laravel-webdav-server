<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Http\Server;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebDavServer
{
    public function handle(Request $request): Response
    {
        return new Response('WebDAV bootstrap placeholder', 200);
    }
}