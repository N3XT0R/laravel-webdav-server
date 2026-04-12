<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use N3XT0R\LaravelWebdavServer\Server\WebDavServerFactory;
use Symfony\Component\HttpFoundation\Response;

final class WebDavController extends Controller
{
    public function __construct(
        private readonly WebDavServerFactory $factory,
    ) {}

    public function __invoke(Request $request): Response
    {
        $server = $this->factory->make()->create();

        ob_start();

        try {
            $server->start();
            $content = (string) ob_get_clean();

            return response($content);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            ob_end_clean();
        }
    }
}
