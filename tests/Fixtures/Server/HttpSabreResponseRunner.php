<?php

declare(strict_types=1);

namespace N3XT0R\LaravelWebdavServer\Tests\Fixtures\Server;

use Illuminate\Http\Request as IlluminateRequest;
use N3XT0R\LaravelWebdavServer\Contracts\Server\ServerRunnerInterface;
use Sabre\DAV\Server;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response as SabreResponse;
use Symfony\Component\HttpFoundation\Response;

final class HttpSabreResponseRunner implements ServerRunnerInterface
{
    /**
     * Execute the configured SabreDAV server against the current Laravel request and return the raw SabreDAV response.
     *
     * @param  Server  $server  Fully configured SabreDAV server instance.
     * @return Response Symfony response mirroring SabreDAV status, headers, and body.
     */
    public function run(Server $server): Response
    {
        $request = $this->makeSabreRequest(app(IlluminateRequest::class));

        RecordingSabreSapi::reset();
        RecordingSabreSapi::$request = $request;

        $server->sapi = new RecordingSabreSapi;
        $server->httpRequest = $request;
        $server->httpResponse = new SabreResponse;
        $server->start();

        $response = RecordingSabreSapi::$response ?? $server->httpResponse;

        return new Response(
            content: $response->getBodyAsString(),
            status: $response->getStatus(),
            headers: $this->flattenHeaders($response->getHeaders()),
        );
    }

    /**
     * Create a Sabre HTTP request from the current Laravel request.
     *
     * @param  IlluminateRequest  $request  Current Laravel request handled by the package route.
     * @return Request Sabre-compatible request carrying method, URI, headers, and raw body.
     */
    private function makeSabreRequest(IlluminateRequest $request): Request
    {
        $headers = [];

        foreach ($request->headers->all() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        $sabreRequest = new Request(
            method: $request->getMethod(),
            url: $request->getRequestUri(),
            headers: $headers,
            body: $request->getContent(),
        );

        $protocol = (string) $request->server->get('SERVER_PROTOCOL', 'HTTP/1.1');

        $sabreRequest->setHttpVersion(match ($protocol) {
            'HTTP/1.0' => '1.0',
            'HTTP/2.0' => '2.0',
            default => '1.1',
        });
        $sabreRequest->setAbsoluteUrl($request->getSchemeAndHttpHost().$request->getRequestUri());
        $sabreRequest->setRawServerData($request->server->all());

        return $sabreRequest;
    }

    /**
     * Convert Sabre header arrays to the flat header format expected by Symfony responses.
     *
     * @param  array<string, list<string>>  $headers  Sabre response headers grouped by name.
     * @return array<string, string> Flat header map with comma-joined values.
     */
    private function flattenHeaders(array $headers): array
    {
        $flattened = [];

        foreach ($headers as $name => $values) {
            $flattened[$name] = implode(', ', $values);
        }

        return $flattened;
    }
}
