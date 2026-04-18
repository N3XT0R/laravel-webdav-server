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
    ) {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->hasBasicAuthAttempt($request)) {
            return response('Unauthorized', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Basic realm="WebDAV"',
            ]);
        }
        $server = $this->factory->make($request);

        ob_start();

        try {
            $server->start();
            $content = (string)ob_get_clean();

            return response($content);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
        }
    }

    private function hasBasicAuthAttempt(Request $request): bool
    {
        if (is_string($request->getUser()) && is_string($request->getPassword())) {
            return true;
        }

        $authorization = $request->headers->get('Authorization');

        return is_string($authorization) && str_starts_with($authorization, 'Basic ');
    }
}
