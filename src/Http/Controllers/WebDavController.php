<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use N3XT0R\LaravelWebdavServer\Http\Server\WebDavServerFactory;
use Symfony\Component\HttpFoundation\Response;

class WebDavController extends Controller
{
    public function __construct(
        private readonly WebDavServerFactory $factory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return $this->factory->make()->handle($request);
    }
}