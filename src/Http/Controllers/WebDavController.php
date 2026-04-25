<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;
use Symfony\Component\HttpFoundation\Response;

final class WebDavController extends Controller
{
    /**
     * Create the HTTP controller that orchestrates WebDAV request entry handling.
     *
     * @param \N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory $factory Factory used to construct the SabreDAV server for the request.
     * @param \N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface $serverRunner Runtime adapter used to hand execution off to SabreDAV.
     */
    public function __construct(
        private readonly WebDavServerFactory $factory,
        private readonly ServerRunnerInterface $serverRunner,
    ) {}

    /**
     * Handle the incoming WebDAV request, ensure a Basic Auth attempt exists, and hand execution off to SabreDAV.
     *
     * @param \Illuminate\Http\Request $request Incoming HTTP request for the WebDAV endpoint.
     *
     * @throws \N3XT0R\LaravelWebdavServer\Exception\DomainException When auth, request-context resolution, or storage resolution fails while building the server.
     *
     * @return \Symfony\Component\HttpFoundation\Response Unauthorized response when no Basic Auth attempt exists, otherwise the runtime adapter response.
     */
    public function __invoke(Request $request): Response
    {
        if (! $this->hasBasicAuthAttempt($request)) {
            return response('Unauthorized', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Basic realm="WebDAV"',
            ]);
        }

        $server = $this->factory->make($request);

        return $this->serverRunner->run($server);
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
