<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use N3XT0R\LaravelWebdavServer\Exception\DomainException;
use N3XT0R\LaravelWebdavServer\Logging\WebDavLoggingService;
use N3XT0R\LaravelWebdavServer\Server\Factory\WebDavServerFactory;
use Symfony\Component\HttpFoundation\Response;

final class WebDavController extends Controller
{
    /**
     * Create the HTTP controller that orchestrates WebDAV request entry handling.
     *
     * @param  WebDavServerFactory  $factory  Factory used to construct the SabreDAV server for the request.
     * @param  ServerRunnerInterface  $serverRunner  Runtime adapter used to hand execution off to SabreDAV.
     * @param  WebDavLoggingService  $logger  Package logger used to trace WebDAV request entry handling.
     */
    public function __construct(
        private readonly WebDavServerFactory $factory,
        private readonly ServerRunnerInterface $serverRunner,
        private readonly WebDavLoggingService $logger,
    ) {}

    /**
     * Handle the incoming WebDAV request, ensure a Basic Auth attempt exists, and hand execution off to SabreDAV.
     *
     * @param  Request  $request  Incoming HTTP request for the WebDAV endpoint.
     * @return Response Unauthorized response when no Basic Auth attempt exists, otherwise the runtime adapter response.
     *
     * @throws DomainException When auth, request-context resolution, or storage resolution fails while building the server.
     */
    public function __invoke(Request $request): Response
    {
        if (! $this->hasBasicAuthAttempt($request)) {
            $this->logger->debug('Rejected WebDAV request without a Basic Auth attempt.', [
                'request' => [
                    'method' => $request->getMethod(),
                    'uri' => $request->getRequestUri(),
                ],
            ]);

            return response('Unauthorized', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => 'Basic realm="WebDAV"',
            ]);
        }

        $server = $this->factory->make($request);

        $this->logger->debug('Handing WebDAV request off to the runtime adapter.', [
            'request' => [
                'method' => $request->getMethod(),
                'uri' => $request->getRequestUri(),
            ],
        ]);

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
